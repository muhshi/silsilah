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
   - Penambahan relasi antar anggota dengan sangat mudah.
   - Tabel relasi khusus (`marriages`) untuk mendata status suami/istri beserta tanggal pernikahannya.

3. **Visualisasi Pohon Keluarga**
   - **Vertical View:** Tampilan silsilah vertikal interaktif yang memungkinkan *drill-down* ke setiap anggota keluarga.
   - Menampilkan fallback avatar dan indikator visual untuk status gender serta status meninggal dunia (Wafat).
   - Sidebar navigasi yang responsif di perangkat desktop dan mobile.

4. **Autentikasi Modern (Google SSO)**
   - Pendaftaran dan Login instan via akun Google menggunakan Laravel Socialite.
   - Pendaftaran standar (email/password) dinonaktifkan untuk mendukung alur satu klik (one-click signup).
   - Halaman login didesain ulang secara eksklusif menggunakan design system lokal (earthy tokens) dengan visual terpisah (split panel).

## 🗄️ Struktur Database Inti
- `users`: Autentikasi dan identitas pengguna (ditambah kolom `google_id` dan `avatar`).
- `family_trees`: Menyimpan nama dan deskripsi pohon silsilah (mendukung pengaturan privasi *is_public*).
- `members`: Tabel utama yang menyimpan data setiap individu dalam pohon keluarga. Memiliki koneksi *self-referencing* untuk `father_id` dan `mother_id`.
- `marriages`: Tabel *pivot* dengan data tambahan yang menghubungkan `husband_id` dan `wife_id`.

## 🛠️ Instalasi & Pengembangan Lokal
1. `composer install`
2. `npm install`
3. Buat file `.env` dan atur koneksi database.
4. Tambahkan kredensial Google API ke `.env`:
   ```env
   GOOGLE_CLIENT_ID=xxx
   GOOGLE_CLIENT_SECRET=xxx
   GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
   ```
5. `php artisan key:generate`
6. `php artisan migrate:fresh --seed` (Seeder akan membuat *dummy data* lengkap dengan relasinya).
7. `composer dev` (Untuk menjalankan Laravel Server, Queue, Pail, dan Vite secara bersamaan menggunakan konfigurasi `livewire-starter-kit`).

## 📝 Changelog
*Catat setiap perubahan teknis di bagian ini mengikuti standar [Keep a Changelog](https://keepachangelog.com/).*

### [v1.2.0] - 2026-08-29
- **Added:** Mode Pohon Ringkas / High-Density Simple Tree Mode (`/tree/{slug}/simple`) dengan pill badge gender-coded dan hierarki kompak (24px) untuk cetak & ekspor skala besar.
- **Added:** Konversi gambar profil dan avatar disk lokal (`public_path()`) langsung menjadi Base64 Data URI saat ekspor PNG/PDF untuk mencegah foto kosong pada Puppeteer/Browsershot.
- **Added:** Material Design 3 Segmented Action Bar (`[ 🌳 Bagan | 🧭 Fokus | 📄 Simple ]`), grup utilitas Bagikan/Ekspor, dan overflow menu dropdown.
- **Added:** Redesain Glassmorphic Top Navbar (`backdrop-blur-xl`) dengan active pill indicator dan menu profil modern.
- **Added:** Dashboard Redesign dengan 3 Hero Metric Cards (Total Silsilah, Total Anggota, Pohon Premium), live search instan, dan Filter Chips dengan hitungan badge.
- **Added:** Grid Ikon Karakter Avatar yang proporsional dan kompak (8–9 ikon per baris) lengkap dengan tombol Reset Pilihan.
- **Changed:** Penanda kematian universal & religion-neutral (format tanggal `(1945 - 2012)` dan tag `Wafat`).
- **Changed:** Reposisi field formulir anggota: Tempat Lahir & Profesi diposisikan tepat di atas Alamat pada form dan modal detail profil.
- **Changed:** Optimasi UX canvas dengan kontrol pan, zoom, dan auto-fit untuk layar HP dan desktop.

### [v1.1.0] - 2026-08-29
- **Added:** Integrasi Google SSO (Laravel Socialite) untuk proses login dan registrasi satu klik.
- **Added:** Layout auth baru bergaya split-panel elegan yang sesuai dengan design system utama (earth tokens).
- **Changed:** Penonaktifan pendaftaran manual (email/password) dan pengalihan seluruh rute pendaftaran ke Google SSO.
- **Added:** Konversi penggunaan external Avatar service menjadi aset Avatar lokal yang disimpan di `public/images/avatar/`.
- **Changed:** Penyesuaian `DatabaseSeeder` dan `MemberManager` untuk mengadopsi struktur penamaan file avatar lokal.
