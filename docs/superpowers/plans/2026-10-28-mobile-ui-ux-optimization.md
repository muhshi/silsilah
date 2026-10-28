# Mobile UI/UX Optimization & Missing Features Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Overhaul the mobile viewport (HP) proportions across the dashboard, restore all missing action buttons (Share, Export 6 formats, Family Calendar, Settings, Relationship Calculator, Import) on mobile tree views (`tree-vertical`, `tree-view`, `tree-simple`), and polish the mobile 7-column calendar grid for a fluid native-app experience.

**Architecture:** 
1. Redesign `dashboard-trees` using responsive Tailwind v4 classes: compact hero header, 2-column touch-friendly action buttons on mobile, 3-column compact metric stat cards fitting above the fold on mobile, and responsive tree cards.
2. Unify mobile header toolbars across all 3 tree views (`tree-vertical`, `tree-view`, `tree-simple`) with dedicated mobile action buttons (`Bagikan`, `Ekspor` dropdown with all 6 formats, `Kalender` modal/page shortcut, and `⋮ Opsi` dropdown for Hubungan, Kelola, and Import) rather than hiding everything behind `hidden md:flex`.
3. Enhance mobile bottom navigation bar with 5 clear touch targets (`Fokus`, `Bagan`, `Direktori`, `Kalender`, `Tambah`) and safe-area padding.
4. Refine the 7-column monthly calendar grid on mobile with micro-dot event badges (🟢 Haul, 🎂 Ultah, 💍 Nikah) so dates never cramp or overflow, seamlessly showing the selected day's event card below.

**Tech Stack:** Laravel 13, Livewire 4, Flux UI, Alpine.js, Tailwind CSS v4, Pest 4.

---

### Task 1: Dashboard Mobile Optimization & Proportions Overhaul

**Files:**
- Modify: `resources/views/components/⚡dashboard-trees.blade.php`
- Create: `tests/Feature/DashboardMobileResponsiveTest.php`

- [ ] **Step 1: Write failing feature test for dashboard mobile layout elements**

Create `tests/Feature/DashboardMobileResponsiveTest.php`:
```php
<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

test('dashboard renders responsive mobile metrics and action buttons properly', function () {
    $user = User::factory()->create(['tree_quota' => 5]);
    $tree1 = FamilyTree::factory()->create(['name' => 'Keluarga Ahmad', 'is_public' => true, 'is_premium' => true]);
    $tree2 = FamilyTree::factory()->create(['name' => 'Keluarga Budi', 'is_public' => false, 'is_premium' => false]);
    $tree1->users()->attach($user->id, ['role' => 'owner']);
    $tree2->users()->attach($user->id, ['role' => 'owner']);

    Member::factory()->count(15)->create(['family_tree_id' => $tree1->id]);
    Member::factory()->count(10)->create(['family_tree_id' => $tree2->id]);

    $this->actingAs($user);

    $component = Livewire::test('dashboard-trees')
        ->assertSee('Silsilah Keluarga')
        ->assertSee('Buat Silsilah')
        ->assertSee('Beli Kuota')
        ->assertSee('Total Silsilah')
        ->assertSee('Total Anggota')
        ->assertSee('Pohon Premium')
        ->assertSee('25') // 15 + 10 members
        ->assertSee('Keluarga Ahmad')
        ->assertSee('Keluarga Budi');

    $html = $component->html();
    
    // Assert 3-column mobile metric grid exists
    expect($html)->toContain('grid-cols-3');
    // Assert action buttons have 2-column mobile layout
    expect($html)->toContain('grid-cols-2');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DashboardMobileResponsiveTest`
