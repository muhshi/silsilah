# Tree UX Optimization & Marriage Status Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the unclickable `+` button bug in Focus Explorer, prevent canvas pan/zoom from resetting to center when saving members, add marriage status support (Menikah, Cerai Hidup, Cerai Mati) with full editing capabilities, and streamline canvas node hover actions.

**Architecture:** 
1. Parameter normalization in `MemberManager` Livewire component to handle both PHP named arguments and JavaScript object event payloads seamlessly.
2. Viewport preservation in Alpine.js `canvasTree` state during Livewire DOM morphing so users don't lose their canvas panning position after saving.
3. Enhanced marriage relations management in `MemberManager` allowing users to select marriage status (Menikah, Cerai Hidup, Cerai Mati) upon creation and edit existing spouses/marriages in the edit modal.
4. Clean consolidation of canvas node add buttons to reduce visual clutter.

**Tech Stack:** Laravel 13, Livewire 4, Flux UI v2, Alpine.js, Tailwind CSS v4, Pest PHP.

---

### Task 1: Fix Parameter Normalization in `MemberManager` & Safe Dispatch in Focus Explorer

**Files:**
- Modify: `resources/views/components/⚡member-manager.blade.php:100-125`
- Modify: `resources/views/livewire/tree-vertical.blade.php:975-995`
- Test: `tests/Feature/TreeFeaturesTest.php`

- [ ] **Step 1: Write failing Pest test for array event payload in `create-member`**

Add test to `tests/Feature/TreeFeaturesTest.php`:
```php
it('handles create-member event with array payload from alpine dispatch', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);
    $parent = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male']);

    $this->actingAs($user);

    // Dispatched as single array payload (mimics JS $dispatch('create-member', { targetId: 1, relType: 'child_of' }))
    Livewire::test('member-manager', ['treeId' => $tree->id])
        ->dispatch('create-member', ['targetId' => $parent->id, 'relType' => 'child_of'])
        ->assertSet('targetMemberId', $parent->id)
        ->assertSet('relType', 'child_of');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="handles create-member event with array payload"`
Expected: FAIL because `$targetMemberId` is assigned the entire array `['targetId' => ..., 'relType' => ...]`.

- [ ] **Step 3: Implement parameter normalization in `createMember`**

Update `resources/views/components/⚡member-manager.blade.php`:
```php
    #[On('create-member')]
    public function createMember($targetId = null, $relType = 'child_of')
    {
        // Normalize array payload if dispatched from Alpine JS ($dispatch('create-member', { targetId: X, relType: Y }))
        if (is_array($targetId)) {
            $relType = $targetId['relType'] ?? $targetId['rel_type'] ?? 'child_of';
            $targetId = $targetId['targetId'] ?? $targetId['target_id'] ?? null;
        }

        $this->resetForm();
        $this->targetMemberId = $targetId ? (int) $targetId : null;
        $this->relType = $relType;
        $this->is_living = true;
        $this->gender = 'male';

        $this->autoDetectGender();

        if ($this->relType === 'child_of' && $this->targetMemberId) {
            $spouses = $this->getTargetSpouses();
            if ($spouses->isNotEmpty()) {
                $this->other_parent_id = $spouses->first()->id;
            }
        }

        Flux::modal('member-modal')->show();
    }
```

Also update `resources/views/livewire/tree-vertical.blade.php:980` to safely handle null `$focusMember`:
```blade
<button wire:click="$dispatch('create-member', { targetId: {{ $focusMember?->id ?? 'null' }}, relType: 'child_of' })"
        class="flex-1 flex flex-col items-center justify-center gap-1 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
    <span class="text-lg font-bold">➕</span>
    <span class="text-[11px]">Tambah</span>
</button>
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter="handles create-member event with array payload"`
Expected: PASS

- [ ] **Step 5: Commit changes**

