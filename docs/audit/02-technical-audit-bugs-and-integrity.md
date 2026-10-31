# Phase 2A — Functional Integrity & Edge-Case Bug Audit Report

**Komponen Diperiksa:** `RelationshipService.php`, `Member.php`, `Marriage.php`, `⚡member-manager.blade.php`  
**Status:** Audit Temuan Fungsional & Integritas Relasi

---

## 1. Temuan Bug & Edge Case yang Terverifikasi

### BUG-01: Validasi Tanggal Wafat Tanpa Syarat Kronologis (`death_date < birth_date`)
- **Severity:** Medium (Data Integrity)
- **Lokasi:** [`resources/views/components/⚡member-manager.blade.php#L558-L559`](file:///Users/saiful/development/silsilah/resources/views/components/%E2%9A%A1member-manager.blade.php#L558-L559)
- **Bukti Kode:**
  ```php
  'birth_date' => 'nullable|date',
  'death_date' => 'nullable|date',
  ```
- **Dampak:** Pengguna dapat memasukkan tanggal kematian mendahului tanggal kelahiran (misal: lahir tahun 1990, wafat tahun 1980) tanpa peringatan validasi. Hal ini menyebabkan kalkulasi umur negatif pada kartu anggota dan fitur haul/peringatan wafat.
- **Rekomendasi Perbaikan:** Tambahkan validasi kondisional:
  ```php
  'death_date' => 'nullable|date|after_or_equal:birth_date',
  ```
  Serta bersihkan `death_date = null` secara otomatis apabila `$this->is_living === true`.

---

### BUG-02: Validasi Duplikasi Ayah dan Ibu Sebagai Anggota yang Sama
- **Severity:** Medium (Logic Inconsistency)
- **Lokasi:** [`resources/views/components/⚡member-manager.blade.php#L561-L562`](file:///Users/saiful/development/silsilah/resources/views/components/%E2%9A%A1member-manager.blade.php#L561-L562)
- **Bukti Kode:**
  ```php
  'father_id' => 'nullable|exists:members,id',
  'mother_id' => 'nullable|exists:members,id',
  ```
- **Dampak:** Tidak ada aturan `different:mother_id` atau pembatasan gender yang ketat pada level request validation. Jika pengguna secara tidak sengaja memilih ID anggota yang sama untuk kolom ayah dan ibu, sistem akan menyimpan record dengan `father_id == mother_id`.
- **Rekomendasi Perbaikan:** Tambahkan rule `different:mother_id` pada `father_id` dan pastikan gender matching (`father` bertipe `male`, `mother` bertipe `female`).

---

### BUG-03: Rekor Pernikahan Yatim (*Orphaned Marriages*) Saat Anggota Dihapus
- **Severity:** Medium (Referential Integrity / Database Pollution)
- **Lokasi:** [`database/migrations/2026_04_01_065707_create_marriages_table.php#L16-L17`](file:///Users/saiful/development/silsilah/database/migrations/2026_04_01_065707_create_marriages_table.php#L16-L17)
- **Bukti Kode:**
  ```php
  $table->foreignId('husband_id')->nullable()->constrained('members')->nullOnDelete();
  $table->foreignId('wife_id')->nullable()->constrained('members')->nullOnDelete();
  ```
- **Dampak:** Saat salah satu anggota keluarga dihapus dari silsilah, relasi foreign key menjalankan `nullOnDelete()`. Rekor pernikahan di tabel `marriages` tetap ada dengan salah satu pasangan bernilai `NULL`. Jika kedua belah pihak dihapus berurutan, terdapat rekor kosong `husband_id = NULL, wife_id = NULL`.
- **Rekomendasi Perbaikan:**
  Tambahkan `cascadeOnDelete()` atau hook Eloquent `deleting` pada model `Member` untuk membersihkan rekor pernikahan di mana salah satu pihak dihapus, atau membuat event cleanup agar tidak meninggalkan *ghost marriages*.

---

### BUG-04: Potensi Duplikasi Rekor Pernikahan Antar Pasangan yang Sama
- **Severity:** Low to Medium (Data Consistency)
- **Lokasi:** [`database/migrations/2026_04_01_065707_create_marriages_table.php`](file:///Users/saiful/development/silsilah/database/migrations/2026_04_01_065707_create_marriages_table.php)
- **Bukti Kode:** Tidak ada indeks komposit unik:
  ```php
  // Tidak ada: $table->unique(['husband_id', 'wife_id']);
  ```
- **Dampak:** Jika terjadi *double click* atau *concurrent save* saat menghubungkan pasangan, dua baris pernikahan identik dapat tercipta di database. Hal ini menyebabkan query `Member::getSpouses()` atau rendering visualisasi pohon menampilkan pasangan ganda pada node yang sama.
- **Rekomendasi Perbaikan:**
  Tambahkan penanganan *unique check* pada service perkawinan sebelum melakukan `create`, atau tambahkan indeks komposit unik.

---

## 2. Analisis Safeguard yang Sudah Bekerja Baik

1. **Cycle Prevention:** Tes `MemberRelationshipCycleTest` membuktikan bahwa model `Member::getAllDescendantIds()` secara rekursif menelusuri keturunan dan `⚡member-manager` mengecualikan keturunan dari opsi pemilihan orang tua, mencegah terbentuknya *ancestor-descendant loop* (anak menjadi orang tua dari kakeknya).
2. **Infinite Loop Protection di LCA:** Algoritma BFS pada `RelationshipService::getAncestorsWithDistance()` menandai node yang sudah dikunjungi (`if (! isset($ancestors[$pId]))`), sehingga kalkulator nasab kebal terhadap infinite loop.
