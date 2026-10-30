# Cross-Tree Member Reuse & Nuclear Family Cloning Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enable users to seamlessly reuse existing member profiles and clone entire nuclear family units (spouses & children) across multiple family trees without manual re-typing, while maintaining cross-tree lineage integrity.

**Architecture:**
1. **Source Tracking Column**: Add optional `source_member_id` and `source_tree_id` to the `members` table to track provenance and facilitate cross-tree syncing and reciprocal links.
2. **Cross-Tree Search & Profile Prefill**: In `MemberManager`, add a reactive search input allowing users to find and autofill any profile from their other owned trees (name, birth/death details, photo/avatar, contacts, address, bio).
3. **Nuclear Family Unit Cloning (Subtree Copy)**: When selecting a member from another tree, detect existing spouses and children with interactive checkboxes (`[x] Sertakan Pasangan`, `[x] Sertakan 2 Anak`). On submission, batch-insert the person, spouses, and children with proper parent/spouse relationships preserved.
4. **Cross-Tree Portal Badge**: In Member Detail modal, show a "Tercatat juga di Silsilah: [Nama Pohon Lain]" badge with a one-click navigation link to view that lineage.

**Tech Stack:** Laravel 13, Livewire 4, Flux UI, Alpine.js, Tailwind CSS v4, Pest PHP.

---

### File Structure Map

| File Path | Responsibility |
|---|---|
| `database/migrations/xxxx_xx_xx_add_source_member_to_members_table.php` | Add `source_member_id` and `source_tree_id` nullable foreign references |
| `app/Models/Member.php` | Define `sourceMember` and `sourceTree` relationships and clone helper methods |
| `resources/views/components/⚡member-manager.blade.php` | UI for searching other trees, auto-filling profile, and batch-cloning spouses/children |
| `resources/views/components/⚡member-manager.blade.php` (detail view) | Cross-tree link badge in member profile viewer |
| `tests/Feature/CrossTreeCloningTest.php` | Feature tests for cross-tree profile reuse, nuclear family cloning, and permission checks |

---

### Task 1: Database Migration for Cross-Tree Tracking

**Files:**
- Create: Migration `add_source_tree_and_member_to_members_table`
- Modify: `app/Models/Member.php`

- [ ] **Step 1: Create migration**

Run: `php artisan make:migration add_source_tree_and_member_to_members_table --no-interaction`

```php
public function up(): void
{
    Schema::table('members', function (Blueprint $table) {
        $table->foreignId('source_tree_id')->nullable()->constrained('family_trees')->nullOnDelete();
        $table->foreignId('source_member_id')->nullable()->constrained('members')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('members', function (Blueprint $table) {
        $table->dropForeign(['source_tree_id']);
        $table->dropForeign(['source_member_id']);
        $table->dropColumn(['source_tree_id', 'source_member_id']);
    });
}
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`

- [ ] **Step 3: Update `app/Models/Member.php`**

Add `source_tree_id` and `source_member_id` to `$fillable`. Add relationships:
```php
public function sourceTree(): BelongsTo
{
    return $this->belongsTo(FamilyTree::class, 'source_tree_id');
}

public function sourceMember(): BelongsTo
{
    return $this->belongsTo(Member::class, 'source_member_id');
}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/* app/Models/Member.php
git commit -m "feat(tree): add source_tree_id and source_member_id to members table"
```

---

### Task 2: Implement Cross-Tree Search & Cloning Backend in `MemberManager`

**Files:**
- Modify: `resources/views/components/⚡member-manager.blade.php`
- Test: `tests/Feature/CrossTreeCloningTest.php`

- [ ] **Step 1: Write failing test for cross-tree candidate search and profile prefill**