```bash
git add resources/views/components/⚡member-manager.blade.php resources/views/livewire/tree-vertical.blade.php tests/Feature/TreeFeaturesTest.php
git commit -m "fix(tree): resolve unhandled array payload in createMember event and safe dispatch in focus mode"
```

---

### Task 2: Preserve Canvas Pan & Zoom Viewport on Refresh

**Files:**
- Modify: `resources/views/livewire/tree-view.blade.php:680-735`
- Test: Manual browser check & existing canvas tests

- [ ] **Step 1: Update `canvasTree` state in `tree-view.blade.php` to preserve viewport**

In `resources/views/livewire/tree-view.blade.php`, update `Alpine.data('canvasTree', ...)`:
Add `hasUserMoved: false` and check before centering:
```javascript
        hasUserMoved: false,

        init() {
            // Only auto-center on initial mount if user hasn't panned or zoomed
            if (!this.hasUserMoved && this.panX === 0 && this.panY === 0) {
                this.$nextTick(() => {
                    setTimeout(() => this.centerTree(), 100);
                });
            }
            window.addEventListener('resize', () => {
                if (!this.hasUserMoved) {
                    this.centerTree();
                }
            });
        },

        centerTree() {
            const container = this.$refs.container;
            const inner = this.$refs.inner;
            if (!container || !inner) return;

            const treeEl = document.getElementById('myTree') || inner;
            const treeW = treeEl.scrollWidth || inner.scrollWidth || 800;
            const treeH = treeEl.scrollHeight || inner.scrollHeight || 600;
            const containerW = container.clientWidth || window.innerWidth;
            const containerH = container.clientHeight || 600;

            const marginX = containerW < 768 ? 20 : 60;
            const marginY = 40;

            const scaleX = (containerW - marginX) / treeW;
            const scaleY = (containerH - marginY) / treeH;
            
            let optimalScale = Math.min(1.0, Math.min(scaleX, scaleY));
            if (containerW < 768) {
                optimalScale = Math.min(0.85, Math.max(0.35, scaleX));
            } else {
                optimalScale = Math.min(1.0, Math.max(0.45, optimalScale));
            }

            this.scale = parseFloat(optimalScale.toFixed(2));
            this.panX = Math.round((containerW - (treeW * this.scale)) / 2);
            this.panY = 24;
        },

        startDrag(e) {
            if (e.target.closest('a') || e.target.closest('button') || e.target.closest('.pt-zoom-controls')) return;
            this.dragging = true;
            this.hasUserMoved = true;
            this.startX = e.clientX - this.panX;
            this.startY = e.clientY - this.panY;
        },
```
Also in `doZoom(e)` and `startTouch(e)`, set `this.hasUserMoved = true`.
And in `resetView()`:
```javascript
        resetView() {
            this.hasUserMoved = false;
            this.centerTree();
        },
```

- [ ] **Step 2: Run Pint formatter on modified view**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Commit changes**

```bash
git add resources/views/livewire/tree-view.blade.php
git commit -m "fix(canvas): prevent canvas viewport reset to center when saving members"
```

---

### Task 3: Marriage Status & Spouse Management (Menikah, Cerai Hidup, Cerai Mati)

**Files:**
- Modify: `resources/views/components/⚡member-manager.blade.php`
- Modify: `resources/views/components/tree-node.blade.php`
- Modify: `resources/views/components/simple-tree-node.blade.php`
- Modify: `resources/views/livewire/tree-vertical.blade.php`
- Test: `tests/Feature/TreeFeaturesTest.php`

- [ ] **Step 1: Write failing Pest test for updating marriage status to Cerai Hidup and Cerai Mati**

