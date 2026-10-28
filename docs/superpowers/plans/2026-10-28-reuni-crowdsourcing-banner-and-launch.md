# Rencana Implementasi: Banner Silsilah, Crowdsourcing Reuni, & Peluncuran Publik

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun fitur Banner Silsilah 1 Halaman Penuh (*Multi-Column Branch Layout*), Formulir Usulan Data Mandiri bagi Kerabat (*Crowdsourcing Reuni*), PWA (*Install ke Layar Utama HP*), serta *Landing Page & OpenGraph Social Card Showcase* untuk peluncuran publik.

**Architecture:** 
- *Multi-Column Branch Layout*: `TreeBannerExportService` mengelompokkan keturunan per cabang anak (Gen 2) ke dalam kolom-kolom vertikal terorganisir untuk memaksimalkan rasio bidang banner (16:9 / 3:1 / A1) dengan font yang tetap besar dan terbaca.
- *Crowdsourcing Reuni*: Entitas `TreeSuggestion` dengan token akses publik per pohon. Kerabat dapat mengajukan penambahan anak/pasangan atau koreksi tanggal secara mandiri; pengelola pohon dapat meninjau dengan 1-klik *Approve / Reject*.
- *PWA & Social Cards*: Web App Manifest, Service Worker offline fallback, dan meta tag OpenGraph dinamis yang menampilkan preview kartu silsilah keluarga saat link dibagikan di WhatsApp.

**Tech Stack:** Laravel 13, Livewire 4, Flux UI, Pest 4, Tailwind CSS v4, Browsershot / CSS Paged Media.

---

### Task 1: Engine Layout Banner 1 Halaman (`TreeBannerExportService.php`) & Template Cetak

**Files:**
- Create: `app/Services/TreeBannerExportService.php`
- Create: `resources/views/tree/export-banner.blade.php`
- Modify: `app/Http/Controllers/TreeExportController.php`
- Modify: `routes/web.php`
- Test: `tests/Unit/TreeBannerExportServiceTest.php`
- Test: `tests/Feature/TreeBannerExportTest.php`

- [ ] **Step 1: Tulis unit test untuk struktur cabang kolom banner**

```php
// tests/Unit/TreeBannerExportServiceTest.php
<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Services\TreeBannerExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('groups members into root ancestor and generational child columns', function () {
    $tree = FamilyTree::factory()->create(['name' => 'Bani Mochtar']);

    // Root Ancestor (Gen 1)
    $root = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Mochtar',
        'gender' => 'male',
    ]);

    // Children (Gen 2)
    $child1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ahmad',
        'father_id' => $root->id,
        'order' => 1,
    ]);

    $child2 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Budi',
        'father_id' => $root->id,
        'order' => 2,
    ]);

    // Grandchildren (Gen 3)
    $grandchild1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Citra',
        'father_id' => $child1->id,
        'order' => 1,
    ]);

    $service = new TreeBannerExportService();
    $bannerData = $service->prepareBannerData($tree);

    expect($bannerData)->toHaveKeys(['root', 'columns', 'total_members', 'stats'])
        ->and($bannerData['root']->id)->toBe($root->id)
        ->and($bannerData['columns'])->toHaveCount(2)
        ->and($bannerData['columns'][0]['child']->id)->toBe($child1->id)
        ->and($bannerData['columns'][0]['descendants'])->toHaveCount(1)
        ->and($bannerData['columns'][1]['child']->id)->toBe($child2->id)
        ->and($bannerData['columns'][1]['descendants'])->toHaveCount(0);
});
```

- [ ] **Step 2: Jalankan test untuk memastikan test gagal**

Run: `php artisan test --compact tests/Unit/TreeBannerExportServiceTest.php`
Expected: FAIL (Class `TreeBannerExportService` not found)

- [ ] **Step 3: Implementasikan minimal code `TreeBannerExportService.php`**

