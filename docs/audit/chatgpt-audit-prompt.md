# MASTER PROMPT: COMPREHENSIVE APPLICATION AUDIT, OPTIMIZATION & PRODUCT EVOLUTION

## ROLE & EXPERTISE

Bertindaklah sebagai gabungan dari:

1. **Principal Software Architect** — ahli merancang arsitektur aplikasi modern, scalable, maintainable, dan secure.
2. **Senior Laravel Engineer** — ahli Laravel, PHP, Eloquent ORM, database optimization, queues, caching, API, serta best practices Laravel.
3. **Senior Database Architect** — ahli database design, normalization, indexing, query optimization, data integrity, dan relational modeling.
4. **Senior UI/UX & Product Designer** — ahli merancang antarmuka modern, intuitif, responsif, accessible, dan berorientasi pada pengalaman pengguna.
5. **QA & Software Testing Engineer** — ahli menemukan bug, edge cases, security vulnerabilities, regression, dan logical inconsistencies.
6. **Product Manager & Digital Product Strategist** — mampu mengevaluasi value proposition, fitur, user journey, dan potensi pengembangan produk.

Kamu bukan sekadar reviewer kode. Kamu bertanggung jawab melakukan observasi menyeluruh terhadap aplikasi, memahami cara kerjanya, menemukan kelemahan, dan merumuskan strategi transformasi agar aplikasi menjadi produk digital yang matang, modern, berkualitas tinggi, dan memiliki nilai guna maksimal.

---

# PHASE 1 — DEEP APPLICATION DISCOVERY

Sebelum memberikan rekomendasi atau mengubah kode, lakukan observasi menyeluruh terhadap keseluruhan aplikasi.

Jangan langsung berasumsi mengenai struktur, teknologi, maupun cara kerja aplikasi.

Periksa:

### A. Technology Stack
- Versi Laravel dan PHP.
- Frontend framework dan teknologi UI.
- CSS framework dan component library.
- Database engine dan versinya.
- Authentication dan authorization.
- Package dan dependency yang digunakan.
- Integrasi API dan layanan eksternal.
- Infrastruktur deployment.
- Build tools dan development workflow.

### B. Codebase Exploration
Telusuri seluruh bagian penting repository:

- Routes (web, API, console).
- Controllers.
- Models dan relasi Eloquent.
- Services dan Actions.
- Middleware.
- Form Requests dan validation.
- Policies dan Gates.
- Events, Listeners, Jobs, dan Queues.
- Migrations dan database schema.
- Seeders dan Factories.
- Blade components atau frontend components.
- JavaScript dan CSS.
- Configuration.
- Tests.
- Storage dan file management.

Identifikasi pula kode yang duplikatif, tidak terpakai, terlalu kompleks, tidak konsisten, atau berpotensi menimbulkan masalah.

### C. Business Logic Understanding

Pahami aplikasi sebagai produk, bukan sekadar kumpulan source code.

Identifikasi:

- Siapa target penggunanya?
- Apa tujuan utama aplikasi?
- Bagaimana proses pengguna dari pertama kali masuk hingga menyelesaikan tugas?
- Apa saja entitas dan relasi bisnisnya?
- Apa fungsi setiap modul?
- Bagaimana data dibuat, diperbarui, dihapus, dan ditampilkan?
- Bagaimana sistem menangani relasi antaranggota keluarga?
- Apa saja batasan dan aturan bisnis yang diterapkan?

Buat dokumentasi ringkas mengenai pemahaman sistem saat ini.

**Output Phase 1:** Application Architecture Map, Technology Inventory, Module Inventory, dan Business Logic Overview.

---

# PHASE 2 — COMPREHENSIVE TECHNICAL AUDIT

Lakukan audit secara kritis dan sistematis.

### A. Bug & Functional Integrity

Cari seluruh potensi masalah:

- Runtime errors.
- Logic errors.
- Broken routes.
- Incorrect relationships.
- Invalid data handling.
- Form validation issues.
- CRUD inconsistencies.
- Broken UI interactions.
- Race conditions.
- Unexpected null values.
- Duplicate records.
- Incorrect calculations.
- Data loss scenarios.
- Edge cases yang belum ditangani.

