# Phase 5 & 6 — Target Architecture & Implementation Roadmap

---

## 1. Target Architecture Proposal

### A. Practical Architecture (Optimasi Cepat Tanpa Perombakan Ekstrem)
Pilihan ini mempertahankan stack yang ada saat ini (Laravel 13 + Livewire 4 SFC + Tailwind 4) dengan perbaikan terarah:

1. **Scoped Authorization Guard:** Semua query mutasi anggota dan perkawinan di dalam Livewire wajib melalui relasi `$tree->members()->findOrFail($id)` untuk mengeliminasi celah IDOR.
2. **State Hydration Optimization:** Mengganti properti Livewire publik `public $tree` dengan `public int $treeId` dan memanfaatkan atribut `#[Computed]` agar snapshot JSON Livewire tetap ramping (<10 KB).
3. **Database Indexing:** Menambahkan composite index pada tabel `members` (`family_tree_id + order`, `family_tree_id + birth_date`) dan `marriages`.
4. **Throttle Protection:** Membungkus endpoint `api/image-proxy` dan `orders/checkout` dengan rate limiting.

### B. Long-Term Architecture (Skala Nasional / Ratusan Ribu Pohon)
Rancangan kondisi ideal untuk jangka panjang:

```mermaid
flowchart TD
    subgraph Client Layer
        Web["Web & Mobile Browser (PWA)"]
    end

    subgraph API & Gateway
        Nginx["Nginx / Caddy (SSL, HTTP/2, Static Cache)"]
        Web --> Nginx
    end

    subgraph Application Monolith (Modular Domains)
        Nginx --> AppCore["Laravel 13 Core Application"]
        AppCore --> DomainTree["Domain: Tree & Kinship Engine"]
        AppCore --> DomainArchive["Domain: Heritage Archive & Media"]
        AppCore --> DomainBilling["Domain: Subscriptions & Quota"]
        AppCore --> DomainCollab["Domain: Invitations & Approvals"]
    end

    subgraph Asynchronous Infrastructure
        AppCore --> RedisQueue["Redis Queue (Background Workers)"]
        RedisQueue --> JobExport["Job: Puppeteer High-Res Export"]
        RedisQueue --> JobTelegram["Job: Telegram Reminders Batch"]
        RedisQueue --> JobAI["Job: OCR & Bio Generation"]
    end

    subgraph Data & Storage
        AppCore --> MySQL[("Primary Relational Database\n(MySQL 8+ with Strict FKs)")]
        AppCore --> RedisCache[("Redis Cache\n(Tree Graph Caching with Tags)")]
        JobExport --> S3Storage[("Object Storage\n(Cloudflare R2 / AWS S3)")]
    end
```

---

## 2. Priority Matrix (P0 – P3)

