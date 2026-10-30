# Game-Like Interactive Onboarding Tour Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun fitur panduan/tutorial interaktif bergaya game (*quest-based interactive walkthrough*) untuk pengguna baru yang membimbing langkah demi langkah: membuat pohon pertama di dashboard, menambahkan anggota pertama (leluhur), menambahkan pasangan, dan menambahkan anak, lengkap dengan tombol lewati (*skip*) serta tombol ulangi panduan.

**Architecture:** Menggunakan komponen blade terisolasi berbasis Alpine.js (`<x-onboarding-tour />`) yang merender SVG spotlight mask gelap, pulsing halo ring di elemen target, floating quest tooltip popover dengan progress bar & badge quest RPG, dan `localStorage` persistence. Komponen disematkan pada Dashboard, Horizontal Tree Canvas, dan Vertical Tree View dengan target `data-tour` yang konsisten.

**Tech Stack:** Laravel 13, Livewire 4, Alpine.js, Tailwind CSS v4, Flux UI, Pest PHP.

---

### File Structure Map

| File | Tipe | Tanggung Jawab |
|---|---|---|
| `resources/views/components/onboarding-tour.blade.php` | Blade Component + Alpine.js | UI overlay spotlight, quest card, progress bar, multi-step game navigation, tombol Skip, dan auto-positioning logic. |
| `resources/views/components/⚡dashboard-trees.blade.php` | Livewire / Blade View | Menambahkan trigger tombol `🎓 Panduan` & atribut `data-tour="create-tree-btn"`. |
| `resources/views/livewire/tree-view.blade.php` | Livewire Component | Menyematkan `<x-onboarding-tour mode="canvas" />` dan memastikan tombol `🎓 Panduan` di dock/topbar canvas horizontal. |
| `resources/views/livewire/tree-vertical.blade.php` | Livewire Component | Menyematkan `<x-onboarding-tour mode="vertical" />` pada canvas vertikal. |
| `resources/views/components/tree-node.blade.php` | Blade Component | Menandai `data-tour="member-card"` dan `data-tour="add-rel-btn"` pada kartu anggota pertama. |
| `tests/Feature/OnboardingTourTest.php` | Pest Feature Test | Memverifikasi render tour component di Dashboard, Horizontal Canvas, dan Vertical Canvas beserta atribut `data-tour` dan event listener-nya. |

---

## Tasks

### Task 1: Buat Feature Test untuk Komponen Onboarding Tour

**Files:**
- Create: `tests/Feature/OnboardingTourTest.php`

- [ ] **Step 1: Tulis tes skenario Onboarding Tour**

```php
<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

test('dashboard view renders onboarding tour with create tree target', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee('x-data="onboardingTour', false);
    $response->assertSee('data-tour="create-tree-btn"', false);
    $response->assertSee('Lewati Tutorial');
});

test('empty tree canvas renders onboarding tour with first member guidance', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Keluarga Baru']);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    $response = $this->get(route('tree.show', $tree->id));
    $response->assertOk();
    $response->assertSee('x-data="onboardingTour', false);
    $response->assertSee('Misi 1: Anggota Pertama');
    $response->assertSee('Misi 2: Tambah Pasangan');
    $response->assertSee('Misi 3: Tambahkan Anak');
    $response->assertSee('Lewati Tutorial');
});

test('vertical tree canvas also includes onboarding tour', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Keluarga Vertikal']);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    $response = $this->get(route('tree.vertical', $tree->id));
    $response->assertOk();
    $response->assertSee('x-data="onboardingTour', false);
    $response->assertSee('Lewati Tutorial');
});
```

- [ ] **Step 2: Jalankan tes untuk memverifikasi kondisi awal**

Run: `php artisan test --compact tests/Feature/OnboardingTourTest.php`
Expected: Beberapa assertion gagal karena teks quest dan implementasi di vertical view belum disesuaikan.

- [ ] **Step 3: Commit tes awal**

```bash
git add tests/Feature/OnboardingTourTest.php
git commit -m "test: add feature tests for game-like onboarding tour"
```

---

### Task 2: Perbarui & Desain Game-Like UI di `onboarding-tour.blade.php`

