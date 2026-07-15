# Project Planning: Aplikasi Pohon Keluarga (Silsilah)

Dokumen ini berisi *roadmap* dan rencana pengembangan untuk aplikasi Pohon Keluarga. Karena aplikasi masih dalam tahap *building*, perencanaan ini bersifat fleksibel dan akan terus diperbarui seiring dengan perkembangan *requirements* dan arsitektur aplikasi.

## 🎯 Visi Proyek
Membuat platform pengelolaan silsilah keluarga yang modern, interaktif, responsif, dan mudah digunakan (User-Friendly) dengan visualisasi yang menarik, baik diakses melalui Desktop maupun Mobile.

---

## 🗺️ Roadmap Pengembangan

### 🟢 Phase 1: Foundation & Core Mechanics (Sedang Berjalan / Selesai)
Fokus pada membangun pondasi aplikasi, struktur database, dan fitur CRUD dasar.
- [x] Inisialisasi Project (Laravel 13 + Livewire 4 + Flux UI + Tailwind 4).
- [x] Desain Skema Database (Users, FamilyTrees, Members, Marriages).
- [x] CRUD Anggota Keluarga (Member Manager Modal dengan Flux UI).
- [x] Implementasi Logika Relasi (Father, Mother, Spouse, Child).
- [x] Integrasi Foto & Manajemen Avatar Lokal (Intervention Image).
- [x] Visualisasi Dasar Pohon (Vertical View).

### 🟡 Phase 2: Interactivity & Feature Enhancements (Tahap Selanjutnya)
Membuat visualisasi lebih *powerful* dan menambahkan fitur kolaborasi.
- [ ] **Advanced Tree Visualization:**
  - Tambahan tampilan Horizontal/Pan-Zoom (*Canvas-based* atau struktur DOM yang *draggable*).
  - Tampilan *Simple Tree* atau *Pedigree View* (leluhur ke atas).
- [ ] **Privacy & Sharing:**
  - Akses publik *Read-Only* ke pohon silsilah (bisa dilindungi dengan PIN/Password).
  - Sistem *Invitation* agar anggota keluarga lain bisa login dan ikut mengedit pohon keluarga bersama (Kolaborasi).
  - Role-based Access Control (Owner, Editor, Viewer).
- [ ] **Data Export & Import:**
  - Kemampuan Export visual pohon menjadi gambar (PNG/PDF) atau cetak kualitas tinggi.
  - Import data anggota dari *file* CSV / Excel.

### 🔴 Phase 3: Performance & Polish (Masa Depan)
Fokus pada skalabilitas, *performance*, dan *Quality of Life (QoL)* *improvements*.
- [ ] **Optimization untuk Data Besar:**
  - *Lazy loading* cabang-cabang silsilah jika jumlah *member* sudah mencapai ribuan (mencegah *browser freeze*).
  - Optimasi query database (mencegah *N+1 queries* saat merender pohon raksasa).
- [ ] **Notifikasi & Pengingat:**
  - Reminder hari ulang tahun atau hari peringatan kematian anggota keluarga.
- [ ] **Event Logging / History:**
  - Mencatat siapa yang mengedit, menambah, atau menghapus anggota silsilah (Audit Trail).
- [ ] **Testing Menyeluruh:**
  - Unit & Feature testing (PestPHP) untuk memastikan logika silsilah (misal: validasi agar seseorang tidak bisa menjadi orang tua dari kakeknya sendiri).

---

## 📝 Catatan Arsitektur (Bagi AI/Developer Selanjutnya)
1. **Livewire First:** Hampir seluruh interaksi (modal form, manipulasi data, navigasi antar-anggota) dikelola via komponen Livewire dan Alpine.js. Pastikan menjaga performa respons dengan tidak memuat terlalu banyak data dalam state Livewire (gunakan fitur `#[Computed]` jika memungkinkan).
2. **UI Konsisten:** Aplikasi ini *heavily relies* pada *Flux UI*. Gunakan `<flux:modal>`, `<flux:input>`, dan standar Flux lainnya untuk menjaga konsistensi komponen daripada membuat *custom DOM* dari nol, kecuali sangat terpaksa.
3. **Penyimpanan Gambar:** Foto *member* di-compress sebelum masuk server untuk menghemat *storage*, lalu *path*-nya disimpan ke database. Avatar menggunakan aset statis yang disimpan di `/public/images/avatar/`.