Expected: FAIL (assertion `expect($html)->toContain('grid-cols-3')` fails because it's currently `grid-cols-1 sm:grid-cols-3`).

- [ ] **Step 3: Update `resources/views/components/⚡dashboard-trees.blade.php` with mobile-first proportions**

Edit `resources/views/components/⚡dashboard-trees.blade.php`:
1. In the Hero Section (lines ~204-232):
   - Title: change `text-4xl sm:text-5xl` to `text-2xl sm:text-4xl lg:text-5xl font-headline font-extrabold text-zinc-900 dark:text-white tracking-tight leading-tight`.
   - Subtitle: change `text-base sm:text-lg mt-2` to `text-xs sm:text-base text-zinc-600 dark:text-zinc-400 mt-1 sm:mt-2 leading-relaxed`.
   - Button Container: change `<div class="flex items-center gap-3 flex-wrap">` to `<div class="grid grid-cols-2 sm:flex sm:items-center gap-2.5 sm:gap-3 w-full sm:w-auto">`.
   - Button `Buat Silsilah Baru`:
     ```blade
     <button wire:click="openCreateTree"
             class="flex items-center justify-center gap-1.5 sm:gap-2 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white px-3 sm:px-6 py-2.5 sm:py-3.5 rounded-xl sm:rounded-2xl font-bold shadow-md shadow-emerald-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all w-full sm:w-auto text-xs sm:text-sm cursor-pointer">
         <span class="material-symbols-outlined text-lg sm:text-xl">add</span>
         <span>Buat Silsilah</span>
     </button>
     ```
   - Button `Beli Kuota Pohon`:
     ```blade
     <flux:modal.trigger name="buy-quota-modal">
         <button class="flex items-center justify-center gap-1.5 sm:gap-2 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 px-3 sm:px-5 py-2.5 sm:py-3.5 rounded-xl sm:rounded-2xl font-bold hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition-all w-full sm:w-auto text-xs sm:text-sm shadow-2xs cursor-pointer">
             <span class="material-symbols-outlined text-base sm:text-lg">shopping_bag</span>
             <span>Beli Kuota</span>
         </button>
     </flux:modal.trigger>
     ```
2. In the Stat Metric Cards (lines ~234-278):
   Change `<div class="grid grid-cols-1 sm:grid-cols-3 gap-5">` to:
   ```blade
   <div class="grid grid-cols-3 gap-2 sm:gap-5">
       <!-- Stat 1: Total Pohon & Kuota -->
       <div class="p-3 sm:p-5 lg:p-6 rounded-2xl sm:rounded-3xl bg-gradient-to-br from-emerald-50/80 to-white dark:from-emerald-950/20 dark:to-zinc-900 border border-emerald-100 dark:border-emerald-900/40 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
           <div class="min-w-0">
               <p class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-0.5 truncate">Total Silsilah</p>
               <p class="text-xl sm:text-3xl lg:text-4xl font-extrabold text-zinc-900 dark:text-white font-headline tabular-nums">{{ $totalTrees }}</p>
               <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">
                   Kuota: <span class="font-bold text-zinc-700 dark:text-zinc-200">{{ $ownedTreesCount }}/{{ $treeQuota }}</span>
                   <span class="hidden sm:inline">
                       @if($remainingQuota > 0)
                           <span class="text-emerald-600 dark:text-emerald-400 font-bold">(Sisa {{ $remainingQuota }})</span>
                       @else
                           <span class="text-amber-600 dark:text-amber-400 font-bold">(Penuh)</span>
                       @endif
                   </span>
               </p>
           </div>
           <div class="hidden sm:flex w-10 h-10 lg:w-14 lg:h-14 rounded-2xl bg-emerald-100/80 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 items-center justify-center text-xl lg:text-2xl shadow-inner shrink-0">
               🌳
           </div>
       </div>

       <!-- Stat 2: Total Anggota -->
       <div class="p-3 sm:p-5 lg:p-6 rounded-2xl sm:rounded-3xl bg-gradient-to-br from-amber-50/80 to-white dark:from-amber-950/20 dark:to-zinc-900 border border-amber-100 dark:border-amber-900/40 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
           <div class="min-w-0">
               <p class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 mb-0.5 truncate">Total Anggota</p>
               <p class="text-xl sm:text-3xl lg:text-4xl font-extrabold text-zinc-900 dark:text-white font-headline tabular-nums">{{ $totalMembers }}</p>
               <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">Jiwa terhubung</p>
           </div>
           <div class="hidden sm:flex w-10 h-10 lg:w-14 lg:h-14 rounded-2xl bg-amber-100/80 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 items-center justify-center text-xl lg:text-2xl shadow-inner shrink-0">
               👥
           </div>
       </div>

       <!-- Stat 3: Status Premium -->
       <div class="p-3 sm:p-5 lg:p-6 rounded-2xl sm:rounded-3xl bg-gradient-to-br from-purple-50/80 to-white dark:from-purple-950/20 dark:to-zinc-900 border border-purple-100 dark:border-purple-900/40 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
           <div class="min-w-0">
               <p class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400 mb-0.5 truncate">Pohon Premium</p>
               <p class="text-xl sm:text-3xl lg:text-4xl font-extrabold text-zinc-900 dark:text-white font-headline tabular-nums">{{ $totalPremium }}</p>
               <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">Fitur tak terbatas</p>
           </div>
           <div class="hidden sm:flex w-10 h-10 lg:w-14 lg:h-14 rounded-2xl bg-purple-100/80 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 items-center justify-center text-xl lg:text-2xl shadow-inner shrink-0">
               👑
           </div>
       </div>
   </div>
   ```
3. In Tree Cards (lines ~359-450):
   Change padding from `p-6` to `p-4 sm:p-6 rounded-2xl sm:rounded-3xl` and ensure action button row fits comfortably on mobile screens.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=DashboardMobileResponsiveTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/⚡dashboard-trees.blade.php tests/Feature/DashboardMobileResponsiveTest.php
git commit -m "feat(ui): optimize dashboard mobile proportions and responsive layout"
```

---

### Task 2: Restoring Missing Action Buttons on Mobile in `tree-vertical.blade.php` (Fokus Explorer)

**Files:**
- Modify: `resources/views/livewire/tree-vertical.blade.php`
- Modify: `tests/Feature/MobileTreeFeaturesTest.php`

- [ ] **Step 1: Write failing test in `tests/Feature/MobileTreeFeaturesTest.php`**

Add test to `tests/Feature/MobileTreeFeaturesTest.php`:
```php
it('exposes share, export, calendar, and management buttons in tree-vertical header for mobile', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Hasan',
        'slug' => 'keluarga-hasan',
        'is_premium' => true,
    ]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    $component = Livewire::test('tree-vertical', ['id' => $tree->id]);

    // Check that mobile actions are rendered in HTML
    $component->assertSee('Bagikan')
        ->assertSee('Ekspor')
        ->assertSee('Poster Pigura (A1/A0)')
        ->assertSee('Buku Silsilah (PDF)')
        ->assertSee('Kalender')
        ->assertSee('Cek Hubungan Kekerabatan');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MobileTreeFeaturesTest`
Expected: If any element was only inside `hidden md:flex`, it may fail or we need to ensure the mobile header specifically contains these actions.

- [ ] **Step 3: Refactor header and mobile action toolbar in `resources/views/livewire/tree-vertical.blade.php`**

Edit `resources/views/livewire/tree-vertical.blade.php`:
1. In the header bar (`lines ~265-455`):
   - Replace `<div class="hidden md:flex items-center gap-2.5 flex-wrap">` with a dual-mode responsive layout:
     - On desktop (`hidden md:flex`): preserve the full horizontal toolbar.
     - On mobile (`flex md:hidden items-center gap-1.5`): render a compact mobile action bar:
       1. **Bagikan (Share Button)**:
          ```blade
          @if($tree->slug)
              <button type="button"
                      x-data="{ shared: false, async share() {
                          const text = {{ json_encode("Halo! Yuk lihat silsilah keluarga \"{$tree->name}\":\n" . url('/public/tree/' . $tree->slug)) }};
                          const url = '{{ url('/public/tree/' . $tree->slug) }}';
                          if (navigator.share) {
                              try {
                                  await navigator.share({ title: '{{ addslashes($tree->name) }}', text: text, url: url });
                                  return;
                              } catch(err) {}
                          }
                          try {
                              await navigator.clipboard.writeText(url);
                              this.shared = true;
                              setTimeout(() => this.shared = false, 2500);
                          } catch(e) {}
                      } }"
                      @click="share()"
                      class="p-2 rounded-xl bg-zinc-100 hover:bg-emerald-50 active:scale-95 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 transition-all flex items-center justify-center shrink-0"
                      title="Bagikan Silsilah">
                  <span class="material-symbols-outlined text-lg" x-show="!shared">share</span>
                  <span class="material-symbols-outlined text-lg text-emerald-600" x-show="shared">check</span>
              </button>
          @endif
          ```
       2. **Ekspor (Export Dropdown)**:
          ```blade
          <flux:dropdown>
              <button class="p-2 rounded-xl bg-zinc-100 hover:bg-purple-50 active:scale-95 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 transition-all flex items-center justify-center shrink-0"
                      title="Ekspor Bagan">
                  <span class="material-symbols-outlined text-lg">download</span>
              </button>
              <flux:menu class="!w-64">
                  <flux:menu.item icon="photo"
                                  href="#"
                                  @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'banner']) }}', title: 'Poster Silsilah Pigura (A1/A0)' } }))">
                      Poster Pigura (A1/A0)
                  </flux:menu.item>
                  <flux:menu.item icon="book-open"
                                  href="#"
                                  @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'book']) }}', title: 'Buku Silsilah (PDF)' } }))">
                      Buku Silsilah (PDF)
                  </flux:menu.item>
                  <flux:menu.item icon="photo"
                                  href="#"
                                  @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'png', 'view' => 'vertical']) }}', title: 'Gambar Silsilah (PNG)' } }))">
                      Export Gambar (PNG)
                  </flux:menu.item>
                  <flux:menu.item icon="document-arrow-down"
                                  href="#"
                                  @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'pdf', 'view' => 'vertical']) }}', title: 'Bagan Silsilah (PDF)' } }))">
                      Export PDF
                  </flux:menu.item>
                  <flux:menu.item icon="sparkles"
                                  href="#"
                                  @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'prompt']) }}', title: 'Prompt AI (.md)' } }))">
                      Export Prompt AI (.md)
                  </flux:menu.item>
                  <flux:menu.item icon="code-bracket"
                                  href="#"
                                  @click.prevent="window.dispatchEvent(new CustomEvent('start-export', { detail: { url: '{{ route('tree.export', ['tree' => $tree, 'format' => 'json']) }}', title: 'Data Silsilah (JSON)' } }))">
                      Export Data (JSON)
                  </flux:menu.item>
              </flux:menu>
          </flux:dropdown>
          ```
       3. **Kalender Acara Button**:
          ```blade
          <button type="button"
                  wire:click="$dispatch('open-family-reminders')"
                  class="p-2 rounded-xl bg-zinc-100 hover:bg-emerald-50 active:scale-95 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 transition-all flex items-center justify-center shrink-0"
                  title="Agenda & Kalender Keluarga">
              <span class="text-base">🗓️</span>
          </button>
          ```
       4. **Menu Opsi Lainnya (More `⋮`)**:
          ```blade
          <flux:dropdown>
              <button class="p-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 active:scale-95 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 transition-all flex items-center justify-center shrink-0"
                      title="Opsi & Fitur Lainnya">
                  <span class="material-symbols-outlined text-lg">more_vert</span>
              </button>
              <flux:menu class="!w-64">
                  <flux:menu.item icon="user-group" wire:click="$dispatch('open-relationship-calculator')">
                      Cek Hubungan Kekerabatan
                  </flux:menu.item>
                  <flux:menu.item icon="calendar-days" href="{{ $isPublic ? route('tree.public.calendar', $tree->slug) : route('tree.calendar', $tree->slug ?: $tree->id) }}">
                      Buka Halaman Kalender Penuh
                  </flux:menu.item>
                  @if(!$isPublic)
                      <flux:menu.item icon="adjustments-horizontal" wire:click="$dispatch('open-tree-settings')">
                          Kelola Pohon &amp; Statistik
                      </flux:menu.item>
                      <flux:menu.item icon="arrow-down-on-square" wire:click="$dispatch('open-import-modal')">
                          Import CSV / GEDCOM
                      </flux:menu.item>
                  @else
                      <flux:menu.item icon="pencil-square" wire:click="$dispatch('open-contribute-modal')">
                          Ajukan Usulan Data
                      </flux:menu.item>
                  @endif
                  <flux:menu.separator />
                  <flux:menu.item icon="document-text" href="{{ $isPublic ? ($publicSlug ? url('/public/tree/'.$publicSlug.'/simple') : '#') : route('tree.simple', $tree) }}" wire:navigate>
                      Format Simpel (Buku Siap Cetak)
                  </flux:menu.item>
              </flux:menu>
          </flux:dropdown>
          ```
2. In the Mobile Bottom Navigation Bar (`lines ~840-875`):
   - Update to 5 balanced icons:
     1. 🧭 `Fokus`
     2. 🌳 `Bagan`
     3. 👥 `Direktori`
     4. 🗓️ `Kalender` (opens `open-family-reminders`)
     5. ➕ `Tambah` (or ✏️ `Usul` if public)

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=MobileTreeFeaturesTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/livewire/tree-vertical.blade.php tests/Feature/MobileTreeFeaturesTest.php
git commit -m "feat(tree-vertical): restore mobile share, export, calendar, and options toolbar"
```

