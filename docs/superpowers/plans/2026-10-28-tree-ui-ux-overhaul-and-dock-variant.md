# Tree UI/UX Overhaul & Floating Dock Variant Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Overhaul the entire UI/UX of Silsilah across both PC (desktop) and mobile (HP): implementing Option 1 (Full-Bleed Infinite Canvas + Unified Navigation Tabs + Restructured Actions) as the primary experience, building Option 2 (Floating Pill Toolbar / Apple Freeform Dock) on a dedicated route `/tree/{tree}/dock`, and fixing all mobile proportions and missing action buttons.

**Architecture:** 
1. **Desktop Opsi 1 (Primary)**: Convert `tree-view` canvas to a full-bleed edge-to-edge workspace (eliminating awkward rounded outer corners and side margins). Create a clean 3-part header: Left (Breadcrumb `Dashboard / Tree Name` + Badges), Center (Primary View Tabs: Bagan Canvas, Fokus Explorer, Buku Simpel, Kalender Acara), and Right (Bagikan, Ekspor 6-format dropdown, Kelola & Kekerabatan dropdown, and + Tambah Anggota).
2. **Desktop Opsi 2 (Variant)**: Add a dedicated route `/tree/{tree}/dock` and `/public/tree/{slug}/dock` with a floating glassmorphic dock island (`rounded-full shadow-2xl backdrop-blur-xl`) floating top-center over a 100vh full-screen canvas. Include a toggle in the options menu to switch between Standard (Opsi 1) and Dock (Opsi 2).
3. **Mobile (HP) Overhaul**: Fix dashboard proportions (compact hero title, 2-column action buttons, 3-column compact metric stat cards in a single row above the fold). Restore all missing action buttons in tree views (Share, Export, Calendar, Settings, Relationship, Import) on mobile with a compact header bar and 5-target bottom navigation.
4. **Calendar Polish**: Micro event dot indicators on mobile calendar grid (`< sm`) preventing date cell cramping while preserving full detail on click.

**Tech Stack:** Laravel 13, Livewire 4, Flux UI, Alpine.js, Tailwind CSS v4, Pest 4, Pint.

---

### Task 1: Dashboard Mobile & PC Proportions Optimization

**Files:**
- Modify: `resources/views/components/⚡dashboard-trees.blade.php`
- Create: `tests/Feature/DashboardResponsiveOverhaulTest.php`

- [ ] **Step 1: Write failing test for responsive dashboard layout**

Create `tests/Feature/DashboardResponsiveOverhaulTest.php`:
```php
<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

test('dashboard renders responsive metrics, action buttons, and tree cards properly', function () {
    $user = User::factory()->create(['tree_quota' => 5]);
    $tree1 = FamilyTree::factory()->create(['name' => 'Keluarga Ahmad', 'is_public' => true, 'is_premium' => true]);
    $tree2 = FamilyTree::factory()->create(['name' => 'Keluarga Budi', 'is_public' => false, 'is_premium' => false]);
    $tree1->users()->attach($user->id, ['role' => 'owner']);
    $tree2->users()->attach($user->id, ['role' => 'owner']);

    Member::factory()->count(10)->create(['family_tree_id' => $tree1->id]);
    Member::factory()->count(8)->create(['family_tree_id' => $tree2->id]);

    $this->actingAs($user);

    $component = Livewire::test('dashboard-trees')
        ->assertSee('Silsilah Keluarga')
        ->assertSee('Buat Silsilah')
        ->assertSee('Beli Kuota')
        ->assertSee('Total Silsilah')
        ->assertSee('Total Anggota')
        ->assertSee('Pohon Premium')
        ->assertSee('18')
        ->assertSee('Keluarga Ahmad')
        ->assertSee('Keluarga Budi');

    $html = $component->html();
    
    // Assert 3-column metric grid exists on mobile
    expect($html)->toContain('grid-cols-3');
    // Assert action buttons have 2-column mobile layout
    expect($html)->toContain('grid-cols-2');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DashboardResponsiveOverhaulTest`
Expected: FAIL.

- [ ] **Step 3: Update `resources/views/components/⚡dashboard-trees.blade.php`**

Update `resources/views/components/⚡dashboard-trees.blade.php`:
1. **Hero Section (lines ~204-232)**:
   - Make title responsive: `text-2xl sm:text-4xl lg:text-5xl font-headline font-extrabold text-zinc-900 dark:text-white tracking-tight leading-tight`.
   - Subtitle: `text-xs sm:text-base text-zinc-600 dark:text-zinc-400 mt-1 sm:mt-2 leading-relaxed`.
   - Buttons: `grid grid-cols-2 sm:flex sm:items-center gap-2.5 sm:gap-3 w-full sm:w-auto`.
   - Primary button: `Buat Silsilah` with icon `add`.
   - Secondary button: `Beli Kuota` with icon `shopping_bag`.