**Files:**
- Modify: `resources/views/components/onboarding-tour.blade.php`

- [ ] **Step 1: Desain ulang state dan struktur quest Alpine.js**
Tambahkan langkah-langkah spesifik sesuai permintaan pengguna:
1. **Misi Dashboard**: Buat Pohon Baru.
2. **Misi 1 (Canvas)**: Tambahkan Sosok Leluhur / Anggota Pertama.
3. **Misi 2 (Canvas)**: Tambahkan Pasangan (Istri/Suami) beserta status pernikahannya.
4. **Misi 3 (Canvas)**: Tambahkan Anak (Keturunan) dan hubungkan kedua orang tuanya.
5. **Misi Selesai**: Perayaan game-like (*Level Up*) dengan tips eksplorasi (zoom, pan, ekspor).

Tambahkan elemen visual game:
- XP Progress Bar (`w-full bg-zinc-200 h-1.5 rounded-full overflow-hidden`).
- Quest Rank Badge: `Level 1: Pembuat Silsilah`.
- Tombol **"Lewati Tutorial"** yang selalu terlihat di kiri bawah dan ikon close `✕` di kanan atas.
- Tombol **"Kembali"** dan **"Lanjut ➔"** / **"Selesai! 🎉"**.
- Spotlight cutout SVG dengan transisi halus dan animasi pulsing halo ring hijau toska (`#10b981`).

- [ ] **Step 2: Jalankan tes lokal**

Run: `php artisan test --compact tests/Feature/OnboardingTourTest.php`

---

### Task 3: Lengkapi Atribut `data-tour` & Dukungan di Canvas Pohon (Horizontal & Vertikal)

**Files:**
- Modify: `resources/views/livewire/tree-view.blade.php`
- Modify: `resources/views/livewire/tree-vertical.blade.php`
- Modify: `resources/views/components/tree-node.blade.php`
- Modify: `resources/views/components/vertical-child-row.blade.php`

- [ ] **Step 1: Sisipkan `<x-onboarding-tour>` pada `tree-vertical.blade.php`**
Pastikan view vertikal juga memuat:
```blade
@if(!$isPublic)
    <x-onboarding-tour mode="vertical" :tree-id="$tree->id" :is-owner="true" />
@endif
```

- [ ] **Step 2: Pasang target `data-tour` pada elemen kartu anggota dan aksi tambah**
- Kartu anggota pertama: `data-tour="member-card"`
- Tombol tambah relasi di kartu: `data-tour="add-rel-btn"`
- Tombol tambah anggota di topbar/dock: `data-tour="add-member-top"`
- Empty state: `data-tour="empty-tree-cta"`

- [ ] **Step 3: Tambahkan tombol "🎓 Panduan" pada Dock Menu Canvas**
Agar pengguna yang sudah melewati tutorial bisa membukanya kembali kapan saja dengan 1 klik:
```blade
<flux:menu.item icon="academic-cap" @click="$dispatch('start-onboarding-tour')">
    Panduan Tutorial
</flux:menu.item>
```

---

### Task 4: Verifikasi & Uji Menyeluruh

**Files:**
- Tests: `tests/Feature/OnboardingTourTest.php`
- Tests: Seluruh test suite `php artisan test --compact`

- [ ] **Step 1: Jalankan tes Onboarding Tour**

Run: `php artisan test --compact tests/Feature/OnboardingTourTest.php`
Expected: 3 passed (semua skenario dashboard, horizontal, dan vertical tour lulus).

- [ ] **Step 2: Jalankan seluruh test suite aplikasi**

Run: `php artisan test --compact`
Expected: 282+ passed, 0 failed.

- [ ] **Step 3: Jalankan format code Laravel Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: pass.

- [ ] **Step 4: Commit dan Push ke Git Repository**

```bash
git add .
git commit -m "feat(tutorial): implement game-like onboarding quest tour with skip button"
git push origin main
```

- [ ] **Step 5: Deploy ke Remote Server via SSH**

Run: `ssh -o BatchMode=yes bpsdemak@10.133.21.24 "cd /home/bpsdemak/apps/muhshi/silsilah && bash deploy.sh"`
Expected: Build Docker sukses, container restart, status `200 OK`.
