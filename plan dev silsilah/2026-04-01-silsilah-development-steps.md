# Langkah Pengembangan: Silsilah Keluarga (Livewire Edition)

**Tanggal:** 1 April 2026
**Base:** Fresh Laravel 13
**Target Stack:** Laravel 13 + Livewire 4 + Laravel Breeze + Tailwind CSS 4 + panzoom.js

> [!IMPORTANT]
> **Filament telah dihapus dari stack** sesuai kesepakatan untuk menjaga UI/UX se-simple dan se-mirip mungkin dengan Sketsaweb (1 halaman canvas).
> Kerjakan **berurutan per fase**. Jangan loncat.

---

## FASE 1: Foundation — Breeze & Database

**Goal:** Login system (Breeze), database ter-migrate, models dengan relasi lengkap jalan.

### 1.1 Init Environment

Karena stack berubah, jalan paling mudah untuk clean state (jika sudah ada sisa Filament):
```bash
# Kalau belum buat project:
# laravel new silsilah --breeze --stack=livewire --dark --phpunit --git --no-interaction
```
Jika pakai project yang sudah ada:
```bash
composer require laravel/breeze --dev
php artisan breeze:install livewire --dark
```

### 1.2 Storage Link & Image Package

```bash
composer require intervention/image-laravel
php artisan storage:link
```

### 1.3 Update Migrations & Models

Buat file migration yang sama persis seperti PRD sebelumnya (Tabel `family_trees`, `family_tree_user`, `members`, `marriages`). Ingat bahwa field `website` dan `email` **dihapus** dari `members` (Hanya FB, IG, WA).

```bash
php artisan make:migration create_family_trees_table
php artisan make:migration create_family_tree_user_table
php artisan make:migration create_members_table
php artisan make:migration create_marriages_table
```

*Lihat PRD untuk struktur kolom.*

Buat Model beserta Factory:
```bash
php artisan make:model FamilyTree -f
php artisan make:model Member -f
php artisan make:model Marriage -f
```

*Tambahkan relationship (HasMany, BelongsTo), fillable, dan attribute mutators seperti di PRD pada masing-masing model.*

### 1.4 Dummy Data (Seeder)

Siapkan 18 avatar presets di `public/avatars/1.jpg` s/d `18.jpg` dan `no_profile_pic.jpg`.

Update `DatabaseSeeder.php` untuk membuat 1 User tester, 1 FamilyTree, dan hirarki member 3 generasi menggunakan factory.

```bash
php artisan migrate:fresh --seed
```

---

## FASE 2: Layout & Dashboard (Silsilah Keluargaku)

**Goal:** Menampilkan list/card silsilah persis seperti di `list_silsilah.html`.

### 2.1 Layout Utama

Ubah layout bawaan Breeze di `resources/views/layouts/app.blade.php`.
- Buat top nav navigation persis seperti Sketsaweb (Logo kiri, profile kanan).
- Buang sidebar. Layout menjadi 1 kolom penuh (kontainer di tengah untuk list pohon).

### 2.2 Livewire Component: Dashboard

```bash
php artisan make:livewire Dashboard
```
Ganti route `/dashboard` di `routes/web.php` untuk merujuk ke component ini.

**Fitur di Component ini:**
- Menampilkan daftar `div.pt-list-item` (card) yang memuat nama pohon, waktu dibuat (`diffForHumans()`), dan total member `->members()->count()`.
- Tombol "+ Buat Silsilah".

### 2.3 Modal Create Silsilah (Alpine/Livewire)

- Tambahkan modal (Bisa pakai komponen bawaan `<x-modal>` dari Breeze).
- Form: Nama Keluarga, Deskripsi, Toggle Public/Private.
- Method `saveTree()` di komponen `Dashboard` yang akan redirect ke `/tree/{id}`.

---

## FASE 3: Canvas Tree (Horizontal CSS Tree)

**Goal:** Menampilkan Visualisasi Tree.

### 3.1 Livewire Component: FamilyTreeView

```bash
php artisan make:livewire FamilyTreeView
```
Routing: `Route::get('/tree/{tree}', \App\Livewire\FamilyTreeView::class)->middleware('auth');`

### 3.2 Gate & Policy

Pastikan user hanya bisa buka `/tree/{id}` jika mereka punya akses (Owner/Editor di pivot `family_tree_user`). Tambahkan Gate/Policy check.

### 3.3 CSS Tree Layout

- Buat `resources/css/tree.css`. Salin styling layout `<ul><li>` dan pseudo elements garis penghubung dari PRD.
- Include di `vite.config.js` atau import di `app.css`.

### 3.4 Blade Recursive Component (Tree Node)

Buat `resources/views/components/tree-node.blade.php`. Parameter: `$member`, `$marriages`.
(Referensi Utama: Gunakan struktur HTML dari `horizontal_view.html` untuk class seperti `.haswife`, `.partner`, `.pt-thumb` dan `vertical_view.html` untuk view card).

### 3.5 Panzoom.js

Embed di `/tree/{id}` view:
```html
<script src="https://unpkg.com/@panzoom/panzoom@4/dist/panzoom.min.js"></script>
```
Gunakan Alpine.js `x-init` pada div utama (contoh `<div class="tree-container" x-ref="container">`) untuk wrap Tree Node.

---

## FASE 4: Modal CRUD Interaktif

**Goal:** 1 klik Action "Tambah/✏️" → buka form detail dari Canvas.

### 4.1 UI Modal

Tambahkan `<x-modal name="memberModal">` di halaman `FamilyTreeView`.
Pecah form menjadi 3 Tab (Alpine x-data="tab: 1"):
- **Pribadi** (Hubungan: Anak/Mantan/Pasangan/Parent, Nama, Tanggal, Avatar Picker, Foto).
- **Kontak** (WA, FB, IG, Alamat).
- **Biografi** (Lahir di, Wafat di, Perusahaan, Hobby, Bio).

### 4.2 Form Handling & File Upload

Tambahkan state variables di component Livewire:
```php
use WithFileUploads;
public $memberForm = [];
public $photo; // temporary upload
public $parentId; // ID member yang diklik action-nya
```

### 4.3 Logika Hubungan (Relationship Logic)

Buat method `saveMember()` yang menangani 4 percabangan:
- **Anak:** Set `father_id` / `mother_id` dari `$parentId`.
- **Pasangan:** Create member, lalu insert ke `marriages` `is_current=1`.
- **Mantan:** Create member, insert ke `marriages` `is_current=0`.
- **Orang Tua:** Create member, ubah `$parentId` dengan `father_id`/`mother_id` member baru.

Refresh component canvas agar hirarki otomatis update tanpa loading 1 halaman.

---

## FASE 5: Public View, Export & Polishing

### 5.1 Public Link View

- Buat controller `PublicTreeController` untuk route `GET /s/{slug}` (Tanpa Middleware Auth).
- View ini me-load Tree Node component yang sama persis `<x-tree-node>`, namun menghilangkan (hide) tombol *Action (Tambah/Edit/Hapus)*.

### 5.2 Export Image

- Install/CDN `html2canvas`.
- Tambahkan logic Button Download SVG->PNG yang melakukan screen capture `.tree-container` lalu download file.

### 5.3 Profiling & Refactoring N+1 Queries

Pastikan query Tree Node me-load relasi sekaligus (`with('childrenAsFather', 'childrenAsMother', 'spouses')`) agar tidak terjadi query N+1 karena sifat recursive component. Menggunakan Collection filtering di backend ($rootMembers) sebelum passing ke component. 

---

**Selesai**. Ini jauh lebih lightweight daripada Filament dan menjamin layout kustom dapat dijalankan.