---

### Task 3: Restoring Missing Action Buttons on Mobile in `tree-view.blade.php` (Bagan Canvas)

**Files:**
- Modify: `resources/views/livewire/tree-view.blade.php`
- Modify: `tests/Feature/MobileTreeFeaturesTest.php`

- [ ] **Step 1: Write failing test in `tests/Feature/MobileTreeFeaturesTest.php` for `tree-view`**

Add test to `tests/Feature/MobileTreeFeaturesTest.php`:
```php
it('exposes share, export, and calendar in tree-view header for mobile', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Santoso',
        'slug' => 'keluarga-santoso',
    ]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    Livewire::test('tree-view', ['id' => $tree->id])
        ->assertSee('Poster Silsilah Pigura (A1/A0)')
        ->assertSee('Buku Silsilah (PDF)')
        ->assertSee('Cek Hubungan Kekerabatan');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MobileTreeFeaturesTest`

- [ ] **Step 3: Update `resources/views/livewire/tree-view.blade.php`**

Edit `resources/views/livewire/tree-view.blade.php`:
1. Add mobile action bar (`flex md:hidden items-center gap-1.5`) next to the tree name in the sticky header, matching the clean pattern with:
   - Share button (`navigator.share` + copy fallback)
   - Export dropdown (all 6 formats)
   - Calendar button (`🗓️`)
   - Opsi dropdown (`⋮` with Cek Hubungan, Kelola Pohon, Import, Simple View)