Create `tests/Feature/CrossTreeCloningTest.php`:
```php
<?php

use App\Models\User;
use App\Models\FamilyTree;
use App\Models\Member;
use Livewire\Livewire;

test('user can search members from other owned trees and prefill form', function () {
    $user = User::factory()->create();
    $treeA = FamilyTree::factory()->create(['name' => 'Bani Ayah']);
    $treeB = FamilyTree::factory()->create(['name' => 'Bani Ibu']);
    $treeA->users()->attach($user->id, ['role' => 'owner']);
    $treeB->users()->attach($user->id, ['role' => 'owner']);

    $person = Member::factory()->create([
        'family_tree_id' => $treeA->id,
        'first_name' => 'Ahmad',
        'last_name' => 'Muhibbin',
        'gender' => 'male',
        'birth_place' => 'Demak',
        'birth_date' => '1990-05-12',
    ]);

    $this->actingAs($user);

    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->set('searchSourceQuery', 'Ahmad')
        ->assertSee('Ahmad Muhibbin')
        ->assertSee('Bani Ayah')
        ->call('selectSourceMember', $person->id)
        ->assertSet('first_name', 'Ahmad')
        ->assertSet('last_name', 'Muhibbin')
        ->assertSet('gender', 'male')
        ->assertSet('birth_place', 'Demak')
        ->assertSet('birth_date', '1990-05-12')
        ->assertSet('selectedSourceMemberId', $person->id);
});
```

- [ ] **Step 2: Run test to verify failure**

Run: `php artisan test --compact tests/Feature/CrossTreeCloningTest.php`
Expected: FAIL due to missing properties and method.

- [ ] **Step 3: Implement search and prefill logic in `MemberManager`**

In `resources/views/components/⚡member-manager.blade.php`:
```php
public $searchSourceQuery = '';
public $selectedSourceMemberId = null;
public $includeSpouses = true;
public $includeChildren = true;
public $sourceSpouseList = [];
public $sourceChildList = [];

public function selectSourceMember($memberId)
{
    $member = Member::with(['spouses', 'children'])->find($memberId);
    if (! $member) return;

    $userTreeIds = auth()->user()->familyTrees()->pluck('family_trees.id');
    if (! $userTreeIds->contains($member->family_tree_id)) {
        abort(403);
    }

    $this->selectedSourceMemberId = $member->id;
    $this->first_name = $member->first_name;
    $this->last_name = $member->last_name;
    $this->gender = $member->gender;
    $this->is_living = (bool) $member->is_living;
    $this->birth_date = $member->birth_date ? $member->birth_date->format('Y-m-d') : '';
    $this->death_date = $member->death_date ? $member->death_date->format('Y-m-d') : '';
    $this->birth_place = $member->birth_place;
    $this->address = $member->address;
    $this->profession = $member->profession;
    $this->bio = $member->bio;
    $this->photo_url = $member->photo_url;
    $this->avatar_id = $member->avatar_id;

    // Load available family unit
    $this->sourceSpouseList = $member->spouses->map(fn($s) => ['id' => $s->id, 'name' => trim($s->first_name . ' ' . $s->last_name)])->toArray();
    $this->sourceChildList = $member->children->map(fn($c) => ['id' => $c->id, 'name' => trim($c->first_name . ' ' . $c->last_name)])->toArray();
}
```

- [ ] **Step 4: Implement Nuclear Family Batch Copy on Save**

When `$selectedSourceMemberId` is set and `$includeSpouses` / `$includeChildren` is true:
```php
// After saving the main member into current tree:
if ($this->selectedSourceMemberId) {
    $source = Member::with(['spouses', 'children'])->find($this->selectedSourceMemberId);
    if ($source) {
        $member->update([
            'source_tree_id' => $source->family_tree_id,
            'source_member_id' => $source->id,
        ]);

        $createdSpouses = [];
        if ($this->includeSpouses) {
            foreach ($source->spouses as $sp) {
                $newSpouse = $sp->replicate([
                    'family_tree_id', 'father_id', 'mother_id'
                ]);
                $newSpouse->family_tree_id = $this->treeId;
                $newSpouse->source_tree_id = $sp->family_tree_id;
                $newSpouse->source_member_id = $sp->id;
                $newSpouse->save();

                // Connect marriage relation
                Relationship::create([
                    'family_tree_id' => $this->treeId,
                    'husband_id' => $member->gender === 'male' ? $member->id : $newSpouse->id,
                    'wife_id' => $member->gender === 'female' ? $member->id : $newSpouse->id,
                    'is_current' => true,
                ]);
                $createdSpouses[$sp->id] = $newSpouse->id;
            }
        }

        if ($this->includeChildren) {
            foreach ($source->children as $child) {
                $newChild = $child->replicate([
                    'family_tree_id', 'father_id', 'mother_id'
                ]);
                $newChild->family_tree_id = $this->treeId;
                $newChild->source_tree_id = $child->family_tree_id;
                $newChild->source_member_id = $child->id;

                if ($member->gender === 'male') {
                    $newChild->father_id = $member->id;
                    $newChild->mother_id = $createdSpouses[$child->mother_id] ?? null;
                } else {
                    $newChild->mother_id = $member->id;
                    $newChild->father_id = $createdSpouses[$child->father_id] ?? null;
                }
                $newChild->save();
            }
        }
    }
}
```

