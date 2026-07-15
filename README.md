# Aplikasi Pohon Keluarga (Silsilah)

Aplikasi Pohon Keluarga (Silsilah) adalah platform manajemen silsilah keluarga berbasis web yang dibangun menggunakan ekosistem Laravel modern. Aplikasi ini memungkinkan pengguna untuk membuat, mengelola, dan memvisualisasikan hubungan kekerabatan secara interaktif.

## 🚀 Teknologi yang Digunakan
Aplikasi ini berjalan di atas teknologi mutakhir dari ekosistem Laravel:
- **Backend:** Laravel 13.x, PHP 8.4+
- **Frontend / Reaktivitas:** Livewire 4.x
- **UI Components:** Flux UI 2.x
- **Styling:** Tailwind CSS 4
- **Authentication:** Laravel Fortify
- **Image Processing:** Intervention Image Laravel 4.x
- **Testing:** PestPHP 4.x

## 🌟 Fitur Utama (Current State)
1. **Manajemen Anggota Keluarga (CRUD)**
   - Mendata informasi personal seperti nama, jenis kelamin, status kehidupan, tanggal lahir/wafat, tempat lahir, profesi, dan biografi.
   - Fitur upload foto dengan kompresi otomatis (max 400px, kualitas 80%).
   - Dukungan pilihan Avatar statis (lokal) sesuai dengan jenis kelamin dan rentang usia.

2. **Manajemen Relasi Kekerabatan**
   - Penambahan relasi antar anggota dengan sangat mudah:
     - Anak (Child of)
     - Orang Tua (Parent of)
     - Pasangan (Spouse of)
     - Mantan Pasangan (Ex of)
   - Tabel relasi khusus (`marriages`) untuk mendata status suami/istri beserta tanggal pernikahannya.

3. **Visualisasi Pohon Keluarga**
   - **Vertical View:** Tampilan silsilah vertikal interaktif yang memungkinkan *drill-down* ke setiap anggota keluarga.
   - Menampilkan fallback avatar dan indikator visual untuk status gender serta status meninggal dunia (Wafat).
   - Sidebar navigasi yang responsif di perangkat desktop dan mobile.

## 🗄️ Struktur Database Inti
- `users`: Autentikasi dan identitas pengguna.
- `family_trees`: Menyimpan nama dan deskripsi pohon silsilah (mendukung pengaturan privasi *is_public*).
- `members`: Tabel utama yang menyimpan data setiap individu dalam pohon keluarga. Memiliki koneksi *self-referencing* untuk `father_id` dan `mother_id`.
- `marriages`: Tabel *pivot* dengan data tambahan yang menghubungkan `husband_id` dan `wife_id`.

## 🛠️ Instalasi & Pengembangan Lokal
1. `composer install`
2. `npm install`
3. Buat file `.env` dan atur koneksi database.
4. `php artisan key:generate`
5. `php artisan migrate:fresh --seed` (Seeder akan membuat *dummy data* lengkap dengan relasinya).
6. `composer dev` (Untuk menjalankan Laravel Server, Queue, Pail, dan Vite secara bersamaan menggunakan konfigurasi `livewire-starter-kit`).

## 📝 Changelog
*Catat setiap perubahan teknis di bagian ini mengikuti standar [Keep a Changelog](https://keepachangelog.com/).*

### [Unreleased]
- **Added:** Konversi penggunaan external Avatar service menjadi aset Avatar lokal yang disimpan di `public/images/avatar/`.
- **Changed:** Penyesuaian `DatabaseSeeder` dan `MemberManager` untuk mengadopsi struktur penamaan file avatar lokal.
