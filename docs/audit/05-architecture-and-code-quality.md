# Phase 2E — Code Architecture, Service Layer & Maintainability Audit Report

**Komponen Diperiksa:** Single-File Components (`⚡*.blade.php`), Domain Services (`app/Services/*`), Formats & Code Quality  
**Status:** Audit Struktur & Pemeliharaan Kode

---

## 1. Analisis Kualitas Kode & Standar Pemrograman

- **Laravel Pint Compliance:** `PASS` (100% lulus pengecekan standar PSR-12 / Laravel Code Style).
- **Type Hinting & Return Types:** Sebagian besar method di `app/Services/` telah menggunakan PHP 8 strict types dan PHPDoc array shape (`@return array{...}`).
- **Skinny Controllers:** Controller di `app/Http/Controllers/` sebagian besar berukuran kecil (<100 baris), mendelegasikan logika ke Service Layer (`RelationshipService`, `DuitkuService`, `DatabaseBackupService`).

---

## 2. Temuan Arsitektural: "God Components" pada Livewire SFC

Penggunaan Livewire 4 Single-File Components (`⚡*.blade.php`) memberikan kemudahan *colocation* antara logika backend dan template Blade. Namun, beberapa komponen telah berkembang menjadi *monolithic god components*:

| Nama Komponen | Jumlah Baris | Tanggung Jawab / Fitur yang Digabungkan |
|---|---|---|
| [`⚡member-manager.blade.php`](file:///Users/saiful/development/silsilah/resources/views/components/%E2%9A%A1member-manager.blade.php) | **1,608 baris** | CRUD Anggota, Detail Modal, Upload Foto, Random Avatar, Manajemen Perkawinan Multi-Pasangan, Cross-Tree Cloning, Anti-Cycle Detection. |
| [`⚡tree-settings.blade.php`](file:///Users/saiful/development/silsilah/resources/views/components/%E2%9A%A1tree-settings.blade.php) | **1,090 baris** | Visibilitas Pohon, Password Gate, Undangan Kolaborator, Log Aktivitas, Export / Import, Transfer Owner, Hapus Pohon. |
| [`⚡dashboard-trees.blade.php`](file:///Users/saiful/development/silsilah/resources/views/components/%E2%9A%A1dashboard-trees.blade.php) | **1,057 baris** | Grid Pohon, Modal Pembuatan Pohon, Kuota & Billing Modal, Pencarian & Filter, Referral & Voucher. |
| [`⚡family-reminders.blade.php`](file:///Users/saiful/development/silsilah/resources/views/components/%E2%9A%A1family-reminders.blade.php) | **675 baris** | Kalender Acara, Modal Tambah Event, Share WhatsApp, Filter Jenis Peringatan (Haul/Ulang Tahun). |

### Dampak Pemeliharaan (*Maintainability Risk*):
1. **Cognitive Load:** Pengembang baru kesulitan menavigasi file 1.600 baris yang mencampur logika form, event dispatching, dan markup modal.
2. **Uji Unit & Regresi:** Setiap perubahan kecil pada form anggota berisiko merusak logika modal perkawinan atau cross-tree cloning karena state berada dalam satu namespace properti yang sangat besar.
3. **Peluang Bug State:** Method `resetForm()` harus me-reset lebih dari 25 properti secara manual, rawan meninggalkan sisa state form sebelumnya (*stale state*).

### Rekomendasi Modularisasi:
Pecah `⚡member-manager.blade.php` menjadi sub-komponen terfokus:
- `components/member/member-form.blade.php` (Input data diri & foto).
- `components/member/member-marriage-manager.blade.php` (Manajemen tanggal & status pasangan).
- `components/member/member-profile-modal.blade.php` (Kartu baca-saja detail profil).
- `components/member/member-clone-modal.blade.php` (Kloning antar pohon).

---

## 3. Evaluasi Kohesi Service Layer

- **Duplikasi Logika Ekspor:**
  `TreeExportService.php` (715 baris) dan `TreeBannerExportService.php` (540 baris) memiliki logika serupa dalam menentukan root members, mengurai garis generasi, dan resolusi warna node. Keduanya dapat mewarisi *base class* atau trait bersama (`TreeTraversalTrait`).
- **Pemisahan Notifikasi & Kalender:**
  `FamilyReminderService.php` (768 baris) menggabungkan kalkulasi tanggal Jawa/Hijriah/Masehi dengan logika pembuatan pesan WhatsApp dan Telegram. Disarankan memisahkan kalkulasi tanggal ke dalam `FamilyCalendarEngine` terpisah agar *pure logic* terisolasi dari *message formatting*.
