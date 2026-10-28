# Tree Admin & Management Dashboard Hub Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a comprehensive Tree Management & Admin Hub ("Dashboard Pengelola Pohon") allowing tree owners and collaborators to view tree statistics, public visit counts, collaboration settings, and full audit logs, while empowering system admins (even in regular user context) to manually override tree premium status with payment method logging (cash/COD, transfer, gateway fallback).

**Architecture:** Extend `FamilyTree` model with `views_count` tracking and analytical aggregates; evolve `⚡tree-settings.blade.php` into a tabbed management hub (Ikhtisar & Statistik, Status & Lisensi, Riwayat Log, Kolaborasi, Pengaturan & Bahaya); add Superadmin override controls protected by role checks with dual logging to `ActivityLog` and `AdminActivityLog`; and add a dedicated `/admin/trees` management view in the central Superadmin panel.

**Tech Stack:** Laravel 13, Livewire 4 / Volt Single-File Components (`⚡...blade.php`), Flux UI, Tailwind CSS v4, MySQL, Pest 4.

---

### File Structure Map

```
app/
├── Models/
│   ├── FamilyTree.php                          # Modify: add views_count, getTreeStatistics()
database/
└── migrations/
    └── 2026_10_28_103000_add_views_count_to_family_trees_table.php  # Create: views_count migration
resources/
├── views/
│   ├── admin/
│   │   └── trees.blade.php                     # Create: Superadmin trees container
│   ├── components/
│   │   ├── ⚡admin-trees.blade.php              # Create: Central admin trees management component
│   │   ├── ⚡tree-settings.blade.php            # Modify: Elevate to full Tree Management Hub
│   │   └── ⚡dashboard-trees.blade.php          # Modify: Add Kelola Pohon button to cards
│   ├── layouts/
│   │   └── admin.blade.php                     # Modify: Add Pohon Silsilah sidebar link
│   └── livewire/
│       ├── tree-view.blade.php                 # Modify: Update Kelola Pohon trigger
│       ├── tree-vertical.blade.php             # Modify: Add Kelola Pohon & include component
│       └── tree-simple.blade.php               # Modify: Update Kelola Pohon trigger
routes/
└── web.php                                     # Modify: Add admin.trees & track public views
tests/
└── Feature/
    ├── TreeViewsCountTest.php                  # Create: Test public view counter
    ├── TreeAdminOverrideTest.php               # Create: Test admin manual override logic & logs
    ├── TreeManagementHubTest.php               # Create: Test hub tabs, stats, and collab
    └── AdminTreesManagementTest.php            # Create: Test admin trees panel
```

---

### Task 1: Database Migration & Public Views Counter

**Files:**
- Create: `database/migrations/2026_10_28_103000_add_views_count_to_family_trees_table.php`
- Modify: `app/Models/FamilyTree.php`
- Modify: `routes/web.php:82-125`
- Test: `tests/Feature/TreeViewsCountTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/TreeViewsCountTest.php`:
```php
<?php

use App\Models\FamilyTree;
use App\Models\User;

it('increments views_count when public tree is visited', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create([
        'is_public' => true,
        'views_count' => 0,
    ]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // First visit: views_count becomes 1
    $this->get(route('tree.public', $tree->slug))
        ->assertOk();

    expect($tree->refresh()->views_count)->toBe(1);

    // Refresh in same session: views_count should not double count
    $this->get(route('tree.public', $tree->slug))
        ->assertOk();

    expect($tree->refresh()->views_count)->toBe(1);
});

it('does not increment views_count if private tree is accessed by guest', function () {
    $tree = FamilyTree::factory()->create([
        'is_public' => false,
        'views_count' => 0,
    ]);

    $this->get(route('tree.public', $tree->slug))
        ->assertNotFound();

    expect($tree->refresh()->views_count)->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=TreeViewsCountTest`
Expected: FAIL (Column 'views_count' not found)

- [ ] **Step 3: Create migration and update model and routes**

Create `database/migrations/2026_10_28_103000_add_views_count_to_family_trees_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_trees', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('is_premium');
        });
    }

    public function down(): void
    {
        Schema::table('family_trees', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};
```

Run migration:
```bash
php artisan migrate --force
```