2. **Stat Metric Cards (lines ~234-278)**:
   - Change container to `grid grid-cols-3 gap-2 sm:gap-5`.
   - Card padding: `p-3 sm:p-5 lg:p-6 rounded-2xl sm:rounded-3xl`.
   - Label: `text-[10px] sm:text-xs font-bold uppercase tracking-wider`.
   - Number: `text-xl sm:text-3xl lg:text-4xl font-extrabold tabular-nums`.
   - Mobile helper: `text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate`.
   - Desktop icon: `hidden sm:flex w-10 h-10 lg:w-14 lg:h-14 rounded-2xl` with subtle shadow.
3. **Tree Cards (lines ~359-450)**:
   - Card padding: `p-4 sm:p-6 rounded-2xl sm:rounded-3xl`.
   - Replace smartphone icon button with a clean view button or tooltip labeled "Mode Eksplorasi Fokus".

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=DashboardResponsiveOverhaulTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/⚡dashboard-trees.blade.php tests/Feature/DashboardResponsiveOverhaulTest.php
git commit -m "feat(dashboard): optimize responsive mobile and desktop proportions"
```

---

### Task 2: Full-Bleed Infinite Canvas (Opsi 1) in `tree-view.blade.php`

**Files:**
- Modify: `resources/css/tree.css`
- Modify: `resources/views/livewire/tree-view.blade.php`
- Modify: `tests/Feature/MobileTreeFeaturesTest.php`

- [ ] **Step 1: Write test for full-bleed canvas and unified desktop navigation in `tests/Feature/MobileTreeFeaturesTest.php`**

Add test in `tests/Feature/MobileTreeFeaturesTest.php`:
```php
it('renders full-bleed canvas with unified tabs and utility actions on tree-view', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create([
        'name' => 'Bani Wahid',
        'slug' => 'bani-wahid',
        'is_premium' => true,
    ]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    $component = Livewire::test('tree-view', ['id' => $tree->id]);

    $component->assertSee('Bani Wahid')
        ->assertSee('Bagan Canvas')
        ->assertSee('Fokus Explorer')
        ->assertSee('Buku Simpel')
        ->assertSee('Kalender Acara')
        ->assertSee('Bagikan')
        ->assertSee('Ekspor')
        ->assertSee('Poster Silsilah Pigura (A1/A0)')
        ->assertSee('Buku Silsilah (PDF)')
        ->assertSee('Anggota Baru');
});
```

- [ ] **Step 2: Run test to verify it fails or needs updates**

Run: `php artisan test --filter=MobileTreeFeaturesTest`

- [ ] **Step 3: Update `resources/css/tree.css` for full-bleed canvas**

In `resources/css/tree.css`:
Add utility class for full-bleed mode:
```css
.pt-sm.pt-full-bleed {
    border-radius: 0 !important;
    border-left: none !important;
    border-right: none !important;
    border-bottom: none !important;
    height: calc(100vh - 65px) !important;
    min-height: calc(100vh - 65px) !important;
}