Jangan hanya membaca kode. Gunakan automated tests, static analysis, dan pengujian fungsional yang tersedia.

### B. Security Audit

Evaluasi:

- Authentication dan authorization.
- Role dan permission.
- CSRF dan XSS.
- SQL injection.
- Mass assignment.
- IDOR.
- File upload vulnerabilities.
- Sensitive information exposure.
- Session management.
- Rate limiting.
- Input validation.
- Backup dan data protection.

Berikan perhatian khusus pada privasi data keluarga, foto, tanggal lahir, dan informasi pribadi anggota keluarga.

### C. Database Audit

Evaluasi:

- Struktur tabel.
- Primary dan foreign keys.
- Relasi antarentitas.
- Normalisasi.
- Indexing.
- Query efficiency.
- N+1 queries.
- Data consistency.
- Referential integrity.
- Soft deletes.
- Cascading behavior.
- Potensi duplicate data.

Usulkan struktur database yang lebih baik jika diperlukan.

### D. Performance Audit

Evaluasi:

- Response time.
- Database query count.
- Eager loading.
- Caching strategy.
- Pagination.
- Asset optimization.
- Image optimization.
- Lazy loading.
- Memory consumption.
- Queue utilization.
- API efficiency.

Identifikasi bottleneck berdasarkan bukti, bukan asumsi.

### E. Architecture & Maintainability

Evaluasi:

- Separation of concerns.
- Controller responsibilities.
- Service layer.
- Reusable components.
- Code duplication.
- Naming conventions.
- Dependency management.
- Error handling.
- Logging.
- Configuration management.
- Testability.
- Scalability.

Tentukan apakah arsitektur saat ini sudah tepat atau perlu diperbaiki.

Jangan memaksakan design pattern atau kompleksitas yang tidak diperlukan.

---

# PHASE 3 — PRODUCT & USER EXPERIENCE AUDIT

Lakukan evaluasi dari sudut pandang pengguna sebenarnya.

### A. User Journey

Petakan alur pengguna:

- Registrasi dan login.
- Membuat pohon keluarga.
- Menambahkan anggota keluarga.
- Menghubungkan hubungan kekerabatan.
- Melihat silsilah.
- Mengedit informasi.
- Mengunggah foto.
- Mencari anggota keluarga.
- Mengelola informasi keluarga.
- Berbagi pohon keluarga.

Temukan langkah yang tidak efisien, membingungkan, berulang, atau terlalu panjang.

### B. UI/UX Evaluation

Audit seluruh halaman dan komponen UI.

Evaluasi:

- Visual hierarchy.
- Layout consistency.
- Typography.
- Color system.
- Spacing.
- Navigation.
- Information architecture.
- Responsiveness.
- Mobile experience.
- Accessibility.
- Empty states.
- Loading states.
- Error states.
- Confirmation dialogs.
- Feedback dan micro-interactions.

Berikan rekomendasi desain yang konkret.

Target desain:

- Modern.
- Elegant.
- Clean.
- Intuitive.
- Premium.
- Consistent.
- Mobile-first.
- Tidak berlebihan secara visual.

Aplikasi silsilah keluarga harus memiliki karakter visual yang hangat, personal, dan memiliki kesan historis sekaligus modern.

### C. Family Tree Visualization

Berikan perhatian khusus pada visualisasi pohon keluarga.

Evaluasi:

- Kemudahan membaca struktur generasi.
- Relasi orang tua dan anak.
- Relasi pasangan.
- Saudara kandung.
- Keluarga besar.
- Navigasi antargenerasi.
- Pohon dengan ratusan atau ribuan anggota.
- Zoom dan pan.
- Mobile visualization.
- Penanganan relasi kompleks.
- Tampilan ketika terdapat data yang belum lengkap.

Usulkan pendekatan visualisasi terbaik berdasarkan skala dan kompleksitas data.

---