Update `app/Models/FamilyTree.php` to add `views_count` to `$fillable` and `$casts`:
```php
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_public',
        'is_premium',
        'views_count',
        'view_password',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_premium' => 'boolean',
            'views_count' => 'integer',
        ];
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
```

Update public routes in `routes/web.php` (lines 82-124) to increment views upon unique session access:
```php
// Helper closure for public tree view session counting
$recordTreeVisit = function (FamilyTree $tree) {
    $sessionKey = "tree_viewed_{$tree->id}";
    if (! session()->has($sessionKey)) {
        $tree->incrementViews();
        session()->put($sessionKey, true);
    }
};

Route::get('public/tree/{slug}', function ($slug) use ($recordTreeVisit) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if (! $tree->is_public) {
        abort(404);
    }

    if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.password', $tree->slug);
    }

    $recordTreeVisit($tree);

    return view('tree.public', ['id' => $tree->id, 'tree' => $tree, 'viewType' => 'horizontal']);
})->name('tree.public');

Route::get('public/tree/{slug}/vertical', function ($slug) use ($recordTreeVisit) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if (! $tree->is_public) {
        abort(404);
    }

    if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.password', $tree->slug);
    }

    $recordTreeVisit($tree);

    return view('tree.public', ['id' => $tree->id, 'tree' => $tree, 'viewType' => 'vertical']);
})->name('tree.public.vertical');

Route::get('public/tree/{slug}/simple', function ($slug) use ($recordTreeVisit) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if (! $tree->is_public) {
        abort(404);
    }

    if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.password', $tree->slug);
    }

    $recordTreeVisit($tree);

    return view('tree.public', ['id' => $tree->id, 'tree' => $tree, 'viewType' => 'simple']);
})->name('tree.public.simple');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=TreeViewsCountTest`
Expected: PASS (2 passed, 4 assertions)

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/FamilyTree.php routes/web.php tests/Feature/TreeViewsCountTest.php
git commit -m "feat(tree): add views_count column and unique session view tracker"
```

---

### Task 2: Tree Statistics Engine & Admin Manual Override Logic

**Files:**
- Modify: `app/Models/FamilyTree.php`
- Test: `tests/Feature/TreeAdminOverrideTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/TreeAdminOverrideTest.php`:
```php
<?php

use App\Models\ActivityLog;
use App\Models\AdminActivityLog;
use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

it('provides comprehensive tree statistics', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['views_count' => 42]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // Create 3 male (2 alive, 1 deceased) and 2 female (alive) members
    Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'is_alive' => true]);
    Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'is_alive' => true]);
    Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'is_alive' => false]);
    Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'is_alive' => true]);
    Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'is_alive' => true]);

    $stats = $tree->getStatistics();

    expect($stats['total_members'])->toBe(5)
        ->and($stats['male_count'])->toBe(3)
        ->and($stats['female_count'])->toBe(2)
        ->and($stats['alive_count'])->toBe(4)
        ->and($stats['deceased_count'])->toBe(1)
        ->and($stats['views_count'])->toBe(42);
});

it('allows admin to manually override free tree to premium with reason and logs', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($admin);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('overrideReason', 'cash_cod')
        ->set('overrideNotes', 'Bayar tunai Rp 5.000 pas acara temu trah')
        ->call('adminOverridePremium', true)
        ->assertHasNoErrors();

    expect($tree->refresh()->is_premium)->toBeTrue();

    // Verify ActivityLog entry on tree
    $activity = ActivityLog::where('family_tree_id', $tree->id)
        ->where('action', 'admin_override_premium')
        ->first();
    expect($activity)->not->toBeNull()
        ->and($activity->description)->toContain('Bayar tunai Rp 5.000');

    // Verify AdminActivityLog entry
    $adminLog = AdminActivityLog::where('target_id', $tree->id)
        ->where('action', 'tree_premium_override')
        ->first();
    expect($adminLog)->not->toBeNull()
        ->and($adminLog->admin_id)->toBe($admin->id);
});