2. In Segmented View Switcher for Mobile (`lines ~296-310`):
   - Update from 2 tabs (`Fokus`, `Bagan`) to include `Simple View` as well:
     `[🧭 Fokus]` `[🌳 Bagan]` `[📄 Simple]`
3. In Mobile Bottom Navigation Bar (`lines ~355-383`):
   - Update to 5 balanced icons:
     1. 🧭 `Fokus`
     2. 🌳 `Bagan` (active)
     3. 👥 `Direktori`
     4. 🗓️ `Kalender`
     5. ➕ `Tambah` (or ✏️ `Usul` if public)

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=MobileTreeFeaturesTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/livewire/tree-view.blade.php tests/Feature/MobileTreeFeaturesTest.php
git commit -m "feat(tree-view): restore mobile toolbar, export dropdown, and 3-way view switcher"
```

---

### Task 4: Restoring Missing Action Buttons on Mobile in `tree-simple.blade.php` (Simple View)

**Files:**
- Modify: `resources/views/livewire/tree-simple.blade.php`
- Modify: `tests/Feature/MobileTreeFeaturesTest.php`

- [ ] **Step 1: Write failing test in `tests/Feature/MobileTreeFeaturesTest.php` for `tree-simple`**

Add test to `tests/Feature/MobileTreeFeaturesTest.php`:
```php
it('exposes share, export, and calendar in tree-simple header for mobile', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Sudirman',
        'slug' => 'keluarga-sudirman',
    ]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    Livewire::test('tree-simple', ['id' => $tree->id])
        ->assertSee('Poster Silsilah Pigura (A1/A0)')
        ->assertSee('Buku Silsilah (PDF)')
        ->assertSee('Cek Hubungan Kekerabatan');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MobileTreeFeaturesTest`

- [ ] **Step 3: Update `resources/views/livewire/tree-simple.blade.php`**

Edit `resources/views/livewire/tree-simple.blade.php`:
1. Add mobile action bar (`flex md:hidden items-center gap-1.5`) in the header with Share, Export, Calendar, and Opsi dropdown.
2. Add mobile view switcher (`[🧭 Fokus] [🌳 Bagan] [📄 Simple]`) so mobile users can switch back easily.
3. Ensure simple tree branch cards wrap comfortably on mobile screen widths without horizontal overflow.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=MobileTreeFeaturesTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/livewire/tree-simple.blade.php tests/Feature/MobileTreeFeaturesTest.php
git commit -m "feat(tree-simple): add mobile toolbar, export options, and responsive view switching"
```