| Kode | Kategori | Temuan / Fitur | Severity / Effort | Dampak & Urgensi |
|---|---|---|---|---|
| **P0-1** | Security | Perbaikan Cross-Tree IDOR pada `save()`, `deleteMember()`, dan `deleteMarriage()` di `⚡member-manager` | **Critical (P0) / S** | Mencegah pembajakan dan penghapusan data antar pengguna. |
| **P0-2** | Data Integrity | Validasi tanggal wafat (`death_date >= birth_date`) dan pencegahan duplikasi ayah=ibu | **Critical (P0) / S** | Menjaga keabsahan data silsilah keluarga. |
| **P1-1** | Performance | Migrasi properti `public $tree` ke `public int $treeId` dengan `#[Computed]` di `tree-vertical` | **High (P1) / M** | Memangkas ukuran payload Livewire hingga 90% pada pohon besar. |
| **P1-2** | Security | Penerapan middleware `throttle` pada `api/image-proxy` dan `orders/checkout` | **High (P1) / S** | Mencegah eksploitasi relay proxy dan spam transaksi. |
| **P1-3** | Database | Penambahan index komposit pada `members` (`family_tree_id`, `order`, `birth_date`) | **High (P1) / S** | Akselerasi query pohon, sorting anak, dan kalender haul. |
| **P1-4** | Product / UX | Kinship Path Finder visual ("Bagaimana saya terhubung dengan X?") | **High (P1) / M** | Fitur unggulan bernilai tinggi saat acara reuni keluarga. |
| **P2-1** | Maintainability | Refactoring dekomposisi `⚡member-manager.blade.php` (1.600 baris) menjadi 3 sub-komponen | **Medium (P2) / L** | Menurunkan risiko regresi dan mempermudah pengembangan fitur baru. |
| **P2-2** | UI/UX | Sentuhan visual bertema Heritage (warm earth palette, tipografi judul serif, badge generasi) | **Medium (P2) / M** | Memberikan diferensiasi estetika yang hangat dan berkelas. |
| **P2-3** | Product | Antrean persetujuan kontribusi silsilah (*Approval Queue*) untuk kerabat | **Medium (P2) / L** | Crowdsourcing data keluarga yang aman tanpa risiko data dirusak. |
| **P3-1** | Innovation | Ekspor/Impor file standar silsilah GEDCOM 5.5.1 / 7.0 | **Low (P3) / L** | Interoperabilitas dengan software silsilah internasional. |
| **P3-2** | AI Guardrail | Asisten penulisan riwayat hidup keluarga berbasis fakta tersimpan (*AI Biographer*) | **Low (P3) / M** | Nilai tambah emosional pelestarian sejarah keluarga. |

---

## 3. Implementation Roadmap (5 Fase Bertahap)

### Tahap 1: Critical Fixes & Security Hardening (P0) — *1–2 Hari Kerja*
- [ ] Terapkan scoped query `$tree->members()->findOrFail(...)` di seluruh method mutasi `⚡member-manager`.
- [ ] Terapkan validasi `death_date >= birth_date` dan `different:mother_id` pada input orang tua.
- [ ] Tambahkan rate limiting pada rute `api/image-proxy` dan `orders/checkout`.
- [ ] Tulis feature test regresi untuk memastikan IDOR dan validasi tanggal tertutup rapat.

### Tahap 2: Core Optimization & Performance (P1) — *2–3 Hari Kerja*
- [ ] Buat migration untuk composite index `members(family_tree_id, order)` dan `members(family_tree_id, birth_date)`.
- [ ] Refactor Livewire `tree-vertical` dan `tree-view`: ganti `public $tree` dengan `public int $treeId` + `#[Computed]`.
- [ ] Optimalkan alokasi memori `RelationshipService` dengan spesifik kolom `select(['id', 'family_tree_id', ...])`.

### Tahap 3: UI/UX & Kinship Experience Upgrade (P2) — *3–5 Hari Kerja*
- [ ] Implementasikan fitur Kinship Path Finder ("Bagaimana saya terhubung dengan X?").
- [ ] Perbarui tema desain dengan karakter *warm digital heritage* (palet hangat, tipografi berkelas, badge generasi).
- [ ] Dekomposisi `⚡member-manager.blade.php` menjadi komponen yang lebih modular.
- [ ] Sempurnakan tampilan mobile dock dan minimizer bar quest pada layar kecil.

### Tahap 4: Digital Archive & Family Collaboration (P3) — *1–2 Pekan*
- [ ] Implementasikan Lembar Riwayat Hidup (*Life Stories*) dan Geo-tagging Makam Leluhur.
- [ ] Kembangkan fitur Antrean Usulan Kontribusi (*Crowdsourced Contribution Queue*) dengan approval workflow.
- [ ] Dukungan ekspor poster silsilah resolusi tinggi (A3/A2/A0) untuk cetak spanduk reuni.

### Tahap 5: AI-Assisted Heritage & Interoperability (P3) — *2 Pekan*
- [ ] Integrasikan Parser & Exporter GEDCOM standar.
- [ ] Kembangkan AI Family Biographer dengan prompt anti-halusinasi yang terkunci pada fakta database.
- [ ] Evaluasi modul OCR dokumen lama (Buku Nikah / KK kuno).