it('blocks non-admin user from executing admin override', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('overrideReason', 'cash_cod')
        ->call('adminOverridePremium', true)
        ->assertForbidden();

    expect($tree->refresh()->is_premium)->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=TreeAdminOverrideTest`
Expected: FAIL (Method getStatistics does not exist, method adminOverridePremium does not exist)

- [ ] **Step 3: Implement getStatistics on FamilyTree**

In `app/Models/FamilyTree.php`, add method:
```php
    /**
     * @return array{
     *     total_members: int,
     *     male_count: int,
     *     female_count: int,
     *     alive_count: int,
     *     deceased_count: int,
     *     views_count: int,
     *     quota_limit: ?int,
     *     remaining_slots: ?int,
     *     is_premium: bool,
     * }
     */
    public function getStatistics(): array
    {
        $members = $this->members()->get(['id', 'gender', 'is_alive']);

        $total = $members->count();
        $male = $members->where('gender', 'male')->count();
        $female = $members->where('gender', 'female')->count();
        $alive = $members->where('is_alive', true)->count();
        $deceased = $members->where('is_alive', false)->count();

        return [
            'total_members' => $total,
            'male_count' => $male,
            'female_count' => $female,
            'alive_count' => $alive,
            'deceased_count' => $deceased,
            'views_count' => (int) ($this->views_count ?? 0),
            'quota_limit' => $this->maxMembersLimit(),
            'remaining_slots' => $this->remainingMemberSlots(),
            'is_premium' => $this->isPremium(),
        ];
    }
```

- [ ] **Step 4: Implement adminOverridePremium in ⚡tree-settings.blade.php**

In `resources/views/components/⚡tree-settings.blade.php`, add component properties and method:
```php
    public $overrideReason = 'cash_cod';
    public $overrideNotes = '';

    public function adminOverridePremium(bool $makePremium)
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        $reasonMap = [
            'cash_cod' => 'Bayar Tunai / COD',
            'bank_transfer' => 'Transfer Bank Manual',
            'gateway_issue' => 'Kendala Gateway Pembayaran QRIS',
            'special_promo' => 'Kompensasi / Promo Khusus',
            'other' => 'Lainnya',
        ];

        $reasonLabel = $reasonMap[$this->overrideReason] ?? $this->overrideReason;
        $notesText = trim($this->overrideNotes);
        $fullDescription = "Super Admin " . auth()->user()->name . ($makePremium ? " mengaktifkan status Premium manual" : " mengembalikan status Standar") . " (Metode: {$reasonLabel}" . ($notesText ? " - {$notesText}" : "") . ")";

        $this->tree->update(['is_premium' => $makePremium]);
        $this->loadTreeData();

        ActivityLog::log(
            $this->treeId,
            'admin_override_premium',
            $fullDescription,
            [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'is_premium' => $makePremium,
                'reason' => $this->overrideReason,
                'notes' => $this->overrideNotes,
            ]
        );

        AdminActivityLog::log(
            'tree_premium_override',
            $fullDescription,
            'family_trees',
            $this->tree->id,
            [
                'is_premium' => $makePremium,
                'reason' => $this->overrideReason,
                'notes' => $this->overrideNotes,
            ]
        );

        session()->flash('success_override', $makePremium ? 'Status pohon berhasil diubah menjadi Premium!' : 'Status pohon dikembalikan ke Standar.');
        $this->dispatch('refresh-tree');
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=TreeAdminOverrideTest`
Expected: PASS (3 passed, 10 assertions)

- [ ] **Step 6: Commit**

```bash
git add app/Models/FamilyTree.php resources/views/components/⚡tree-settings.blade.php tests/Feature/TreeAdminOverrideTest.php
git commit -m "feat(tree): add tree statistics engine and admin manual premium override logic"
```

---

### Task 3: Elevate Tree Management Hub UI (`⚡tree-settings.blade.php`) & View Integration

**Files:**
- Modify: `resources/views/components/⚡tree-settings.blade.php`
- Modify: `resources/views/livewire/tree-view.blade.php:240-252`
- Modify: `resources/views/livewire/tree-vertical.blade.php:400-415,850-856`
- Modify: `resources/views/livewire/tree-simple.blade.php:235-245`
- Modify: `resources/views/components/⚡dashboard-trees.blade.php:420-450`
- Test: `tests/Feature/TreeManagementHubTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/TreeManagementHubTest.php`:
```php
<?php

use App\Models\FamilyTree;
use App\Models\User;
use Livewire\Livewire;

it('renders tree management hub with statistics, license, logs, and collab tabs', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Keluarga Besar Sastrowardoyo']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->assertSee('Keluarga Besar Sastrowardoyo')
        ->assertSee('Ringkasan & Statistik')
        ->assertSee('Status & Lisensi')
        ->assertSee('Riwayat Aktivitas')
        ->assertSee('Kolaborasi')
        ->assertSee('Pengaturan')
        // Check statistics tab active by default or switchable
        ->set('activeTab', 'stats')
        ->assertSee('Total Anggota')
        ->assertSee('Kunjungan Publik')
        // Admin override should NOT be visible to regular user
        ->assertDontSee('Super Admin Override');
});