---

### Task 5: Mobile UX Polish for Monthly Calendar (`family-reminders-content.blade.php` & `tree/calendar.blade.php`)

**Files:**
- Modify: `resources/views/components/family-reminders-content.blade.php`
- Modify: `resources/views/tree/calendar.blade.php`
- Modify: `resources/views/tree/public-calendar.blade.php`
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

- [ ] **Step 2: Run test to verify it runs cleanly**

Run: `php artisan test --filter=FamilyCalendarViewTest`

- [ ] **Step 3: Update `resources/views/components/family-reminders-content.blade.php`**

In the Calendar Weeks Grid (lines ~204-235):
- For mobile screens (`< sm`), inside each day cell:
  - Render compact indicator dots with count:
    ```blade
    {{-- Mobile Micro Indicators (< sm) --}}
    <div class="flex sm:hidden items-center gap-1 mt-1 flex-wrap">
        @foreach($day['events']->take(3) as $ev)
            @if($ev['type'] === 'haul')
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0" title="{{ $ev['title'] }}"></span>
            @elseif($ev['type'] === 'birthday')
                <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0" title="{{ $ev['title'] }}"></span>
            @else
                <span class="w-2 h-2 rounded-full bg-purple-500 shrink-0" title="{{ $ev['title'] }}"></span>
            @endif
        @endforeach
        @if($day['events']->count() > 3)
            <span class="text-[9px] font-bold text-zinc-500">+{{ $day['events']->count() - 3 }}</span>
        @endif
    </div>

    {{-- Tablet/Desktop Full Pills (sm:) --}}
    <div class="hidden sm:block space-y-1 mt-1">
        ... [existing full badges] ...
    </div>
    ```