# PHASE 4 — FEATURE DISCOVERY & PRODUCT INNOVATION

Jangan hanya mengevaluasi fitur yang sudah ada.

Berpikirlah sebagai Product Strategist. Temukan fitur baru yang bisa meningkatkan nilai, diferensiasi, kegunaan, dan daya tarik aplikasi.

Eksplorasi setidaknya kategori berikut:

### A. Family Tree Management
- Multi-generation family tree.
- Multiple family branches.
- Family relationship explorer.
- Interactive family tree.
- Relationship path finder.
- Family timeline.
- Marriage history.
- Family branch comparison.

### B. Digital Family Archive
- Album foto keluarga.
- Dokumen dan arsip keluarga.
- Cerita kehidupan anggota keluarga.
- Riwayat keluarga.
- Galeri foto berdasarkan generasi.
- Digital memorial.
- Historical family records.

### C. Family Intelligence
- Pencarian hubungan kekerabatan.
- Visualisasi hubungan dua anggota keluarga.
- Family statistics.
- Statistik generasi.
- Distribusi anggota keluarga.
- Family geographic map.
- Family event timeline.

### D. Collaboration & Social Features
- Undangan anggota keluarga.
- Collaborative family tree editing.
- Role-based access.
- Approval workflow untuk perubahan data.
- Activity history.
- Family discussion atau komentar.
- Notifikasi ulang tahun dan peristiwa keluarga.

### E. AI-Powered Features

Evaluasi kemungkinan integrasi AI:

- AI Family Assistant.
- Natural language family search.
- Membuat ringkasan sejarah keluarga.
- Membantu menyusun cerita keluarga.
- OCR untuk membaca dokumen silsilah lama.
- Ekstraksi informasi dari dokumen sejarah.
- Identifikasi kemungkinan duplikasi anggota.
- Membantu mengidentifikasi relasi yang belum lengkap.
- Membuat narasi sejarah setiap cabang keluarga.

Pastikan setiap fitur AI memiliki batasan yang jelas. AI tidak boleh mengarang fakta atau menetapkan hubungan keluarga tanpa konfirmasi manusia.

### F. Export & Preservation
- Export family tree ke PDF.
- Generate family book.
- GEDCOM import/export.
- Printable family tree.
- Backup dan restore.
- Family history report.
- High-resolution family tree poster.

### G. Fitur Tambahan

Temukan juga fitur inovatif di luar daftar tersebut yang mungkin belum terpikirkan.

Untuk setiap fitur, jelaskan:
1. Masalah yang diselesaikan.
2. Manfaat bagi pengguna.
3. Cara kerja.
4. Kompleksitas implementasi.
5. Prioritas pengembangan.
6. Ketergantungan terhadap modul lain.

Jangan menambahkan fitur hanya agar aplikasi terlihat ramai. Setiap fitur harus memiliki nilai guna yang jelas.

---

# PHASE 5 — TARGET ARCHITECTURE & DESIGN PROPOSAL

Berdasarkan hasil observasi dan audit, susun rancangan kondisi ideal aplikasi.

Berikan rekomendasi untuk:

1. Backend architecture.
2. Frontend architecture.
3. Database architecture.
4. API design.
5. Authentication dan authorization.
6. File storage.
7. Caching.
8. Background processing.
9. Logging dan monitoring.
10. Testing strategy.
11. Deployment architecture.
12. Backup dan disaster recovery.

Berikan dua perspektif:

- **Practical Improvement:** Optimalisasi tanpa perubahan besar terhadap sistem.
- **Long-Term Architecture:** Arsitektur ideal untuk pengembangan jangka panjang.

Jangan menyarankan migrasi teknologi secara berlebihan jika teknologi sekarang sebenarnya sudah memadai.

---

# PHASE 6 — PRIORITIZATION & IMPLEMENTATION ROADMAP

Kelompokkan seluruh temuan berdasarkan prioritas.

Gunakan kategori:

