# Critical Security IDOR & Data Integrity Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Eliminate the critical Cross-Tree IDOR security vulnerability in MemberManager, enforce chronological and parent relationship validations, and apply rate limiting to sensitive routes while maintaining 100% test pass rate.

**Architecture:** Strengthen authorization enforcement in Livewire Single-File Components by scoping all entity queries (Member, Marriage) to the authorized `FamilyTree` instance. Apply Laravel form request validation rules for date chronology (`after_or_equal`) and parent differentiation (`different`), and attach throttling middleware to public endpoints.

**Tech Stack:** Laravel 13, Livewire 4, Pest PHP v4, Laravel Pint.

---

### Task 1: Security & Data Integrity Test Suite (Failing Tests)

**Files:**
- Create: `tests/Feature/SecurityIdorAndDataIntegrityTest.php`

- [x] **Step 1: Write the failing tests for IDOR and Data Integrity**

```php
<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

test('user cannot update a member belonging to another tree via member manager (IDOR prevention)', function () {
    $userA = User::factory()->create();
    $treeA = FamilyTree::factory()->create();
    $treeA->users()->attach($userA->id, ['role' => 'owner']);

    $userB = User::factory()->create();
    $treeB = FamilyTree::factory()->create();
    $treeB->users()->attach($userB->id, ['role' => 'owner']);

    $victimMember = Member::factory()->create([
        'family_tree_id' => $treeB->id,
        'first_name' => 'Victim',
        'last_name' => 'Original',
        'gender' => 'male',
    ]);

    expect(function () use ($userA, $treeA, $victimMember) {
        Livewire::actingAs($userA)
            ->test('⚡member-manager', ['treeId' => $treeA->id])
            ->set('memberId', $victimMember->id)
            ->set('first_name', 'Hacked Name')
            ->set('last_name', 'Hacked')
            ->set('gender', 'male')
            ->call('save');
    })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($victimMember->fresh()->first_name)->toBe('Victim');
    expect($victimMember->fresh()->family_tree_id)->toBe($treeB->id);
});

test('user cannot delete a member belonging to another tree via member manager (IDOR prevention)', function () {
    $userA = User::factory()->create();
    $treeA = FamilyTree::factory()->create();
    $treeA->users()->attach($userA->id, ['role' => 'owner']);

    $userB = User::factory()->create();
    $treeB = FamilyTree::factory()->create();
    $treeB->users()->attach($userB->id, ['role' => 'owner']);

    $victimMember = Member::factory()->create([
        'family_tree_id' => $treeB->id,
        'first_name' => 'Victim',
        'gender' => 'female',
    ]);

    expect(function () use ($userA, $treeA, $victimMember) {
        Livewire::actingAs($userA)
            ->test('⚡member-manager', ['treeId' => $treeA->id])
            ->set('memberId', $victimMember->id)
            ->call('deleteMember');
    })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Member::find($victimMember->id))->not->toBeNull();
});

test('user cannot delete or update a marriage belonging to another tree (IDOR prevention)', function () {
    $userA = User::factory()->create();
    $treeA = FamilyTree::factory()->create();
    $treeA->users()->attach($userA->id, ['role' => 'owner']);

    $userB = User::factory()->create();
    $treeB = FamilyTree::factory()->create();
    $treeB->users()->attach($userB->id, ['role' => 'owner']);

    $husband = Member::factory()->create(['family_tree_id' => $treeB->id, 'gender' => 'male']);
    $wife = Member::factory()->create(['family_tree_id' => $treeB->id, 'gender' => 'female']);
    $marriage = Marriage::create([
        'husband_id' => $husband->id,
        'wife_id' => $wife->id,
        'is_current' => true,
    ]);

    expect(function () use ($userA, $treeA, $marriage) {
        Livewire::actingAs($userA)
            ->test('⚡member-manager', ['treeId' => $treeA->id])
            ->call('deleteMarriage', $marriage->id);
    })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Marriage::find($marriage->id))->not->toBeNull();
});

test('saving member with death date earlier than birth date fails validation', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    Livewire::actingAs($user)
        ->test('⚡member-manager', ['treeId' => $tree->id])
        ->set('first_name', 'Invalid Chronology')
        ->set('gender', 'male')
        ->set('birth_date', '2000-01-01')
        ->set('death_date', '1990-01-01')
        ->set('is_living', false)
        ->call('save')
        ->assertHasErrors(['death_date']);
});

test('setting is_living true clears death date upon save', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $member = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Living Test',
        'gender' => 'male',
        'is_living' => false,
        'death_date' => '2020-01-01',
    ]);

    Livewire::actingAs($user)
        ->test('⚡member-manager', ['treeId' => $tree->id])
        ->set('memberId', $member->id)
        ->set('first_name', 'Living Test')
        ->set('gender', 'male')
        ->set('is_living', true)
        ->set('death_date', '2020-01-01')
        ->call('save')
        ->assertHasNoErrors();

    expect($member->fresh()->death_date)->toBeNull();
    expect($member->fresh()->is_living)->toBeTrue();
});

test('setting father_id same as mother_id fails validation', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $parent = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Single Parent',
        'gender' => 'male',
    ]);

    Livewire::actingAs($user)
        ->test('⚡member-manager', ['treeId' => $tree->id])
        ->set('first_name', 'Child')
        ->set('gender', 'female')
        ->set('father_id', $parent->id)
        ->set('mother_id', $parent->id)
        ->call('save')
        ->assertHasErrors(['father_id']);
});
```