- In `resources/views/tree/calendar.blade.php` and `resources/views/tree/public-calendar.blade.php`:
  - Optimize header container padding for mobile: `px-3 sm:px-6 py-2.5 sm:py-3.5`.
  - Ensure the back button (`&larr; Kembali ke Pohon`) and month switcher are thumb-friendly.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FamilyCalendarViewTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/family-reminders-content.blade.php resources/views/tree/calendar.blade.php resources/views/tree/public-calendar.blade.php tests/Feature/FamilyCalendarViewTest.php
git commit -m "feat(calendar): optimize monthly calendar grid and event dots for mobile screens"
```

---

### Task 6: Full Verification, Pint Formatting & Production Deployment

**Files:**
- All touched files

- [ ] **Step 1: Run complete test suite**

Run: `php artisan test --compact`
Expected: All tests pass with 0 failures.

- [ ] **Step 2: Run Pint code style fixer**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"result": "pass"}`.

- [ ] **Step 3: Push changes to GitHub main**

Run: `git push origin main`

- [ ] **Step 4: Deploy to production server (`bpsdemak@10.133.21.24`)**

Run:
```bash
ssh bpsdemak@10.133.21.24 "cd /home/bpsdemak/apps/muhshi/silsilah && git pull origin main && docker cp app silsilah-franken:/app/ && docker cp resources silsilah-franken:/app/ && docker cp tests silsilah-franken:/app/ && docker exec silsilah-franken php artisan optimize:clear"
```

- [ ] **Step 5: Verify production smoke test**

Run:
```bash
ssh bpsdemak@10.133.21.24 "curl -s -o /dev/null -w '%{http_code}' http://localhost:8881/dashboard"
```
Expected: 200 or 302 (redirect to login).