it('renders admin quick override panel in hub when viewer is superadmin', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($admin);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('activeTab', 'license')
        ->assertSee('Super Admin Override')
        ->assertSee('Jadikan Pohon Premium Manual');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=TreeManagementHubTest`
Expected: FAIL (Tab labels or admin override strings missing)

- [ ] **Step 3: Update ⚡tree-settings.blade.php UI**

Restructure `resources/views/components/⚡tree-settings.blade.php`:
1. Modal header with tree name, quick badge (👑 Premium vs 🌳 Standar, 🌍 Publik vs 🔒 Privat), and member count.
2. 5 Tabs:
   - `stats` ("Ringkasan & Statistik", default tab): Stat grid with Total Anggota, Laki-laki vs Perempuan, Hidup vs Wafat, and Total Kunjungan Publik (`views_count`), with copy public link button.
   - `license` ("Status & Lisensi"): Status card, 100 member limit meter, Upgrade 5K button for owner, plus **Super Admin Override Card** (only visible when `auth()->user()?->isAdmin()`).
   - `logs` ("Riwayat Aktivitas"): Filterable, paginated audit trail from `ActivityLog` with user avatars, tags, and timestamps.
   - `collab` ("Kolaborasi"): Premium guard, invite email form, shareable direct link creator, active editors list with delete button, pending invitations with WhatsApp text copy button.
   - `general` ("Pengaturan"): Tree name, description, public/private toggle, password protection, and Danger Zone (Delete Tree).
3. Ensure modal size is comfortable (`class="md:w-[48rem] max-w-3xl"`).

- [ ] **Step 4: Update Tree View Headers and Dashboard Tree Cards**

1. In `resources/views/livewire/tree-view.blade.php`:
   - Change dropdown item text from "Pengaturan Silsilah" to "Kelola Pohon (Dashboard & Statistik)" with icon `adjustments-horizontal`.
2. In `resources/views/livewire/tree-vertical.blade.php`:
   - Add `<flux:menu.item icon="adjustments-horizontal" wire:click="$dispatch('open-tree-settings')">Kelola Pohon (Dashboard & Statistik)</flux:menu.item>` in the `Opsi` dropdown.
   - Add `<livewire:tree-settings :tree-id="$tree->id" />` before closing tag.
3. In `resources/views/livewire/tree-simple.blade.php`:
   - Update `open-tree-settings` menu item label to "Kelola Pohon (Dashboard & Statistik)".
4. In `resources/views/components/⚡dashboard-trees.blade.php`:
   - Add a "Kelola" button/icon on each tree card leading to `open-tree-settings` or vertical view.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=TreeManagementHubTest`
Expected: PASS (2 passed, 8 assertions)

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/⚡tree-settings.blade.php resources/views/livewire/ resources/views/components/⚡dashboard-trees.blade.php tests/Feature/TreeManagementHubTest.php
git commit -m "feat(hub): elevate tree settings to comprehensive Tree Management & Admin Hub"
```

---

### Task 4: Super Admin Centralized Trees Management (`/admin/trees`)

**Files:**
- Create: `resources/views/admin/trees.blade.php`
- Create: `resources/views/components/⚡admin-trees.blade.php`
- Modify: `routes/web.php:66-77`
- Modify: `resources/views/layouts/admin.blade.php:51-80`
- Test: `tests/Feature/AdminTreesManagementTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AdminTreesManagementTest.php`:
```php
<?php

use App\Models\FamilyTree;
use App\Models\User;
use Livewire\Livewire;

it('blocks non-admin from accessing /admin/trees', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/trees')
        ->assertForbidden();
});