@media (max-width: 768px) {
    .pt-sm.pt-full-bleed {
        height: calc(100vh - 120px) !important;
        min-height: calc(100vh - 120px) !important;
    }
}
```

- [ ] **Step 4: Redesign header and layout in `resources/views/livewire/tree-view.blade.php`**

Edit `resources/views/livewire/tree-view.blade.php`:
1. In the outer wrapper: Remove `px-4 lg:px-8 pb-20 md:pb-8` around the `.pt-sm` canvas container, applying `w-full h-[calc(100vh-65px)] overflow-hidden relative`.
2. Header Bar redesign (lines ~105-294):
   - **Left**: Breadcrumb link (`Dashboard / {{ $tree->name }}`), Privacy badge (`Publik`/`Privat`), and Premium badge (`👑 Premium`).
   - **Center (Desktop `hidden lg:flex`)**: Navigation Tabs:
     - `[🌳 Bagan Canvas]` (Active highlight: `bg-white dark:bg-zinc-800 text-emerald-700 dark:text-emerald-300 shadow-xs border border-zinc-200/80 dark:border-zinc-700/80 font-bold`)
     - `[🧭 Fokus Explorer]` (Link to `route('tree.vertical', $tree)`)
     - `[📄 Buku Simpel]` (Link to `route('tree.simple', $tree)`)
     - `[🗓️ Kalender Acara]` (Link to `route('tree.calendar', $tree->slug ?: $tree->id)`)
   - **Right (Desktop `hidden md:flex`)**:
     - `Bagikan` button (with clipboard toast)
     - `Ekspor` dropdown with all 6 formats (Poster Pigura A1/A0, Buku Silsilah PDF, Gambar PNG, Bagan Canvas PDF, AI Prompt .md, Data JSON)
     - `Kelola ▾` dropdown (Cek Hubungan Kekerabatan, Kelola Pohon & Statistik, Import CSV/GEDCOM, and link to Coba Tampilan Dock Opsi 2: `route('tree.dock', $tree)`)
     - `+ Anggota Baru` (Primary CTA green button)
   - **Mobile Action Bar (`flex md:hidden items-center gap-1.5`)**:
     - Share icon button (`navigator.share` support)
     - Export icon button (dropdown with 6 formats)
     - Calendar icon button (opens modal)
     - More options dropdown (`⋮` with Cek Hubungan, Kelola Pohon, Import, Buku Simpel, Tampilan Dock)
3. Bottom Navigation Bar on Mobile:
   - 5 balanced targets:
     1. 🧭 `Fokus`
     2. 🌳 `Bagan` (active)
     3. 👥 `Direktori`
     4. 🗓️ `Kalender`
     5. ➕ `Tambah` (or ✏️ `Usul` if public)

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=MobileTreeFeaturesTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/css/tree.css resources/views/livewire/tree-view.blade.php tests/Feature/MobileTreeFeaturesTest.php
git commit -m "feat(tree-view): implement full-bleed canvas, unified tabs, and mobile toolbar"
```

---

### Task 3: Restoring Features & Unifying Navigation in `tree-vertical.blade.php` (Fokus Explorer)

**Files:**
- Modify: `resources/views/livewire/tree-vertical.blade.php`
- Modify: `tests/Feature/MobileTreeFeaturesTest.php`

- [ ] **Step 1: Write test for tree-vertical desktop tabs and mobile toolbar**

Add test in `tests/Feature/MobileTreeFeaturesTest.php`:
```php
it('renders unified desktop navigation tabs and mobile toolbar on tree-vertical', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Hasan',
        'slug' => 'keluarga-hasan',
        'is_premium' => true,
    ]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    $component = Livewire::test('tree-vertical', ['id' => $tree->id]);

    $component->assertSee('Bagan Canvas')
        ->assertSee('Fokus Explorer')
        ->assertSee('Buku Simpel')
        ->assertSee('Kalender Acara')
        ->assertSee('Poster Pigura (A1/A0)')
        ->assertSee('Buku Silsilah (PDF)')
        ->assertSee('Bagikan');
});
```

- [ ] **Step 2: Run test to verify it passes/fails**

Run: `php artisan test --filter=MobileTreeFeaturesTest`

- [ ] **Step 3: Update `resources/views/livewire/tree-vertical.blade.php`**

Edit `resources/views/livewire/tree-vertical.blade.php`:
1. Harmonize header with `tree-view`:
   - Left: Breadcrumb link (`Dashboard / {{ $tree->name }}`), badges.
   - Center (Desktop): Navigation tabs:
     - `[🌳 Bagan Canvas]`
     - `[🧭 Fokus Explorer]` (Active)
     - `[📄 Buku Simpel]`
     - `[🗓️ Kalender Acara]`
   - Right (Desktop):
     - `Bagikan`
     - `Ekspor ▾` (All 6 formats)
     - `Kelola ▾` (Cek Hubungan, Kelola Pohon & Statistik, Import, Dock Variant link)
     - `+ Anggota Baru`
   - Mobile Header (`flex md:hidden items-center gap-1.5`):
     - Share, Export dropdown, Calendar modal, More options dropdown (`⋮`).