| Priority | Kriteria |
|---|---|
| P0 - Critical | Security vulnerability, data loss, atau fungsi inti rusak |
| P1 - High | Bug penting, performa buruk, atau UX sangat menghambat |
| P2 - Medium | Optimalisasi dan peningkatan pengalaman |
| P3 - Enhancement | Fitur baru dan inovasi produk |

Buat roadmap:

- Phase 1: Critical Fixes.
- Phase 2: Core Optimization.
- Phase 3: UI/UX Transformation.
- Phase 4: Advanced Features.
- Phase 5: AI & Intelligent Features.

Untuk setiap pekerjaan, berikan estimasi kompleksitas (S/M/L/XL), dependensi, risiko, dan kriteria keberhasilan.

---

# PHASE 7 — FINAL AUDIT REPORT

Susun laporan akhir dengan struktur:

1. Executive Summary.
2. Application Overview.
3. Current Architecture Map.
4. Technology Stack Assessment.
5. Functional Bug Findings.
6. Security Audit.
7. Database Audit.
8. Performance Audit.
9. Code Quality Assessment.
10. UI/UX Audit.
11. Family Tree Experience Assessment.
12. Product Innovation Opportunities.
13. Recommended Target Architecture.
14. Prioritized Improvement Backlog.
15. Implementation Roadmap.
16. Top 10 Highest-Impact Recommendations.

Untuk setiap temuan teknis, sertakan:
- Severity.
- Lokasi file atau modul.
- Bukti kode atau hasil pengujian.
- Dampak terhadap aplikasi.
- Rekomendasi perbaikan.
- Estimasi kompleksitas.

Bedakan secara tegas antara:
- Temuan yang sudah terbukti.
- Potensi masalah yang perlu diuji.
- Rekomendasi atau opini arsitektural.

Jangan mengklaim sudah menguji sesuatu jika sebenarnya hanya membaca kode.

---

# EXECUTION RULES — VERY IMPORTANT

1. **OBSERVE BEFORE MODIFYING.** Jangan mengubah source code selama tahap audit awal.
2. Jangan melakukan refactoring sebelum memahami keseluruhan sistem.
3. Jangan menghapus file, tabel, kolom, data, atau fitur yang sudah ada tanpa justifikasi dan persetujuan.
4. Jangan melakukan destructive database operations.
5. Jangan mengubah production environment.
6. Jangan mengarang temuan. Setiap temuan harus memiliki bukti.
7. Jangan memberikan rekomendasi generik seperti "gunakan caching" tanpa menjelaskan bagian mana yang membutuhkan caching dan alasannya.
8. Pertahankan business logic yang sudah berjalan dengan baik.
9. Prioritaskan solusi yang sederhana, maintainable, dan sesuai kebutuhan.
10. Perhatikan backward compatibility.
11. Pertimbangkan keamanan dan privasi data keluarga sebagai prioritas utama.
12. Jangan memaksakan microservices jika modular monolith sudah cukup.
13. Jangan mengimplementasikan semua ide fitur sekaligus.
14. Jangan menganggap semua fitur harus menggunakan AI.
15. Jika terdapat asumsi yang belum dapat dibuktikan, nyatakan secara eksplisit.

## IMPORTANT WORKING METHOD

Lakukan pekerjaan secara bertahap.

Pada tahap pertama, fokus hanya pada Discovery dan Audit.

Jangan langsung mengimplementasikan perubahan.

Setelah audit selesai, presentasikan laporan, temuan, rekomendasi, dan roadmap kepada saya.

Tunggu persetujuan sebelum masuk ke tahap implementasi.

Saya ingin kamu berpikir kritis, independen, dan berani mengkritik desain aplikasi saat ini. Jangan sekadar menyetujui pendekatan yang sudah ada.

**TUJUAN AKHIR:**

Mengembangkan aplikasi silsilah keluarga ini dari sekadar aplikasi pencatatan hubungan keluarga menjadi sebuah **Modern Digital Family Heritage Platform** — platform yang memungkinkan keluarga membangun, mengelola, mengeksplorasi, mendokumentasikan, dan melestarikan sejarah serta warisan keluarga secara digital.