```php
// app/Services/TreeBannerExportService.php
<?php

namespace App\Services;

use App\Models\FamilyTree;
use App\Models\Member;
use Illuminate\Support\Collection;

class TreeBannerExportService
{
    /**
     * Prepare structured multi-column data for 1-page banner layout.
     *
     * @return array{
     *     root: Member|null,
     *     root_spouse: Member|null,
     *     columns: array<int, array{
     *         child: Member,
     *         spouses: Collection<int, Member>,
     *         descendants: Collection<int, array{member: Member, spouses: Collection<int, Member>, depth: int}>
     *     }>,
     *     total_members: int,
     *     stats: array{generations: int, living: int, deceased: int}
     * }
     */
    public function prepareBannerData(FamilyTree $tree): array
    {
        $allMembers = $tree->members()->with(['marriagesAsHusband.wife', 'marriagesAsWife.husband'])->get()->keyBy('id');

        // 1. Identify Root Ancestor (Gen 1 with no parents in tree)
        $root = $allMembers->filter(fn ($m) => empty($m->father_id) && empty($m->mother_id))->first();
        
        $rootSpouse = null;
        if ($root) {
            $spouseRel = $root->marriagesAsHusband->first()?->wife ?? $root->marriagesAsWife->first()?->husband;
            $rootSpouse = $spouseRel ? $allMembers->get($spouseRel->id) : null;
        }

        // 2. Identify Children of Root (Gen 2)
        $children = $root ? $allMembers->filter(fn ($m) => $m->father_id === $root->id || $m->mother_id === $root->id)
            ->sortBy(fn ($m) => [$m->order ?? 999, $m->birth_date ?? '9999-99-99', $m->id])
            ->values() : collect();

        // 3. For each child, compile descendants branch
        $columns = [];
        foreach ($children as $child) {
            $childSpouses = $child->marriagesAsHusband->map(fn ($m) => $m->wife)->filter()
                ->concat($child->marriagesAsWife->map(fn ($m) => $m->husband)->filter());

            $descendants = collect();
            $this->collectDescendants($child, $allMembers, $descendants, 1);

            $columns[] = [
                'child' => $child,
                'spouses' => $childSpouses,
                'descendants' => $descendants,
            ];
        }

        return [
            'root' => $root,
            'root_spouse' => $rootSpouse,
            'columns' => $columns,
            'total_members' => $allMembers->count(),
            'stats' => [
                'generations' => count($columns) > 0 ? 3 : 1,
                'living' => $allMembers->where('is_living', true)->count(),
                'deceased' => $allMembers->where('is_living', false)->count(),
            ],
        ];
    }

    /**
     * @param Collection<int, array{member: Member, spouses: Collection<int, Member>, depth: int}> $collector
     */
    protected function collectDescendants(Member $parent, Collection $allMembers, Collection &$collector, int $depth): void
    {
        $children = $allMembers->filter(fn ($m) => $m->father_id === $parent->id || $m->mother_id === $parent->id)
            ->sortBy(fn ($m) => [$m->order ?? 999, $m->birth_date ?? '9999-99-99', $m->id]);

        foreach ($children as $child) {
            $spouses = $child->marriagesAsHusband->map(fn ($m) => $m->wife)->filter()
                ->concat($child->marriagesAsWife->map(fn ($m) => $m->husband)->filter());

            $collector->push([
                'member' => $child,
                'spouses' => $spouses,
                'depth' => $depth,
            ]);

            $this->collectDescendants($child, $allMembers, $collector, $depth + 1);
        }
    }
}
```

- [ ] **Step 4: Buat view `resources/views/tree/export-banner.blade.php`**
Template banner dengan layout grid multi-kolom proporsional, border ornamen klasik keluarga, warna kartu per generasi, dan dukungan cetak landscape ukuran poster (A1 / A0 / Banner 2x1 meter).

- [ ] **Step 5: Hubungkan ke `TreeExportController.php` dan `routes/web.php`**
Tambahkan opsi format `'banner'` pada endpoint ekspor: `route('tree.export', ['tree' => $tree, 'format' => 'banner'])`.

- [ ] **Step 6: Jalankan unit & feature test, lalu format dengan Pint**
Run: `vendor/bin/pint --dirty --format agent && php artisan test --compact tests/Unit/TreeBannerExportServiceTest.php tests/Feature/TreeBannerExportTest.php`

- [ ] **Step 7: Commit Git**
Commit: `feat(banner): implement multi-column single-page banner export engine`

---

### Task 2: Crowdsourcing Reuni - Usulan Perubahan Data Mandiri (`TreeSuggestion`)