2. Update Mobile Bottom Navigation bar: 5 balanced items (`Fokus`, `Bagan`, `Direktori`, `Kalender`, `Tambah`).

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=MobileTreeFeaturesTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/livewire/tree-vertical.blade.php tests/Feature/MobileTreeFeaturesTest.php
git commit -m "feat(tree-vertical): harmonize desktop navigation tabs and restore mobile toolbar"
```

---

### Task 4: Unifying Navigation & Mobile Toolbar in `tree-simple.blade.php` (Buku Simpel)

**Files:**
- Modify: `resources/views/livewire/tree-simple.blade.php`
- Modify: `tests/Feature/MobileTreeFeaturesTest.php`

- [ ] **Step 1: Write test for tree-simple navigation tabs and mobile toolbar**

Add test in `tests/Feature/MobileTreeFeaturesTest.php`:
```php
it('renders unified desktop navigation tabs and mobile toolbar on tree-simple', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Sudirman',
        'slug' => 'keluarga-sudirman',
        'is_premium' => true,
    ]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    $component = Livewire::test('tree-simple', ['id' => $tree->id]);

    $component->assertSee('Bagan Canvas')
        ->assertSee('Fokus Explorer')
        ->assertSee('Buku Simpel')
        ->assertSee('Kalender Acara')
        ->assertSee('Poster Pigura (A1/A0)')
        ->assertSee('Buku Silsilah (PDF)');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MobileTreeFeaturesTest`

- [ ] **Step 3: Update `resources/views/livewire/tree-simple.blade.php`**

Edit `resources/views/livewire/tree-simple.blade.php`:
1. Align header with `tree-view` and `tree-vertical`:
   - Left: Breadcrumb link (`Dashboard / {{ $tree->name }}`), badges.
   - Center (Desktop): Navigation tabs with `Buku Simpel` highlighted as active.
   - Right (Desktop): `Bagikan`, `Ekspor ▾`, `Kelola ▾`, `+ Anggota Baru`.
   - Mobile Header: Compact icon buttons for Share, Export, Calendar, Options (`⋮`).
2. Mobile View Switcher: `[🧭 Fokus] [🌳 Bagan] [📄 Simpel]`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=MobileTreeFeaturesTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/livewire/tree-simple.blade.php tests/Feature/MobileTreeFeaturesTest.php
git commit -m "feat(tree-simple): harmonize navigation tabs and restore mobile action controls"
```

---

### Task 5: Implementing Opsi 2 (Floating Pill Toolbar / Apple Freeform Dock)

**Files:**
- Create: `resources/views/tree/dock.blade.php`
- Create: `resources/views/tree/public-dock.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/livewire/tree-view.blade.php`
- Create: `tests/Feature/TreeDockVariantTest.php`

- [ ] **Step 1: Write test for Opsi 2 dock route in `tests/Feature/TreeDockVariantTest.php`**

Create `tests/Feature/TreeDockVariantTest.php`:
```php
<?php

use App\Models\FamilyTree;
use App\Models\User;

test('authenticated user can visit dock variant view (Option 2)', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Bani Soekamto']);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    $response = $this->get(route('tree.dock', $tree));
    $response->assertOk()
        ->assertSee('Bani Soekamto')
        ->assertSee('tree-view');
});

test('public tree can be viewed with dock variant', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Publik',
        'slug' => 'keluarga-publik',
        'is_public' => true,
    ]);

    $response = $this->get(route('tree.public.dock', $tree->slug));
    $response->assertOk()
        ->assertSee('Keluarga Publik');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=TreeDockVariantTest`
Expected: FAIL (route `tree.dock` not defined).

- [ ] **Step 3: Register routes in `routes/web.php`**

In `routes/web.php`:
1. Add internal dock route:
   ```php
   Route::get('tree/{tree}/dock', function ($tree) {
       $treeModel = is_numeric($tree)
           ? FamilyTree::where('id', $tree)->first() ?? FamilyTree::where('slug', $tree)->firstOrFail()
           : FamilyTree::where('slug', $tree)->first() ?? FamilyTree::where('id', $tree)->firstOrFail();

       return view('tree.dock', ['id' => $treeModel->id, 'tree' => $treeModel]);
   })->name('tree.dock');
   ```
2. Add public dock route:
   ```php
   Route::get('public/tree/{slug}/dock', function ($slug) use ($recordTreeVisit) {
       $tree = FamilyTree::where('slug', $slug)->firstOrFail();

       if (! $tree->is_public) {
           abort(404);
       }

       if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
           return redirect()->route('tree.password', $tree->slug);
       }

       $recordTreeVisit($tree);

       return view('tree.public-dock', ['id' => $tree->id, 'tree' => $tree]);
   })->name('tree.public.dock');
   ```

- [ ] **Step 4: Create `resources/views/tree/dock.blade.php` and `resources/views/tree/public-dock.blade.php`**

Create `resources/views/tree/dock.blade.php`:
```blade
<x-layouts::app :title="$tree->name . ' - Dock View (Opsi 2)'">
    <livewire:tree-view :id="$id" :dock="true" />
</x-layouts::app>
```

