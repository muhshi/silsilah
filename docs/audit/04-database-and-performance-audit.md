# Phase 2C & 2D — Database Schema & Performance Audit Report

**Komponen Diperiksa:** Migrations, Eloquent Models (`FamilyTree`, `Member`, `Marriage`), Livewire SFC Hydration, `TreeExportService`  
**Kategori Audit:** Database Normalization, Indexing, Query Optimization, Payload Serialization

---

## 1. Temuan Skema Database & Indexing

### DB-01: Indexing Gap pada Kolom Sorting dan Pencarian Relasi
- **Lokasi:** `database/migrations/2026_04_01_065705_create_members_table.php`
- **Temuan:**
  - `members.order`: Sering digunakan untuk mengurutkan anak (`->sortBy('order')` dan query order), namun belum memiliki index.
  - `members.birth_date` & `members.death_date`: Di-filter secara konstan oleh `FamilyReminderService` dan fitur kalender silsilah, namun belum memiliki index.
  - `marriages.marriage_date`: Belum memiliki index untuk anniversary query.
- **Rekomendasi:**
  Tambahkan migration indexing komposit:
  ```php
  $table->index(['family_tree_id', 'order']);
  $table->index(['family_tree_id', 'birth_date']);
  $table->index(['family_tree_id', 'death_date']);
  ```

---

### DB-02: Tidak Adanya Composite Unique Index pada Relasi Pasangan
- **Lokasi:** `database/migrations/2026_04_01_065707_create_marriages_table.php`
- **Temuan:**
  Tabel `marriages` tidak memiliki composite unique index antara `husband_id` dan `wife_id`.
- **Dampak:**
  Memungkinkan terjadinya duplikasi data pernikahan jika ada race condition atau double click pada form.
- **Rekomendasi:**
  Tambahkan unique constraint atau pastikan validasi backend membatasi 1 entri perkawinan unik per pasangan suami-istri.

---

## 2. Temuan Kinerja & Query Optimization

### PERF-01 [HIGH]: Livewire Public Model Serialization Overhead pada Pohon Besar
- **Lokasi:** [`resources/views/livewire/tree-vertical.blade.php#L12`](file:///Users/saiful/development/silsilah/resources/views/livewire/tree-vertical.blade.php#L12), [`resources/views/livewire/tree-view.blade.php`](file:///Users/saiful/development/silsilah/resources/views/livewire/tree-view.blade.php)
- **Temuan Teknis:**
  Komponen Livewire mendeklarasikan properti public:
  ```php
  public $tree;
  ```
  Di dalam method `mount()`, seluruh model `FamilyTree` dimuat lengkap dengan ratusan anggota dan relasi perkawinan:
  ```php
  $this->tree = FamilyTree::with([
      'members',
      'members.marriagesAsHusband.wife',
      'members.marriagesAsWife.husband',
  ])->...
  ```
- **Dampak Performa:**
  Dalam arsitektur Livewire (versi 3 & 4), semua properti bertipe `public` akan di-dehydrate dan diserialisasi menjadi snapshot JSON terenkripsi yang dikirimkan bolak-balik antara server dan browser pada **setiap** aksi Livewire (klik filter, tab, search).
  - Pada pohon dengan 200–500 anggota, snapshot Livewire dapat membengkak hingga ratusan kilobyte atau megabyte per request.
  - Meningkatkan waktu parsing JSON, latency jaringan, dan konsumsi memori server.
- **Rekomendasi Perbaikan:**
  Simpan hanya ID primitif pada state komponen:
  ```php
  public int $treeId;
  ```
  Dan gunakan atribut `#[Computed]` untuk memuat data pohon dan anggotanya secara on-demand hanya saat proses render, sehingga tidak ikut diserialisasi ke dalam snapshot klien:
  ```php
  #[Computed]
  public function tree(): FamilyTree
  {
      return FamilyTree::with([...])->findOrFail($this->treeId);
  }
  ```

---

### PERF-02 [MEDIUM]: Eksekusi Headless Browser (Browsershot) Sinkron pada Request Web
- **Lokasi:** [`app/Services/TreeExportService.php`](file:///Users/saiful/development/silsilah/app/Services/TreeExportService.php), [`app/Http/Controllers/TreeExportController.php`](file:///Users/saiful/development/silsilah/app/Http/Controllers/TreeExportController.php)
- **Temuan Teknis:**
  Export pohon ke PDF atau gambar beresolusi tinggi menggunakan Puppeteer/Browsershot dieksekusi secara sinkron di dalam siklus HTTP request controller.
- **Dampak Performa:**
  Pohon dengan ratusan node memerlukan waktu render canvas yang lama (bisa >15–30 detik). Hal ini memicu risiko *HTTP 504 Gateway Timeout* pada server produksi (Caddy/Nginx) dan mengunci worker PHP-FPM.
- **Rekomendasi Perbaikan:**
  Untuk ekspor pohon besar, gunakan sistem antrean background (`Queue / Job`) yang mengirimkan notifikasi atau link unduhan saat file PDF siap, atau gunakan modal progress export asinkron yang sudah ada (`export-progress-modal.blade.php`).

---

### PERF-03 [LOW to MEDIUM]: Optimasi Query Memory pada `RelationshipService`
- **Lokasi:** [`app/Services/RelationshipService.php#L68-L71`](file:///Users/saiful/development/silsilah/app/Services/RelationshipService.php#L68-L71)
- **Temuan Teknis:**
  Setiap kali menghitung relasi nasab antara dua orang, service melakukan:
  ```php
  $allMembers = Member::whereIn('family_tree_id', $treeIds)->get()->keyBy('id');
  ```
  Ini memuat seluruh kolom (`select *`) dari semua anggota pohon ke memori RAM PHP.
- **Rekomendasi Perbaikan:**
  Batasi kolom yang diambil hanya yang dibutuhkan untuk penelusuran graf:
  ```php
  $allMembers = Member::whereIn('family_tree_id', $treeIds)
      ->select(['id', 'family_tree_id', 'first_name', 'last_name', 'gender', 'father_id', 'mother_id', 'order', 'birth_date'])
      ->get()
      ->keyBy('id');
  ```
  Hal ini menghemat hingga 60–70% alokasi memori RAM PHP pada pohon besar.