- [ ] **Step 5: Run tests and ensure pass**

Run: `php artisan test --compact tests/Feature/CrossTreeCloningTest.php`

---

### Task 3: Build the UI for Cross-Tree Search & Subtree Selection

**Files:**
- Modify: `resources/views/components/⚡member-manager.blade.php`

- [ ] **Step 1: Add "Ambil Profil dari Silsilah Lain" accordion/collapsible in Create Member Modal**

Above the personal info inputs:
```blade
@if(!$memberId)
    <div x-data="{ openSearch: false }" class="p-3 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-800/40 rounded-2xl mb-4">
        <div class="flex items-center justify-between cursor-pointer" @click="openSearch = !openSearch">
            <div class="flex items-center gap-2 text-xs font-bold text-emerald-800 dark:text-emerald-300">
                <span class="text-sm">⚡</span>
                <span>Punya data ini di silsilah lain? Salin otomatis di sini</span>
            </div>
            <button type="button" class="text-xs text-emerald-600 font-semibold underline">
                <span x-text="openSearch ? 'Tutup' : 'Cari Profil'"></span>
            </button>
        </div>

        <div x-show="openSearch" x-collapse class="mt-3 space-y-2">
            <flux:input wire:model.live.debounce.300ms="searchSourceQuery" placeholder="Ketik nama anggota di silsilah lain..." icon="magnifying-glass" size="sm" />
            
            {{-- Search results dropdown list --}}
            @if(strlen($searchSourceQuery) >= 2)
                <div class="max-h-40 overflow-y-auto space-y-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl p-1.5 shadow-sm">
                    @forelse($this->sourceMemberCandidates as $candidate)
                        <div wire:click="selectSourceMember({{ $candidate->id }})" class="p-2 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg cursor-pointer flex items-center justify-between text-xs transition-colors">
                            <div>
                                <span class="font-bold text-zinc-900 dark:text-white">{{ $candidate->first_name }} {{ $candidate->last_name }}</span>
                                <span class="text-[10px] text-zinc-500 block">Silsilah: {{ $candidate->familyTree->name }}</span>
                            </div>
                            <span class="text-[10px] bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 px-2 py-0.5 rounded-full font-bold">Pilih</span>
                        </div>
                    @empty
                        <div class="text-xs text-zinc-400 p-2 text-center">Tidak ditemukan di silsilah lain</div>
                    @endforelse
                </div>
            @endif

            {{-- Selected Source Banner & Nuclear Family Checkboxes --}}
            @if($selectedSourceMemberId)
                <div class="p-2.5 bg-white dark:bg-zinc-900 rounded-xl border border-emerald-300 dark:border-emerald-700 mt-2 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-emerald-700 dark:text-emerald-300 font-bold">✓ Profil berhasil diisi otomatis</span>
                        <button type="button" wire:click="clearSourceMember" class="text-[10px] text-zinc-400 hover:text-red-500">Batal / Reset</button>
                    </div>

                    @if(!empty($sourceSpouseList) || !empty($sourceChildList))
                        <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 space-y-1.5">
                            <div class="text-[11px] font-bold text-zinc-700 dark:text-zinc-300">Salin Cabang Keluarga Sekaligus:</div>
                            @if(!empty($sourceSpouseList))
                                <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-300 cursor-pointer">
                                    <input type="checkbox" wire:model="includeSpouses" class="rounded text-emerald-600">
                                    <span>Salin Pasangan: <strong>{{ implode(', ', array_column($sourceSpouseList, 'name')) }}</strong></span>
                                </label>
                            @endif
                            @if(!empty($sourceChildList))
                                <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-300 cursor-pointer">
                                    <input type="checkbox" wire:model="includeChildren" class="rounded text-emerald-600">
                                    <span>Salin {{ count($sourceChildList) }} Anak: <strong>{{ implode(', ', array_column($sourceChildList, 'name')) }}</strong></span>
                                </label>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif
```