it('allows admin to visit /admin/trees and search trees', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.com']);
    $tree = FamilyTree::factory()->create(['name' => 'Pohon Silsilah Trah Budi']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($admin)
        ->get('/admin/trees')
        ->assertOk()
        ->assertSee('Pohon Silsilah Trah Budi');

    Livewire::test('admin-trees')
        ->set('search', 'Trah Budi')
        ->assertSee('Pohon Silsilah Trah Budi')
        ->set('search', 'NonExistentString')
        ->assertDontSee('Pohon Silsilah Trah Budi');
});

it('allows admin to toggle tree premium status directly from admin panel', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($admin);

    Livewire::test('admin-trees')
        ->call('openOverrideModal', $tree->id)
        ->set('overrideReason', 'bank_transfer')
        ->set('overrideNotes', 'Bukti transfer BCA via WhatsApp')
        ->call('saveOverride', true);

    expect($tree->refresh()->is_premium)->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=AdminTreesManagementTest`
Expected: FAIL (Route /admin/trees not found or 404)

- [ ] **Step 3: Register route and create admin view**

In `routes/web.php`, add `Route::view('trees', 'admin.trees')->name('trees');` under `Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(...)`.

Create `resources/views/admin/trees.blade.php`:
```blade
<x-layouts::admin :title="__('Manajemen Pohon Silsilah')">
    <livewire:admin-trees />
</x-layouts::admin>
```

Add navigation link in `resources/views/layouts/admin.blade.php` under Group 2 (Manajemen SaaS):
```blade
<flux:sidebar.item icon="cube" :href="route('admin.trees')" :current="request()->routeIs('admin.trees')" wire:navigate>
    {{ __('Pohon Silsilah') }}
</flux:sidebar.item>
```

- [ ] **Step 4: Implement ⚡admin-trees.blade.php component**

Create `resources/views/components/⚡admin-trees.blade.php`:
- Livewire Volt component with properties:
  - `$search = ''`
  - `$filter = 'all'` (`all`, `free`, `premium`, `public`, `private`)
  - `$selectedTreeId = null`
  - `$overrideReason = 'cash_cod'`
  - `$overrideNotes = ''`
  - `$targetIsPremium = true`
  - `$showOverrideModal = false`
- Query with pagination:
  - `FamilyTree::with(['users' => fn($q) => $q->where('family_tree_user.role', 'owner')])->withCount('members')`
  - Filters by search on `name`, `slug`, or owner `name`/`email`.
- Table rendering:
  - Name, slug, Owner avatar & name, Total Members count, Total Views count, Public/Private badge, Premium badge, Created date.
  - Actions: "Buka Pohon" link, and "Ubah Status / Override" button triggering modal.
- Override modal with reason dropdown ("Bayar Tunai / COD", "Transfer Bank Manual", "Kendala QRIS", "Kompensasi", "Lainnya") and notes.
- Method `saveOverride(bool $isPremium)` that executes dual logging (`ActivityLog` & `AdminActivityLog`) and flashes success.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=AdminTreesManagementTest`
Expected: PASS (3 passed, 7 assertions)

- [ ] **Step 6: Commit**

```bash
git add routes/web.php resources/views/admin/ resources/views/components/⚡admin-trees.blade.php resources/views/layouts/admin.blade.php tests/Feature/AdminTreesManagementTest.php
git commit -m "feat(admin): add centralized tree management panel and manual override controls"
```

---

### Task 5: End-to-End Verification, Code Styling (Pint), and Production Deployment

**Files:**
- Full test suite verification
- Code formatting across modified PHP files

- [ ] **Step 1: Run Pint code formatter**

```bash
vendor/bin/pint --dirty --format agent
```
Expected: Clean formatting matching Laravel standards.

- [ ] **Step 2: Run all tests**

```bash
php artisan test --compact
```
Expected: All tests pass (160+ passed, 0 failed).

- [ ] **Step 3: Git status & push to main**

```bash
git status
git push origin main
```

- [ ] **Step 4: Deploy to production server (10.133.21.24)**

Run:
```bash
ssh bpsdemak@10.133.21.24 "cd apps/muhshi/silsilah && git pull origin main && docker exec silsilah-franken php artisan migrate --force && docker exec silsilah-franken php artisan view:clear && docker exec silsilah-franken php artisan config:clear"
```
Expected: Deployment completed cleanly, migration ran, view cache cleared.

- [ ] **Step 5: Production Smoke Test**

Test public route, admin login, and tree settings modal on production container via curl.
