# Silsilah Platform Enhancement & Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menuntaskan pembersihan arsitektur, penguatan keamanan SSRF, validasi anti-siklus hubungan keluarga, halaman legalitas/pricing, penyempurnaan impor data relasi, dan scheduled maintenance untuk aplikasi Silsilah Keluarga.

**Architecture:** Menerapkan Service Layer Pattern (`app/Services/`) untuk memecah `routes/web.php` yang gemuk, menambahkan custom validator anti-siklus pada model `Member`, hardening proxy image terhadap SSRF, serta melengkapi landing page dengan kartu harga dan syarat legalitas (Privacy, Terms, Refund) untuk merchant approval.

**Tech Stack:** Laravel 13, Livewire 4 SFC, Flux UI v2, Tailwind CSS v4, PestPHP 4, Spatie Browsershot, Duitku Payment Gateway.

---

### Task 1: [SELESAI] Monetisasi & Model Kuota SaaS via Duitku QRIS

**Files:**
- Created: `database/migrations/2026_10_27_085427_add_tree_quota_to_users_table.php`
- Created: `database/migrations/2026_10_27_085431_create_orders_table.php`
- Created: `app/Models/Order.php`
- Created: `app/Services/DuitkuService.php`
- Created: `app/Http/Controllers/DuitkuCallbackController.php`
- Created: `app/Http/Controllers/DuitkuCheckoutController.php`
- Modified: `app/Models/User.php`
- Modified: `bootstrap/app.php` (CSRF exemption for callback)
- Modified: `config/services.php` & `.env` / `.env.example`
- Modified: `resources/views/components/⚡dashboard-trees.blade.php`
- Test: `tests/Feature/DuitkuPaymentTest.php`

- [x] **Step 1: Migration `tree_quota` pada `users` dan pembuatan tabel `orders`**
- [x] **Step 2: Model `Order` dan relasi kuota pada model `User`**
- [x] **Step 3: Service `DuitkuService` (API v2 Create Invoice HMAC-SHA256 & Callback Verification)**
- [x] **Step 4: Controller Checkout & Callback Webhook Duitku**
- [x] **Step 5: Integrasi UI Modal Beli Kuota (Rp 10.000 via QRIS) pada Dashboard Trees**
- [x] **Step 6: Pest feature test passing 100% (4 tests passed)**

---

### Task 2: [SELESAI] Refactoring `routes/web.php` ke Service Layer & Dedicated Controllers

**Files:**
- Created: `app/Services/TreeExportService.php`
- Created: `app/Http/Controllers/TreeExportController.php`
- Modified: `routes/web.php` (dipangkas dari 528 baris menjadi 135 baris)
- Test: `tests/Feature/TreeExportTest.php`

- [x] **Step 1: Write feature tests for Tree Export (PDF, PNG, JSON, Prompt AI)**
- [x] **Step 2: Run test to verify initial state**
- [x] **Step 3: Create `TreeExportService`**
- [x] **Step 4: Create `TreeExportController`**
- [x] **Step 5: Rampingkan `routes/web.php`**
- [x] **Step 6: Run tests and verify (65 tests passing)**
- [x] **Step 7: Format with Pint & Commit**

---

### Task 3: [SELESAI] Hardening Keamanan `/api/image-proxy` (Pencegahan SSRF)

**Files:**
- Created: `app/Http/Controllers/ImageProxyController.php`
- Modified: `routes/web.php`
- Test: `tests/Feature/ImageProxySecurityTest.php`

- [x] **Step 1: Write failing security tests for image proxy**
- [x] **Step 2: Run test to verify it fails on private IPs**
- [x] **Step 3: Implement `ImageProxyController` with IP validation**
- [x] **Step 4: Update route in `routes/web.php`**
- [x] **Step 5: Run tests and verify (10 tests passed)**
- [x] **Step 6: Format & Commit**

---

### Task 4: [SELESAI] Validasi Logika Kekerabatan Anti-Siklus (`isDescendantOf`)

**Files:**
- Modified: `app/Models/Member.php` (`getAllDescendantIds()`, `isDescendantOf()`)
- Modified: `resources/views/components/⚡member-manager.blade.php` (dropdown filter & validation guard)
- Test: `tests/Feature/MemberRelationshipCycleTest.php`

- [x] **Step 1: Write failing test for ancestry cycle validation**
- [x] **Step 2: Add descendant traversal to `Member.php`**
- [x] **Step 3: Filter `availableMembers` di `⚡member-manager.blade.php`**
- [x] **Step 4: Run tests and verify (3 tests passed)**
- [x] **Step 5: Format & Commit**

---

### Task 5: [SELESAI] Halaman Legalitas (Privacy, Terms, Refund) & Pricing Landing Page

**Files:**
- Created: `resources/views/pages/legal/privacy.blade.php`
- Created: `resources/views/pages/legal/terms.blade.php`
- Created: `resources/views/pages/legal/refund.blade.php`
- Modified: `routes/web.php`
- Modified: `resources/views/welcome.blade.php`
- Test: `tests/Feature/LegalPagesTest.php`

- [x] **Step 1: Write test to verify legal pages return HTTP 200**
- [x] **Step 2: Create legal blade views (Privacy, Terms, Refund)**
- [x] **Step 3: Add routes in `routes/web.php`**
- [x] **Step 4: Update `welcome.blade.php` pricing card & footer links**
- [x] **Step 5: Run tests and verify (4 tests passed)**
- [x] **Step 6: Format & Commit**

---

### Task 6: [SELESAI] Peningkatan Fitur Impor Excel dengan Relasi Kekerabatan

**Files:**
- Modify: `resources/views/components/⚡import-members.blade.php`
- Test: `tests/Feature/MemberImportWithRelationsTest.php`

- [x] **Step 1: Write test for importing spreadsheet with parent relationships**
- [x] **Step 2: Update template Excel download**
  Tambahkan kolom:
  - `No Urut (1, 2, 3...)`
  - `Nama Depan`
  - `Nama Belakang`
  - `Jenis Kelamin (L/P)`
  - `Status (Hidup/Wafat)`
  - `Tanggal Lahir (YYYY-MM-DD)`
  - `No Urut Ayah`
  - `No Urut Ibu`
- [x] **Step 3: Implement 2-pass import in Livewire component**
  - Pass 1: Buat semua model Member dan simpan peta `row_index => member_id`.
  - Pass 2: Kaitkan `father_id` dan `mother_id` berdasarkan pemetaan baris, serta otomatis buat relasi pernikahan (Marriage).
- [x] **Step 4: Run tests and verify (2 tests passed, 8 assertions)**
- [x] **Step 5: Format & Commit**

---

### Task 7: [SELESAI] Scheduled Maintenance Tasks di `routes/console.php`

**Files:**
- Modify: `routes/console.php`
- Test: `tests/Feature/ScheduledTasksTest.php`

- [x] **Step 1: Write test for cleanup command**
- [x] **Step 2: Add scheduled commands in `routes/console.php`**
  - Artisan command `silsilah:cleanup`
  - Bersihkan undangan kedaluwarsa:
    `TreeInvitation::where('created_at', '<', now()->subDays(30))->delete()`
  - Bersihkan pending orders kedaluwarsa:
    `Order::where('status', 'pending')->where('created_at', '<', now()->subHours(24))->update(['status' => 'expired'])`
  - Dijadwalkan `Schedule::command('silsilah:cleanup')->daily()`
- [x] **Step 3: Run test and verify (1 test passed, 6 assertions)**
- [x] **Step 4: Format & Commit**