- [x] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test --filter=SecurityIdorAndDataIntegrityTest --compact
```
Expected: FAIL (IDOR allows cross-tree update/delete, and death_date validation is missing).

---

### Task 2: Implement Cross-Tree IDOR Remediation in Member Manager

**Files:**
- Modify: `resources/views/components/⚡member-manager.blade.php:640-655`
- Modify: `resources/views/components/⚡member-manager.blade.php:769-835`

- [x] **Step 1: Scope member updates to authorized tree**

In `resources/views/components/⚡member-manager.blade.php` inside `save()`:
Replace:
```php
        if ($this->memberId) {
            $member = Member::findOrFail($this->memberId);
            $member->update($data);
```
With:
```php
        if ($this->memberId) {
            $member = $tree->members()->findOrFail($this->memberId);
            $member->update($data);
```

- [x] **Step 2: Scope member deletion to authorized tree**

In `deleteMember()`:
Replace:
```php
    public function deleteMember()
    {
        \Illuminate\Support\Facades\Gate::authorize('editMembers', FamilyTree::findOrFail($this->treeId));
        if ($this->memberId) {
            $member = Member::find($this->memberId);
            if ($member) {
                $name = "{$member->first_name} {$member->last_name}";
                Marriage::where('husband_id', $this->memberId)->orWhere('wife_id', $this->memberId)->delete();
                $member->delete();
```
With:
```php
    public function deleteMember()
    {
        $tree = FamilyTree::findOrFail($this->treeId);
        \Illuminate\Support\Facades\Gate::authorize('editMembers', $tree);
        if ($this->memberId) {
            $member = $tree->members()->findOrFail($this->memberId);
            $name = "{$member->first_name} {$member->last_name}";
            Marriage::where('husband_id', $this->memberId)->orWhere('wife_id', $this->memberId)->delete();
            $member->delete();
```

- [x] **Step 3: Scope marriage update and deletion methods to authorized tree**

In `updateMarriageStatus()`, `updateMarriageDate()`, and `deleteMarriage()`:
Replace global `Marriage::findOrFail($marriageId)` with:
```php
        $marriage = Marriage::where(function ($query) use ($tree) {
            $query->whereHas('husband', fn ($h) => $h->where('family_tree_id', $tree->id))
                  ->orWhereHas('wife', fn ($w) => $w->where('family_tree_id', $tree->id));
        })->findOrFail($marriageId);
```

- [x] **Step 4: Run Pest IDOR tests to verify passes**

Run:
```bash
php artisan test --filter=test_user_cannot --compact
```
Expected: PASS (All 3 IDOR tests pass).

---

### Task 3: Implement Chronological & Relational Validations in Member Manager

**Files:**
- Modify: `resources/views/components/⚡member-manager.blade.php:550-595`

- [x] **Step 1: Add validation rules for death_date and father_id differentiation**

In `save()` of `resources/views/components/⚡member-manager.blade.php`:
Replace:
```php
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'gender' => 'required|in:male,female',
            'order' => 'nullable|integer|min:1|max:999',
            'is_living' => 'boolean',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
            'father_id' => 'nullable|exists:members,id',
            'mother_id' => 'nullable|exists:members,id',
        ]);
```
With:
```php
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'gender' => 'required|in:male,female',
            'order' => 'nullable|integer|min:1|max:999',
            'is_living' => 'boolean',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date|after_or_equal:birth_date',
            'photo' => 'nullable|image|max:2048',
            'father_id' => 'nullable|exists:members,id|different:mother_id',
            'mother_id' => 'nullable|exists:members,id',
        ]);
```

- [x] **Step 2: Auto-clear death_date when is_living is true**

In `$data` array construction inside `save()`:
Replace:
```php
            'death_date' => $this->death_date ?: null,
```
With:
```php
            'death_date' => ($this->is_living || ! $this->death_date) ? null : $this->death_date,
```

- [x] **Step 3: Run full Security and Data Integrity tests**

Run:
```bash
php artisan test --filter=SecurityIdorAndDataIntegrityTest --compact
```
Expected: PASS (All 6 tests pass).

---

### Task 4: Implement Rate Limiting on Sensitive Public Endpoints

**Files:**
- Modify: `routes/web.php`
- Create: `tests/Feature/RateLimitingTest.php`

- [x] **Step 1: Write rate limiting feature test**

Create `tests/Feature/RateLimitingTest.php`:
```php
<?php

test('image proxy endpoint enforces rate limiting', function () {
    for ($i = 0; $i < 60; $i++) {
        $this->get(route('image.proxy', ['url' => 'https://example.com/test.jpg']));
    }

    $response = $this->get(route('image.proxy', ['url' => 'https://example.com/test.jpg']));
    $response->assertStatus(429);
});
```

- [x] **Step 2: Add throttle middleware in routes/web.php**

In `routes/web.php`:
Replace:
```php
Route::get('api/image-proxy', [ImageProxyController::class, 'proxy'])->name('image.proxy');
```
With:
```php
Route::get('api/image-proxy', [ImageProxyController::class, 'proxy'])
    ->middleware('throttle:60,1')
    ->name('image.proxy');
```
And add `middleware('throttle:10,1')` on `orders/checkout` and `orders/validate-code`.

- [x] **Step 3: Run RateLimitingTest**

Run:
```bash
php artisan test --filter=RateLimitingTest --compact
```
Expected: PASS.

---

### Task 5: Final Code Formatting & Complete Regression Suite

**Files:**
- All modified files

- [x] **Step 1: Run Laravel Pint on modified files**

Run:
```bash
vendor/bin/pint --dirty --format agent
```
Expected: All files conform to coding standards.

- [x] **Step 2: Run complete test suite to ensure zero regressions**

Run:
```bash
php artisan test --compact
```
Expected: 294+ passed (all original 287 tests + 7 new security/integrity tests pass).

- [x] **Step 3: Commit changes**

Run:
```bash
git add .
git commit -m "fix(security): resolve cross-tree IDOR, add date chronology checks, and throttle sensitive endpoints"
```
Expected: Clean commit on working branch.

---

## Self-Review Checklist

- [x] **Spec coverage:** Solves SEC-01 (IDOR), BUG-01 (Chronological Dates), BUG-02 (Parent Validation), and SEC-02 (Rate Limiting).
- [x] **No placeholders:** Exact code blocks, exact line anchors, exact expected assertions.
- [x] **TDD workflow:** Test written first (Task 1), minimal fix implemented (Tasks 2-4), verification executed (Task 5).