**Files:**
- Create: `database/migrations/YYYY_MM_DD_create_tree_suggestions_table.php`
- Create: `app/Models/TreeSuggestion.php`
- Create: `resources/views/components/⚡tree-contribute-modal.blade.php`
- Modify: `resources/views/components/⚡tree-settings.blade.php`
- Test: `tests/Feature/TreeSuggestionTest.php`

- [ ] **Step 1: Tulis feature test untuk alur usulan data anggota keluarga**

```php
// tests/Feature/TreeSuggestionTest.php
test('family member can submit a new child or correction without login', function () {
    $tree = FamilyTree::factory()->create(['is_public' => true, 'slug' => 'bani-salim']);
    $parent = Member::factory()->create(['family_tree_id' => $tree->id, 'first_name' => 'Fauzi']);

    $component = Livewire::test('tree-contribute-modal', ['treeId' => $tree->id])
        ->set('targetMemberId', $parent->id)
        ->set('suggestionType', 'add_child')
        ->set('firstName', 'Ahmad')
        ->set('gender', 'male')
        ->set('contributorName', 'Budi Santoso (Sepupu)')
        ->set('contributorContact', '08123456789')
        ->set('notes', 'Lahir bulan lalu')
        ->call('submitSuggestion');

    $component->assertHasNoErrors();

    $this->assertDatabaseHas('tree_suggestions', [
        'family_tree_id' => $tree->id,
        'target_member_id' => $parent->id,
        'type' => 'add_child',
        'status' => 'pending',
        'contributor_name' => 'Budi Santoso (Sepupu)',
    ]);
});

test('tree owner can approve suggestion and member is automatically created', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);
    $parent = Member::factory()->create(['family_tree_id' => $tree->id, 'first_name' => 'Fauzi']);

    $suggestion = TreeSuggestion::create([
        'family_tree_id' => $tree->id,
        'target_member_id' => $parent->id,
        'type' => 'add_child',
        'data' => ['first_name' => 'Ahmad', 'gender' => 'male'],
        'contributor_name' => 'Budi',
        'status' => 'pending',
    ]);

    $this->actingAs($owner);
    // Approve action creates member
    app(TreeSuggestionService::class)->approve($suggestion);

    expect($suggestion->fresh()->status)->toBe('approved');
    $this->assertDatabaseHas('members', [
        'family_tree_id' => $tree->id,
        'first_name' => 'Ahmad',
        'father_id' => $parent->id,
    ]);
});
```

- [ ] **Step 2: Jalankan test dan pastikan gagal**
Run: `php artisan test --compact tests/Feature/TreeSuggestionTest.php`

- [ ] **Step 3: Buat migrasi & model `TreeSuggestion`**
Fields: `id`, `family_tree_id`, `target_member_id` (nullable), `type` (add_child, add_spouse, update_info), `data` (json), `contributor_name`, `contributor_contact`, `notes`, `status` (pending, approved, rejected), `reviewed_by`, `reviewed_at`.

- [ ] **Step 4: Buat komponen Livewire `⚡tree-contribute-modal.blade.php`**
Form modal yang bisa dibuka dari tombol "Usulkan Koreksi / Tambah Data" di menu publik pohon keluarga.

- [ ] **Step 5: Integrasikan tab "Kotak Masuk Usulan Data" di `⚡tree-settings.blade.php`**
Tampilkan daftar usulan yang masuk dengan badge "Pending", serta tombol aksi 1-klik: **"Terima & Terapkan ke Pohon"** atau **"Tolak"**.

- [ ] **Step 6: Jalankan test dan format code**
Run: `vendor/bin/pint --dirty --format agent && php artisan test --compact tests/Feature/TreeSuggestionTest.php`

- [ ] **Step 7: Commit Git**
Commit: `feat(crowdsourcing): add tree suggestion system for family reunions`

---

### Task 3: PWA (Install ke Layar Utama HP) & OpenGraph WhatsApp Preview Card

**Files:**
- Create: `public/manifest.json`
- Create: `public/sw.js`
- Modify: `resources/views/tree/public.blade.php`
- Modify: `resources/views/layouts/app.blade.php`
- Test: `tests/Feature/PwaAndMetaTagsTest.php`

- [ ] **Step 1: Tulis feature test untuk OpenGraph meta tag dinamis dan manifest**