Add test to `tests/Feature/TreeFeaturesTest.php`:
```php
it('can update existing marriage status to divorced or deceased in member manager', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $husband = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Budi']);
    $wife = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'first_name' => 'Siti']);

    $marriage = Marriage::create([
        'husband_id' => $husband->id,
        'wife_id' => $wife->id,
        'is_current' => true,
    ]);

    $this->actingAs($user);

    // Call updateMarriageStatus action
    Livewire::test('member-manager', ['treeId' => $tree->id])
        ->call('updateMarriageStatus', $marriage->id, false)
        ->assertDispatched('refresh-tree');

    expect($marriage->fresh()->is_current)->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="can update existing marriage status to divorced"`
Expected: FAIL (`updateMarriageStatus` method not found).

- [ ] **Step 3: Implement marriage management methods in `⚡member-manager.blade.php`**

In `⚡member-manager.blade.php`:
1. Add property `public $marriage_status = 'married';` (`'married'`, `'divorced'`, `'widowed'`).
2. In `createMember`: if `$relType === 'ex_of'`, set `$this->marriage_status = 'divorced'`; otherwise `'married'`.
3. In `save()`:
   When creating marriage:
   ```php
   Marriage::create([
       'husband_id' => $husbandId,
       'wife_id' => $wifeId,
       'marriage_date' => $this->marriage_date ?: null,
       'is_current' => $this->marriage_status === 'married',
   ]);
   ```
4. Add method `updateMarriageStatus(int $marriageId, bool $isCurrent)`:
   ```php
   public function updateMarriageStatus($marriageId, $isCurrent)
   {
       $tree = FamilyTree::findOrFail($this->treeId);
       \Illuminate\Support\Facades\Gate::authorize('editMembers', $tree);

       $marriage = Marriage::findOrFail($marriageId);
       $marriage->update(['is_current' => (bool) $isCurrent]);

       $statusLabel = $isCurrent ? 'Menikah (Aktif)' : 'Cerai';
       \App\Models\ActivityLog::log(
           $this->treeId,
           'marriage_updated',
           "Mengubah status pernikahan menjadi {$statusLabel}"
       );

       session()->flash('success', 'Status pernikahan berhasil diperbarui.');
       $this->dispatch('refresh-tree');
   }

   public function deleteMarriage($marriageId)
   {
       $tree = FamilyTree::findOrFail($this->treeId);
       \Illuminate\Support\Facades\Gate::authorize('editMembers', $tree);

       $marriage = Marriage::findOrFail($marriageId);
       $marriage->delete();

       \App\Models\ActivityLog::log(
           $this->treeId,
           'marriage_deleted',
           "Menghapus relasi pernikahan"
       );

       session()->flash('success', 'Relasi pernikahan berhasil dihapus.');
       $this->dispatch('refresh-tree');
   }
   ```
5. In `editMember($id)`:
   Load the member's existing marriages into view state so the edit modal can display:
   - List of spouses
   - Badge: Menikah (Aktif) / Cerai Hidup / Cerai Mati
   - Toggle buttons: Set "Menikah" / Set "Cerai Hidup"
   - Button: "Hapus Relasi Pasangan"

- [ ] **Step 4: Update Blade view in `⚡member-manager.blade.php`**

Inside `member-modal`:
1. In creation mode (`!$memberId && in_array($relType, ['spouse_of', 'ex_of'])`):
   Display `Status Pernikahan`:
   ```blade
   <flux:radio.group wire:model="marriage_status" label="Status Pernikahan">
       <flux:radio value="married" label="Menikah (Aktif)" />
       <flux:radio value="divorced" label="Cerai Hidup" />
       <flux:radio value="widowed" label="Cerai Mati" />
   </flux:radio.group>
   ```
