# Changelog

Semua perubahan penting pada aplikasi **Silsilah** dicatat dalam dokumen ini.

## [v1.2.0] - 2026-08-29

### 🚀 Fitur Baru & Peningkatan (Features & Enhancements)
- **High-Density Simple Tree Mode (`/tree/{slug}/simple`)**:
  - Tampilan pohon keluarga ringkas berbasis pill badge (tinggi ~26px) dengan border penanda gender (Teal/Pria & Pink/Wanita).
  - Jarak hierarki generasi ultra-kompak (24px generasi, 4px sibling) untuk memuat ratusan nama dalam 1 lembar cetak atau ekspor.
  - Dilengkapi kontrol Pan/Zoom canvas interaktif, Auto-Fit, dan mode cetak/ekspor vektor penuh tanpa terpotong.
- **Religion-Neutral Indicator**:
  - Penanda tanggal lahir-wafat dan status tanpa simbol keagamaan tertentu (format tanggal `(1945 - 2012)` dan tag `Wafat`).
- **Export Local Base64 Inlining**:
  - Seluruh avatar, foto profil custom, dan fallback image kini langsung dikonversi ke Base64 Data URI dari storage disk lokal (`public_path()`) saat diekspor ke PNG/PDF dengan Browsershot/Puppeteer.
- **M3 Segmented Action Bar & Navigation**:
  - Segmented View Switcher Pill terpadu: `[ 🌳 Bagan | 🧭 Fokus | 📄 Simple ]`.
  - Pengelompokan rapi tombol aksi: `Bagikan`, `Ekspor ▾`, menu dropdown `Opsi ⚙️` (Import GEDCOM/CSV & Pengaturan), dan Primary CTA `➕ Anggota Baru`.
  - Glassmorphic Top Navbar (`backdrop-blur-xl`) dengan active indicator, brand logo gradient, dan profile chip dropdown.
- **Material Design 3 Dashboard Upgrade**:
  - 3 Hero Metric Cards: *Total Silsilah*, *Total Anggota*, dan *Pohon Premium*.
  - Live Search instan dan Filter Chips dengan badge hitungan data: `[Semua (N)]`, `[🌍 Publik (N)]`, `[🔒 Privat (N)]`, `[👑 Premium (N)]`.
  - Elevated Tree Cards berlekuk `rounded-3xl` dengan elevasi halus saat hover, status publik/privat, dan aksi cepat.
- **Form & Member Profile Improvements**:
  - Grid pemilihan ikon avatar karakter kini kompak dan proporsional (8–9 ikon per baris) dilengkapi highlight ring dan tombol *Reset Pilihan*.
  - Reposisi bidang formulir: **Tempat Lahir** dan **Profesi** diposisikan tepat di atas **Alamat** pada Form Tambah/Edit Anggota dan Modal Detail Profil.

### 🧪 Pengujian & Kualitas Kode
- 54 automated feature tests lulus 100% (`php artisan test --compact`).
- Pembersihan gaya penulisan kode PHP dengan Laravel Pint.