- [ ] **Step 2: Add Cross-Tree Link Badge in Member Detail Profile Viewer**

In the view mode of `⚡member-manager.blade.php`:
```blade
@if($viewMember->sourceTree)
    <div class="mt-3 p-2.5 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl border border-zinc-200 dark:border-zinc-700 flex items-center justify-between text-xs">
        <div class="flex items-center gap-2">
            <span class="text-sm">🌳</span>
            <div>
                <span class="text-[10px] text-zinc-500 block">Juga tercatat di silsilah lain:</span>
                <span class="font-bold text-zinc-900 dark:text-white">{{ $viewMember->sourceTree->name }}</span>
            </div>
        </div>
        <a href="{{ route('tree.show', $viewMember->source_tree_id) }}" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
            Buka Silsilah ➔
        </a>
    </div>
@endif
```

---

### Task 4: Comprehensive Feature Testing & Verification

**Files:**
- Modify: `tests/Feature/CrossTreeCloningTest.php`

- [ ] **Step 1: Write Pest test for cloning nuclear family (spouses and children)**

```php
test('user can clone person along with spouse and children into another tree in one action', function () {
    $user = User::factory()->create();
    $treeA = FamilyTree::factory()->create(['name' => 'Bani Ayah']);
    $treeB = FamilyTree::factory()->create(['name' => 'Bani Ibu']);
    $treeA->users()->attach($user->id, ['role' => 'owner']);
    $treeB->users()->attach($user->id, ['role' => 'owner']);

    $husband = Member::factory()->create(['family_tree_id' => $treeA->id, 'first_name' => 'Budi', 'gender' => 'male']);
    $wife = Member::factory()->create(['family_tree_id' => $treeA->id, 'first_name' => 'Siti', 'gender' => 'female']);
    Relationship::create([
        'family_tree_id' => $treeA->id,
        'husband_id' => $husband->id,
        'wife_id' => $wife->id,
        'is_current' => true,
    ]);

    $child = Member::factory()->create([
        'family_tree_id' => $treeA->id,
        'first_name' => 'Doni',
        'gender' => 'male',
        'father_id' => $husband->id,
        'mother_id' => $wife->id,
    ]);

    $this->actingAs($user);

    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('selectSourceMember', $husband->id)
        ->set('includeSpouses', true)
        ->set('includeChildren', true)
        ->call('saveMember');

    // Assert Tree B now has all 3 members linked together
    expect(Member::where('family_tree_id', $treeB->id)->count())->toBe(3);
    
    $newHusband = Member::where('family_tree_id', $treeB->id)->where('first_name', 'Budi')->first();
    $newWife = Member::where('family_tree_id', $treeB->id)->where('first_name', 'Siti')->first();
    $newChild = Member::where('family_tree_id', $treeB->id)->where('first_name', 'Doni')->first();

    expect($newHusband)->not->toBeNull();
    expect($newWife)->not->toBeNull();
    expect($newChild)->not->toBeNull();
    expect($newChild->father_id)->toBe($newHusband->id);
    expect($newChild->mother_id)->toBe($newWife->id);
});
```

- [ ] **Step 2: Run all tests**

```bash
php artisan test --compact tests/Feature/CrossTreeCloningTest.php
php artisan test --compact
```

- [ ] **Step 3: Run Pint Formatter**

```bash
vendor/bin/pint --dirty --format agent
```

---

### Task 5: Deployment & Review

- [ ] **Step 1: Commit and push changes**
- [ ] **Step 2: Deploy to server via SSH (`deploy.sh`)**
- [ ] **Step 3: Verify live on server**