```php
// tests/Feature/PwaAndMetaTagsTest.php
test('public tree renders dynamic OpenGraph meta tags for WhatsApp previews', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Bani Mochtar',
        'is_public' => true,
        'slug' => 'bani-mochtar',
        'description' => 'Silsilah keturunan H. Mochtar Demak',
    ]);

    $response = $this->get(route('tree.public', $tree->slug));

    $response->assertOk();
    $response->assertSee('<meta property="og:title" content="Bani Mochtar — Silsilah Keluarga"', false);
    $response->assertSee('<meta property="og:description"', false);
    $response->assertSee('manifest.json', false);
});
```

- [ ] **Step 2: Jalankan test untuk memverifikasi kegagalan**
Run: `php artisan test --compact tests/Feature/PwaAndMetaTagsTest.php`

- [ ] **Step 3: Buat `public/manifest.json` & `public/sw.js`**
Manifest dengan nama "Silsilah Keluarga", icons, tema warna hijau zamrud (`#059669`), display `standalone`, dan background `#ffffff`.

- [ ] **Step 4: Pasang meta OpenGraph & Twitter Card pada `tree/public.blade.php`**
Menampilkan thumbnail, judul pohon keluarga yang ramah, dan deskripsi generasi saat link dibagikan di WhatsApp.

- [ ] **Step 5: Jalankan test, format code, dan commit**
Run: `vendor/bin/pint --dirty --format agent && php artisan test --compact tests/Feature/PwaAndMetaTagsTest.php`
Commit: `feat(pwa): add progressive web app manifest and dynamic opengraph cards for whatsapp`

---

### Task 4: Pembaruan Landing Page Utama & Demo Interaktif Publik

**Files:**
- Modify: `resources/views/welcome.blade.php`
- Test: `tests/Feature/LandingPageTest.php`

- [ ] **Step 1: Tulis feature test untuk section baru di landing page**

```php
// tests/Feature/LandingPageTest.php
test('landing page displays showcase of kinship calculator, reminders, and interactive demo link', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Kalkulator Nasab');
    $response->assertSee('Pengingat Haul & Ulang Tahun');
    $response->assertSee('Buku Silsilah');
    $response->assertSee('Coba Demo Langsung');
});
```

- [ ] **Step 2: Jalankan test untuk memverifikasi kegagalan**
Run: `php artisan test --compact tests/Feature/LandingPageTest.php`

- [ ] **Step 3: Update `resources/views/welcome.blade.php`**
Tambahkan badge interaktif, kartu fitur (Kalkulator Nasab, Pengingat Haul & Ultah WA, Buku PDF Generasi, Banner Cetak 1 Halaman), dan CTA *"Coba Demo Langsung"* yang mengarah ke `/public/tree/{slug_demo}`.

- [ ] **Step 4: Jalankan test dan format code**
Run: `vendor/bin/pint --dirty --format agent && php artisan test --compact tests/Feature/LandingPageTest.php`

- [ ] **Step 5: Commit Git**
Commit: `feat(landing): refresh landing page with feature showcase and interactive demo`

---

### Task 5: Verifikasi End-to-End & Deployment ke Server Produksi

- [ ] **Step 1: Jalankan pemeriksaan format kode Pint secara menyeluruh**
Run: `vendor/bin/pint --test --format agent`

- [ ] **Step 2: Jalankan seluruh test suite aplikasi**
Run: `php artisan test --compact` (Target: 230+ tests lulus, 0 errors)

- [ ] **Step 3: Push commit ke GitHub `main`**
Run: `git push origin main`

- [ ] **Step 4: Deploy ke server produksi (`bpsdemak@10.133.21.24`)**
Jalankan migrasi database di container Docker dan bersihkan cache:
```bash
ssh bpsdemak@10.133.21.24 "cd /home/bpsdemak/apps/muhshi/silsilah && git pull origin main && docker cp app silsilah-franken:/app/ && docker cp resources silsilah-franken:/app/ && docker cp routes silsilah-franken:/app/ && docker cp database silsilah-franken:/app/ && docker cp public silsilah-franken:/app/ && docker exec silsilah-franken php artisan migrate --force && docker exec silsilah-franken php artisan route:clear && docker exec silsilah-franken php artisan view:clear"
```

- [ ] **Step 5: Smoke test fitur di live server**
Verifikasi live ekspor banner, formulir usulan data, manifest PWA, dan landing page demo.