Create `resources/views/tree/public-dock.blade.php`:
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <title>{{ $tree->name }} - Silsilah Digital</title>
    @fluxAppearance
    @livewireStyles
</head>
<body class="bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100 min-h-screen font-body antialiased overflow-hidden">
    <livewire:tree-view :id="$id" :is-public="true" :public-slug="$tree->slug" :dock="true" />
    @fluxScripts
    @livewireScripts
</body>
</html>
```

- [ ] **Step 5: Support `:dock="true"` in `resources/views/livewire/tree-view.blade.php`**

In `app/Livewire/TreeView.php` or `resources/views/livewire/tree-view.blade.php`:
- Add `public bool $dock = false;`
- In the template:
  When `$dock` is true:
  - Hide the default flat top bar.
  - Render the floating dock island at `fixed top-5 left-1/2 -translate-x-1/2 z-40 flex items-center gap-2 px-3.5 py-2 rounded-full bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-zinc-700/80 shadow-2xl`:
    - Tree Name + Status Pill
    - Mode Switcher: `[🌳 Bagan]` `[🧭 Fokus]` `[📄 Simpel]` `[🗓️ Kalender]`
    - Action buttons: Bagikan, Ekspor, + Anggota
    - Switch back link: `[Kembali ke Tampilan Standar (Opsi 1)]` (`route('tree.show', $tree)`)
  - Canvas fills full screen height `h-screen w-screen`.

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=TreeDockVariantTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add routes/web.php resources/views/tree/dock.blade.php resources/views/tree/public-dock.blade.php resources/views/livewire/tree-view.blade.php tests/Feature/TreeDockVariantTest.php
git commit -m "feat(dock): add Option 2 floating pill toolbar variant with dedicated route"
```

---

### Task 6: Calendar Mobile UX Polish & Micro Event Dots

**Files:**
- Modify: `resources/views/components/family-reminders-content.blade.php`
- Modify: `resources/views/tree/calendar.blade.php`
- Modify: `tests/Feature/FamilyCalendarViewTest.php`

- [ ] **Step 1: Write test for mobile calendar day indicators**

Add test in `tests/Feature/FamilyCalendarViewTest.php`:
```php
it('renders compact event indicators on calendar grid for mobile screens', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $member = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kyai',
        'last_name' => 'Asmuni',
        'is_living' => false,
        'death_date' => now()->startOfMonth()->addDays(5)->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    Livewire::test('family-reminders', ['treeId' => $tree->id])
        ->assertSee('Haul')
        ->assertSee('Asmuni');
});
```

- [ ] **Step 2: Update `resources/views/components/family-reminders-content.blade.php`**

In the Calendar Weeks Grid:
- For `< sm` mobile screens: render compact colored indicator dots (🟢 Haul, 🎂 Ultah, 💍 Nikah) with a count indicator (`+2`).
- For `sm:` screens: preserve full textual event badges.
- Optimize calendar page container padding: `px-3 sm:px-6 py-2.5 sm:py-3.5`.

- [ ] **Step 3: Run test to verify it passes**

Run: `php artisan test --filter=FamilyCalendarViewTest`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/family-reminders-content.blade.php resources/views/tree/calendar.blade.php tests/Feature/FamilyCalendarViewTest.php
git commit -m "feat(calendar): optimize monthly calendar grid and micro event dots for mobile"
```

---

### Task 7: Full Verification, Pint Code Style & Production Deployment

**Files:**
- All touched files

- [ ] **Step 1: Run complete test suite**

Run: `php artisan test --compact`
Expected: All tests pass (253+ passed, 0 failures).

- [ ] **Step 2: Run Pint code style fixer**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"result": "pass"}`.

- [ ] **Step 3: Push changes to GitHub main**

Run: `git push origin main`

- [ ] **Step 4: Deploy to remote production container (`bpsdemak@10.133.21.24`)**

Run:
```bash
ssh bpsdemak@10.133.21.24 "cd /home/bpsdemak/apps/muhshi/silsilah && git pull origin main && docker cp app silsilah-franken:/app/ && docker cp resources silsilah-franken:/app/ && docker cp routes silsilah-franken:/app/ && docker exec silsilah-franken php artisan optimize:clear"
```

- [ ] **Step 5: Verify production smoke test**

Run:
```bash
ssh bpsdemak@10.133.21.24 "curl -s -o /dev/null -w '%{http_code}' http://localhost:8881/tree/1/dock"
```
Expected: 200 or 302.
