# Superadmin & Quota Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menghadirkan modul Superadmin terpadu untuk pemilik aplikasi Silsilah (UMKM SaaS) guna mengelola pengguna, memantau metrik bisnis (pengguna, pohon, pendapatan QRIS Duitku), mengelola transaksi, serta memberikan/menyesuaikan kuota pohon silsilah secara manual untuk penanganan bantuan pembayaran.

**Architecture:** Menambahkan kolom `role` (enum: `user`, `admin`) pada tabel `users`, middleware proteksi `AdminMiddleware`, layout navigasi admin berbasis Flux UI + Tailwind v4, serta komponen Livewire SFC untuk Dashboard Statistik, Manajemen Pengguna (dengan modal penyesuaian kuota), dan Manajemen Transaksi Duitku (dengan aksi konfirmasi manual).

**Tech Stack:** Laravel 13, Livewire 4 SFC, Flux UI v2, Tailwind CSS v4, PestPHP 4.

---

### File Structure Map

- **Database & Model:**
  - `database/migrations/2026_10_27_110000_add_role_to_users_table.php` (tambah kolom `role` string default `'user'`)
  - Modify `app/Models/User.php` (tambah helper `isAdmin()`, fillable `role`, scope `admin()`, dll.)
  - Modify `database/seeders/DatabaseSeeder.php` (pastikan ada akun admin awal)

- **Middleware & Security:**
  - `app/Http/Middleware/EnsureUserIsAdmin.php` (blokir akses non-admin dengan HTTP 403 Forbidden)
  - `bootstrap/app.php` (daftarkan middleware alias `admin`)

- **Routing & Views:**
  - Modify `routes/web.php` (kelompok route `/admin` dengan middleware `['auth', 'verified', 'admin']`)
  - `resources/views/layouts/admin.blade.php` (layout khusus superadmin dengan sidebar/navbar terpadu)
  - `resources/views/components/⚡admin-dashboard.blade.php` (overview KPI pendapatan, user, pohon)
  - `resources/views/components/⚡admin-users.blade.php` (manajemen user, search, modal edit kuota pohon)
  - `resources/views/components/⚡admin-orders.blade.php` (audit transaksi Duitku, filter status, aksi verifikasi manual)

- **Tests:**
  - `tests/Feature/AdminAccessTest.php` (tes otorisasi middleware & proteksi rute)
  - `tests/Feature/AdminUserQuotaManagementTest.php` (tes perubahan kuota manual oleh admin)
  - `tests/Feature/AdminOrderManagementTest.php` (tes manual confirm payment & pemberian kuota)

---

### Task 1: [SELESAI] Skema Database & Model User Role

**Files:**
- Create: `database/migrations/2026_10_27_105546_add_role_to_users_table.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/AdminAccessTest.php`

- [x] **Step 1: Write failing test for user role and `isAdmin()` helper**
- [x] **Step 2: Run test to verify it fails**
- [x] **Step 3: Create migration adding `role` column to `users`**
- [x] **Step 4: Update `User` model with `$fillable`, `isAdmin()`, and cast**
- [x] **Step 5: Run migration and verify test passes**
- [x] **Step 6: Format with Pint & Commit**

---

### Task 2: [SELESAI] Middleware & Otorisasi Admin

**Files:**
- Create: `app/Http/Middleware/EnsureUserIsAdmin.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AdminAccessTest.php`

- [x] **Step 1: Write test for `/admin` route authorization (403 for user, 200 for admin, redirect for guest)**
- [x] **Step 2: Run test to verify failure**
- [x] **Step 3: Implement `EnsureUserIsAdmin` middleware**
- [x] **Step 4: Register middleware alias `admin` in `bootstrap/app.php`**
- [x] **Step 5: Add protected route group `/admin` in `routes/web.php`**
- [x] **Step 6: Run tests and verify they pass**
- [x] **Step 7: Format with Pint & Commit**

---

### Task 3: [SELESAI] Layout Superadmin & Dashboard Metrik KPI

**Files:**
- Create: `resources/views/layouts/admin.blade.php`
- Create: `resources/views/components/⚡admin-dashboard.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AdminAccessTest.php`

- [x] **Step 1: Write test for admin dashboard rendering and KPI statistics**
- [x] **Step 2: Create `resources/views/layouts/admin.blade.php` with navigation and organic editorialism theme**
- [x] **Step 3: Implement Livewire component `⚡admin-dashboard`**
  - KPI Cards: Total Pendapatan QRIS (Rp), Total Pengguna, Total Pohon Keluarga, Total Jiwa/Anggota.
  - Recent Orders table & Recent Users list.
- [x] **Step 4: Run test and verify it passes**
- [x] **Step 5: Format with Pint & Commit**

---

### Task 4: [SELESAI] Manajemen Pengguna & Penyesuaian Kuota Manual

**Files:**
- Create: `resources/views/components/⚡admin-users.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AdminUserQuotaManagementTest.php`

- [x] **Step 1: Write failing test for manual tree quota adjustment**
- [x] **Step 2: Run test to verify failure**
- [x] **Step 3: Implement `⚡admin-users.blade.php`**
  - Daftar pengguna dengan pagination dan pencarian (nama, email).
  - Tampilan kuota saat ini (`tree_quota`), pohon yang dimiliki, dan status registrasi.
  - Modal "Atur Kuota Pohon": Input angka kuota baru atau quick button (+1 kuota, +2 kuota), serta input alasan (misal: "Kompensasi kendala pembayaran").
  - Flash notification keberhasilan.
- [x] **Step 4: Run test and verify it passes**
- [x] **Step 5: Format with Pint & Commit**

---

### Task 5: [SELESAI] Manajemen Transaksi & Verifikasi Manual Order Duitku

**Files:**
- Create: `resources/views/components/⚡admin-orders.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AdminOrderManagementTest.php`

- [x] **Step 1: Write failing test for manual order verification**
- [x] **Step 2: Run test to verify failure**
- [x] **Step 3: Implement `⚡admin-orders.blade.php`**
  - Tabel transaksi: `order_id`, Nama Pengguna, Nominal, Status badge (`paid`, `pending`, `expired`), Metode Pembayaran, Waktu.
  - Filter berdasarkan status (`all`, `paid`, `pending`, `expired`).
  - Aksi "Verifikasi Manual (Set Paid & Beri Kuota)":
    - Mengubah order pending menjadi `paid`.
    - Otomatis menambahkan `tree_quota` pengguna sebesar `quota_count` (+1).
    - Mencatat log penyesuaian.
- [x] **Step 4: Run test and verify it passes**
- [x] **Step 5: Format with Pint & Commit**

---

### Task 6: [SELESAI] Tautan Menu Superadmin di Header Pengguna

**Files:**
- Modify: `resources/views/layouts/app/header.blade.php`
- Modify: `resources/views/layouts/app/sidebar.blade.php`
- Test: `tests/Feature/AdminAccessTest.php`

- [x] **Step 1: Write test checking admin menu link visibility only for admin users**
- [x] **Step 2: Add "Panel Superadmin" item in user dropdown for users where `auth()->user()->isAdmin()`**
- [x] **Step 3: Run full test suite to guarantee 0 regressions**
- [x] **Step 4: Format with Pint & Final Commit**