2. In edit mode (`$memberId`):
   Display existing spouses section:
   ```blade
   @if($existingMarriages->isNotEmpty())
       <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-2">
           <label class="text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2 block">Daftar Pasangan & Status Hubungan</label>
           <div class="space-y-2">
               @foreach($existingMarriages as $m)
                   @php
                       $sp = $m->husband_id == $memberId ? $m->wife : $m->husband;
                       $isDivorced = !$m->is_current;
                       $isDeceasedSpouse = $sp && !$sp->is_living;
                   @endphp
                   @if($sp)
                       <div class="flex items-center justify-between p-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                           <div class="flex items-center gap-2">
                               <span class="text-sm">💍</span>
                               <div>
                                   <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ $sp->first_name }} {{ $sp->last_name }}</div>
                                   <div class="text-[10px] text-zinc-500">
                                       @if($isDeceasedSpouse && $isDivorced)
                                           <span class="text-zinc-600 dark:text-zinc-400 font-semibold">Cerai Mati (Wafat)</span>
                                       @elseif($isDivorced)
                                           <span class="text-amber-600 dark:text-amber-400 font-semibold">Cerai Hidup</span>
                                       @else
                                           <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Menikah (Aktif)</span>
                                       @endif
                                       @if($m->marriage_date) • {{ \Carbon\Carbon::parse($m->marriage_date)->format('d M Y') }} @endif
                                   </div>
                               </div>
                           </div>
                           <div class="flex items-center gap-1.5">
                               @if($m->is_current)
                                   <flux:button size="xs" variant="subtle" wire:click="updateMarriageStatus({{ $m->id }}, false)">
                                       Ubah ke Cerai
                                   </flux:button>
                               @else
                                   <flux:button size="xs" variant="subtle" wire:click="updateMarriageStatus({{ $m->id }}, true)">
                                       Ubah ke Menikah
                                   </flux:button>
                               @endif
                               <flux:button size="xs" variant="ghost" wire:click="deleteMarriage({{ $m->id }})" class="!text-red-500" title="Hapus Relasi">
                                   ✕
                               </flux:button>
                           </div>
                       </div>
                   @endif
               @endforeach
           </div>
       </div>
   @endif
   ```

- [ ] **Step 5: Run tests and verify**

Run: `php artisan test --compact --filter="can update existing marriage status"`
Expected: PASS

- [ ] **Step 6: Run Pint & Commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/components/⚡member-manager.blade.php tests/Feature/TreeFeaturesTest.php
git commit -m "feat(marriage): implement marriage status selection and editing for divorced and active spouses"
```

---

### Task 4: Streamline Canvas Node Hover Add Options

**Files:**
- Modify: `resources/views/components/tree-node.blade.php:108-126`
- Modify: `public/css/genealogy.css` or relevant node styles if needed
- Test: `tests/Feature/TreeFeaturesTest.php`

- [ ] **Step 1: Consolidate the 3 separate `+` buttons on canvas node**

In `resources/views/components/tree-node.blade.php`:
Replace the 3 separate buttons (`.tree-add`, `.tree-spouse`, `.tree-parent`) with:
1. Primary `+` button that adds a child (`child_of`) with tooltip "Tambah Anak".
2. A contextual quick dropdown or popover with options:
   - `👶 Tambah Anak`
   - `💍 Tambah Pasangan`
   - `👴 Tambah Orang Tua`
Or keep the buttons cleanly grouped with clearer visual hierarchy and tooltips.

- [ ] **Step 2: Verify all 270+ tests pass**

Run: `php artisan test --compact`
Expected: 270+ passed, 0 failures.

- [ ] **Step 3: Run Pint formatter & commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/components/tree-node.blade.php
git commit -m "refactor(canvas): streamline node add actions into a clean contextual relation picker"
```

---

### Task 5: Full Verification and Deployment Readiness

**Files:**
- Test suite: `tests/`
- Documentation / plan review

- [ ] **Step 1: Run complete test suite**

Run: `php artisan test --compact`
Expected: All tests PASS.

- [ ] **Step 2: Run Pint check on entire repository**

Run: `vendor/bin/pint --test --format agent` (or `vendor/bin/pint --dirty`)

- [ ] **Step 3: Test local build & verify no regressions**

Run: `npm run build`
Expected: Build successfully without errors.
