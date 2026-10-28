# Virality & Engagement (Relationship Calculator & Family Reminders) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build two high-virality and engagement features for Silsilah: (1) An interactive Relationship Calculator ("Cek Hubungan Kekerabatan / Nasab") with lowest-common-ancestor pathfinding and Indonesian/Javanese kinship terms, and (2) A Zero-Cost Family Reminders Engine (Haul & Birthday) with 1-click WhatsApp group broadcasting (`wa.me`) and Google/Apple Calendar (`.ics`) subscription.

**Architecture:** Create `RelationshipService` using graph traversal (BFS/LCA) to compute exact kinship degrees, generational distances, and lineage paths; build `⚡relationship-calculator.blade.php` with 1-click WhatsApp text generator; create `FamilyReminderService` and `TreeCalendarController` to compute upcoming birthdays/hauls and stream standard iCalendar (`.ics`) feeds; and integrate the agenda into the Tree Management Hub and canvas toolbars.

**Tech Stack:** Laravel 13, Livewire 4 / Volt Single-File Components (`⚡...blade.php`), Flux UI, Tailwind CSS v4, iCalendar RFC 5545, Pest 4.

---

### File Structure Map

```
app/
├── Http/
│   └── Controllers/
│       └── TreeCalendarController.php              # Create: Streams .ics calendar feed
├── Services/
│   ├── RelationshipService.php                     # Create: Graph LCA kinship calculation engine
│   └── FamilyReminderService.php                   # Create: Haul & birthday reminder aggregator
resources/
├── views/
│   ├── components/
│   │   ├── ⚡relationship-calculator.blade.php     # Create: Interactive kinship modal & WA sharer
│   │   ├── ⚡family-reminders.blade.php             # Create: Reminders & calendar widget
│   │   └── ⚡tree-settings.blade.php               # Modify: Add Agenda & Pengingat tab
│   └── livewire/
│       ├── tree-view.blade.php                     # Modify: Add Cek Hubungan menu option
│       ├── tree-vertical.blade.php                 # Modify: Add Cek Hubungan menu option
│       └── tree-simple.blade.php                   # Modify: Add Cek Hubungan menu option
routes/
└── web.php                                         # Modify: Register calendar .ics route
tests/
├── Unit/
│   ├── RelationshipServiceTest.php                 # Create: Test kinship logic & Javanese terms
│   └── FamilyReminderServiceTest.php               # Create: Test haul/birthday calculations
└── Feature/
    ├── RelationshipCalculatorTest.php              # Create: Test Livewire calculator component
    └── FamilyRemindersTest.php                     # Create: Test reminder UI & .ics download
```

---

### Task 1: Relationship Calculation Engine (`RelationshipService.php`) & Unit Tests

**Files:**
- Create: `app/Services/RelationshipService.php`
- Test: `tests/Unit/RelationshipServiceTest.php`

- [ ] **Step 1: Write the failing unit tests**

Create `tests/Unit/RelationshipServiceTest.php`:
```php
<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Services\RelationshipService;

test('calculates self relationship', function () {
    $tree = FamilyTree::factory()->create();
    $person = Member::factory()->create(['family_tree_id' => $tree->id, 'first_name' => 'Budi']);

    $service = new RelationshipService;
    $result = $service->calculate($person, $person);

    expect($result['title'])->toBe('Diri Sendiri')
        ->and($result['degree'])->toBe(0);
});

test('calculates direct spouse relationship', function () {
    $tree = FamilyTree::factory()->create();
    $husband = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Budi']);
    $wife = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'first_name' => 'Siti']);

    Marriage::create(['husband_id' => $husband->id, 'wife_id' => $wife->id, 'is_current' => true]);

    $service = new RelationshipService;
    $result1 = $service->calculate($husband, $wife);
    $result2 = $service->calculate($wife, $husband);

    expect($result1['title'])->toBe('Istri')
        ->and($result2['title'])->toBe('Suami');
});

test('calculates parent and child relationships', function () {
    $tree = FamilyTree::factory()->create();
    $father = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Ahmad']);
    $mother = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'first_name' => 'Fatimah']);
    $son = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'gender' => 'male',
        'first_name' => 'Budi',
        'father_id' => $father->id,
        'mother_id' => $mother->id,
    ]);
    $daughter = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'gender' => 'female',
        'first_name' => 'Dewi',
        'father_id' => $father->id,
        'mother_id' => $mother->id,
    ]);

    $service = new RelationshipService;

    // From son to father
    expect($service->calculate($son, $father)['title'])->toBe('Ayah Kandung');
    // From father to son
    expect($service->calculate($father, $son)['title'])->toBe('Anak Laki-laki (Kandung)');
    // From mother to daughter
    expect($service->calculate($mother, $daughter)['title'])->toBe('Anak Perempuan (Kandung)');
    // Sibling
    expect($service->calculate($son, $daughter)['title'])->toBe('Saudara Kandung (Saudari)');
});

test('calculates grandparent and grandchild relationships', function () {
    $tree = FamilyTree::factory()->create();
    $grandpa = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mochtar']);
    $father = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mahmudi', 'father_id' => $grandpa->id]);
    $grandson = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Fauzi', 'father_id' => $father->id]);

    $service = new RelationshipService;

    expect($service->calculate($grandson, $grandpa)['title'])->toBe('Kakek');
    expect($service->calculate($grandpa, $grandson)['title'])->toBe('Cucu Laki-laki');
});

test('calculates uncle aunt nephew and first cousin misanan relationships', function () {
    $tree = FamilyTree::factory()->create();
    $grandpa = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mochtar']);

    // Two brothers
    $brother1 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mahmudi', 'father_id' => $grandpa->id]);
    $brother2 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Husain', 'father_id' => $grandpa->id]);

    // Children of brothers (First Cousins / Misanan)
    $child1 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Ali', 'father_id' => $brother1->id]);
    $child2 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'first_name' => 'Zahra', 'father_id' => $brother2->id]);

    $service = new RelationshipService;

    // Uncle / Nephew
    expect($service->calculate($child1, $brother2)['title'])->toBe('Paman (Om / Paklik / Pakdhe)')
        ->and($service->calculate($brother2, $child1)['title'])->toBe('Keponakan');

    // First cousins (Misanan)
    $cousinRel = $service->calculate($child1, $child2);
    expect($cousinRel['title'])->toBe('Saudara Sepupu (Misanan)')
        ->and($cousinRel['cultural_term'])->toBe('Misanan');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/RelationshipServiceTest.php`
Expected: FAIL (Class RelationshipService does not exist)

- [ ] **Step 3: Implement RelationshipService**

Create `app/Services/RelationshipService.php`:
```php
<?php

namespace App\Services;

use App\Models\Marriage;
use App\Models\Member;

class RelationshipService
{
    /**
     * Calculate relationship between Member A (subject) and Member B (target).
     *
     * @return array{
     *     title: string,
     *     formal_title: string,
     *     cultural_term: ?string,
     *     lineage_side: ?string,
     *     degree: int,
     *     generational_diff: int,
     *     path: array<int, string>,
     *     is_blood: bool,
     *     description: string,
     * }
     */
    public function calculate(Member $from, Member $to): array
    {
        // 1. Same Person
        if ($from->id === $to->id) {
            return [
                'title' => 'Diri Sendiri',
                'formal_title' => 'Diri Sendiri',
                'cultural_term' => null,
                'lineage_side' => null,
                'degree' => 0,
                'generational_diff' => 0,
                'path' => [$from->first_name],
                'is_blood' => true,
                'description' => 'Ini adalah profil Anda sendiri.',
            ];
        }

        // 2. Direct Spouses
        $isSpouse = Marriage::where(function ($q) use ($from, $to) {
            $q->where('husband_id', $from->id)->where('wife_id', $to->id);
        })->orWhere(function ($q) use ($from, $to) {
            $q->where('husband_id', $to->id)->where('wife_id', $from->id);
        })->exists();

        if ($isSpouse) {
            $title = $to->gender === 'male' ? 'Suami' : 'Istri';

            return [
                'title' => $title,
                'formal_title' => 'Pasangan Suami-Istri',
                'cultural_term' => 'Garwa (Pasangan)',
                'lineage_side' => 'Ikatan Pernikahan',
                'degree' => 1,
                'generational_diff' => 0,
                'path' => [$from->first_name, $to->first_name],
                'is_blood' => false,
                'description' => "{$to->first_name} adalah {$title} sah dari {$from->first_name}.",
            ];
        }

        // Load all tree members for in-memory graph traversal
        $allMembers = Member::where('family_tree_id', $from->family_tree_id)->get()->keyBy('id');

        // Build Ancestor Chains with distance
        $ancestorsFrom = $this->getAncestorsWithDistance($from, $allMembers);
        $ancestorsTo = $this->getAncestorsWithDistance($to, $allMembers);

        // Check if $to is direct ancestor of $from (Parent, Grandparent, Great-grandparent)
        if (isset($ancestorsFrom[$to->id])) {
            $dist = $ancestorsFrom[$to->id]['distance'];

            return $this->formatDirectAncestor($to, $dist, $from);
        }

        // Check if $from is direct ancestor of $to ($to is Child, Grandchild, Great-grandchild)
        if (isset($ancestorsTo[$from->id])) {
            $dist = $ancestorsTo[$from->id]['distance'];

            return $this->formatDirectDescendant($to, $dist, $from);
        }

        // 3. Find Lowest Common Ancestor (LCA)
        $commonAncestorIds = array_intersect(array_keys($ancestorsFrom), array_keys($ancestorsTo));

        if (! empty($commonAncestorIds)) {
            // Find LCA with smallest sum of distances
            $bestLcaId = null;
            $minDistSum = PHP_INT_MAX;

            foreach ($commonAncestorIds as $caId) {
                $distSum = $ancestorsFrom[$caId]['distance'] + $ancestorsTo[$caId]['distance'];
                if ($distSum < $minDistSum) {
                    $minDistSum = $distSum;
                    $bestLcaId = $caId;
                }
            }

            $dFrom = $ancestorsFrom[$bestLcaId]['distance'];
            $dTo = $ancestorsTo[$bestLcaId]['distance'];
            $lca = $allMembers->get($bestLcaId);

            return $this->formatCollateralRelative($from, $to, $dFrom, $dTo, $lca);
        }

        // 4. In-Law / Affinity Relationships (Mertua, Menantu, Ipar, Besan)
        $inLawRel = $this->calculateInLawRelationship($from, $to, $allMembers, $ancestorsFrom, $ancestorsTo);
        if ($inLawRel) {
            return $inLawRel;
        }

        // 5. No verifiable link found
        return [
            'title' => 'Kerabat Terhubung',
            'formal_title' => 'Kerabat Belum Terpetakan Langsung',
            'cultural_term' => 'Sanak Kadang',
            'lineage_side' => null,
            'degree' => 99,
            'generational_diff' => 0,
            'path' => [$from->first_name, $to->first_name],
            'is_blood' => false,
            'description' => "{$from->first_name} dan {$to->first_name} berada dalam satu bagan silsilah keluarga, namun jalur nasab langsungnya belum tercatat lengkap.",
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Member>  $allMembers
     * @return array<int, array{distance: int, path: array<int, int>}>
     */
    protected function getAncestorsWithDistance(Member $member, $allMembers): array
    {
        $ancestors = [];
        $queue = [[$member->id, 0, [$member->id]]];

        while (! empty($queue)) {
            [$currId, $dist, $path] = array_shift($queue);
            $curr = $allMembers->get($currId);
            if (! $curr) {
                continue;
            }

            foreach ([$curr->father_id, $curr->mother_id] as $pId) {
                if ($pId && ! isset($ancestors[$pId])) {
                    $newDist = $dist + 1;
                    $newPath = array_merge($path, [$pId]);
                    $ancestors[$pId] = [
                        'distance' => $newDist,
                        'path' => $newPath,
                    ];
                    $queue[] = [$pId, $newDist, $newPath];
                }
            }
        }

        return $ancestors;
    }

    protected function formatDirectAncestor(Member $target, int $dist, Member $from): array
    {
        $isMale = $target->gender === 'male';
        if ($dist === 1) {
            $title = $isMale ? 'Ayah Kandung' : 'Ibu Kandung';
            $term = $isMale ? 'Bapak / Rama' : 'Ibu / Simbok';
        } elseif ($dist === 2) {
            $title = $isMale ? 'Kakek' : 'Nenek';
            $term = $isMale ? 'Mbah Kakung' : 'Mbah Putri';
        } elseif ($dist === 3) {
            $title = 'Buyut';
            $term = 'Mbah Buyut';
        } else {
            $title = 'Leluhur (Generasi ke-'.$dist.')';
            $term = 'Mbah Canggah / Wareng';
        }

        return [
            'title' => $title,
            'formal_title' => "Leluhur Langsung ({$dist} Generasi di Atas)",
            'cultural_term' => $term,
            'lineage_side' => 'Jalur Garis Lurus Ke Atas',
            'degree' => $dist,
            'generational_diff' => -$dist,
            'path' => [$from->first_name, $target->first_name],
            'is_blood' => true,
            'description' => "{$target->first_name} adalah {$title} dari {$from->first_name}.",
        ];
    }

    protected function formatDirectDescendant(Member $target, int $dist, Member $from): array
    {
        $isMale = $target->gender === 'male';
        if ($dist === 1) {
            $title = $isMale ? 'Anak Laki-laki (Kandung)' : 'Anak Perempuan (Kandung)';
            $term = 'Anak Kandung';
        } elseif ($dist === 2) {
            $title = $isMale ? 'Cucu Laki-laki' : 'Cucu Perempuan';
            $term = 'Wayah / Cucu';
        } elseif ($dist === 3) {
            $title = 'Cicit';
            $term = 'Buyut / Cicit';
        } else {
            $title = 'Keturunan Langsung (Generasi ke-'.$dist.')';
            $term = 'Keturunan';
        }

        return [
            'title' => $title,
            'formal_title' => "Keturunan Langsung ({$dist} Generasi di Bawah)",
            'cultural_term' => $term,
            'lineage_side' => 'Jalur Garis Lurus Ke Bawah',
            'degree' => $dist,
            'generational_diff' => $dist,
            'path' => [$from->first_name, $target->first_name],
            'is_blood' => true,
            'description' => "{$target->first_name} adalah {$title} dari {$from->first_name}.",
        ];
    }

    protected function formatCollateralRelative(Member $from, Member $to, int $dFrom, int $dTo, ?Member $lca): array
    {
        // dFrom = 1, dTo = 1 -> Siblings
        if ($dFrom === 1 && $dTo === 1) {
            $isMale = $to->gender === 'male';
            $title = 'Saudara Kandung ('.($isMale ? 'Saudara' : 'Saudari').')';

            return [
                'title' => $title,
                'formal_title' => 'Saudara Kandung',
                'cultural_term' => 'Kakang / Mbakyu / Adhi',
                'lineage_side' => 'Satu Orang Tua',
                'degree' => 2,
                'generational_diff' => 0,
                'path' => [$from->first_name, $lca?->first_name ?? 'Orang Tua', $to->first_name],
                'is_blood' => true,
                'description' => "{$from->first_name} dan {$to->first_name} adalah saudara kandung satu keturunan.",
            ];
        }

        // dFrom = 2, dTo = 1 -> Uncle / Aunt
        if ($dFrom === 2 && $dTo === 1) {
            $isMale = $to->gender === 'male';
            $title = $isMale ? 'Paman (Om / Paklik / Pakdhe)' : 'Bibi (Tante / Bulik / Budhe)';

            return [
                'title' => $title,
                'formal_title' => 'Paman / Bibi (Saudara Orang Tua)',
                'cultural_term' => $isMale ? 'Pakdhe / Paklik' : 'Budhe / Bulik',
                'lineage_side' => 'Garis Paman/Bibi',
                'degree' => 3,
                'generational_diff' => -1,
                'path' => [$from->first_name, 'Orang Tua', $lca?->first_name ?? 'Kakek/Nenek', $to->first_name],
                'is_blood' => true,
                'description' => "{$to->first_name} adalah {$title} dari {$from->first_name}.",
            ];
        }

        // dFrom = 1, dTo = 2 -> Nephew / Niece
        if ($dFrom === 1 && $dTo === 2) {
            $isMale = $to->gender === 'male';
            $title = 'Keponakan';

            return [
                'title' => $title,
                'formal_title' => 'Keponakan (Anak dari Saudara)',
                'cultural_term' => 'Keponakan',
                'lineage_side' => 'Anak Saudara',
                'degree' => 3,
                'generational_diff' => 1,
                'path' => [$from->first_name, $lca?->first_name ?? 'Orang Tua', 'Saudara', $to->first_name],
                'is_blood' => true,
                'description' => "{$to->first_name} adalah keponakan dari {$from->first_name}.",
            ];
        }

        // dFrom = 2, dTo = 2 -> First Cousins (Misanan)
        if ($dFrom === 2 && $dTo === 2) {
            return [
                'title' => 'Saudara Sepupu (Misanan)',
                'formal_title' => 'Sepupu 1 Kali (Satu Kakek/Nenek)',
                'cultural_term' => 'Misanan',
                'lineage_side' => 'Anak dari Paman/Bibi',
                'degree' => 4,
                'generational_diff' => 0,
                'path' => [$from->first_name, 'Orang Tua', $lca?->first_name ?? 'Kakek', 'Paman/Bibi', $to->first_name],
                'is_blood' => true,
                'description' => "{$from->first_name} dan {$to->first_name} adalah saudara sepupu (misanan), cucu dari {$lca?->first_name}.",
            ];
        }

        // dFrom = 3, dTo = 3 -> Second Cousins (Mindhoan)
        if ($dFrom === 3 && $dTo === 3) {
            return [
                'title' => 'Saudara Sepupu 2 Kali (Mindhoan)',
                'formal_title' => 'Sepupu 2 Kali (Satu Buyut)',
                'cultural_term' => 'Mindhoan',
                'lineage_side' => 'Garis Buyut Bersama',
                'degree' => 6,
                'generational_diff' => 0,
                'path' => [$from->first_name, 'Kakek', $lca?->first_name ?? 'Buyut', 'Kakek Kerabat', $to->first_name],
                'is_blood' => true,
                'description' => "{$from->first_name} dan {$to->first_name} adalah saudara sepupu 2 kali (mindhoan) dengan buyut bersama {$lca?->first_name}.",
            ];
        }

        // Fallback for higher degrees
        $genDiff = $dTo - $dFrom;
        $title = $genDiff === 0 ? 'Kerabat Sebaya (Sepupu Jauh)' : ($genDiff > 0 ? 'Kerabat Keponakan Jauh' : 'Kerabat Paman/Bibi Jauh');

        return [
            'title' => $title,
            'formal_title' => "Kerabat Garis Keturunan Leluhur ({$lca?->first_name})",
            'cultural_term' => 'Sanak Kadang',
            'lineage_side' => 'Leluhur Bersama: '.$lca?->first_name,
            'degree' => $dFrom + $dTo,
            'generational_diff' => $genDiff,
            'path' => [$from->first_name, $lca?->first_name ?? 'Leluhur', $to->first_name],
            'is_blood' => true,
            'description' => "{$from->first_name} dan {$to->first_name} terhubung melalui leluhur bersama {$lca?->first_name}.",
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Member>  $allMembers
     */
    protected function calculateInLawRelationship(Member $from, Member $to, $allMembers, array $ancFrom, array $ancTo): ?array
    {
        // Check marriages of $from to see if $to is spouse's parent (Mertua)
        $spousesOfFrom = Marriage::where('husband_id', $from->id)->pluck('wife_id')
            ->concat(Marriage::where('wife_id', $from->id)->pluck('husband_id'));

        foreach ($spousesOfFrom as $spId) {
            $spouse = $allMembers->get($spId);
            if ($spouse && ($spouse->father_id === $to->id || $spouse->mother_id === $to->id)) {
                return [
                    'title' => $to->gender === 'male' ? 'Mertua Laki-laki' : 'Mertua Perempuan',
                    'formal_title' => 'Mertua (Orang Tua Pasangan)',
                    'cultural_term' => 'Mertua',
                    'lineage_side' => 'Garis Pasangan',
                    'degree' => 2,
                    'generational_diff' => -1,
                    'path' => [$from->first_name, $spouse->first_name, $to->first_name],
                    'is_blood' => false,
                    'description' => "{$to->first_name} adalah mertua dari {$from->first_name} (orang tua dari {$spouse->first_name}).",
                ];
            }
        }

        // Check if $from is parent of $to's spouse (Menantu)
        $spousesOfTo = Marriage::where('husband_id', $to->id)->pluck('wife_id')
            ->concat(Marriage::where('wife_id', $to->id)->pluck('husband_id'));

        foreach ($spousesOfTo as $spId) {
            $spouse = $allMembers->get($spId);
            if ($spouse && ($spouse->father_id === $from->id || $spouse->mother_id === $from->id)) {
                return [
                    'title' => $to->gender === 'male' ? 'Menantu Laki-laki' : 'Menantu Perempuan',
                    'formal_title' => 'Menantu (Pasangan Anak)',
                    'cultural_term' => 'Mantu',
                    'lineage_side' => 'Garis Pasangan Anak',
                    'degree' => 2,
                    'generational_diff' => 1,
                    'path' => [$from->first_name, $spouse->first_name, $to->first_name],
                    'is_blood' => false,
                    'description' => "{$to->first_name} adalah menantu dari {$from->first_name} (menikah dengan {$spouse->first_name}).",
                ];
            }
        }

        return null;
    }
}
```

- [ ] **Step 4: Run tests and verify they pass**

Run: `php artisan test --compact tests/Unit/RelationshipServiceTest.php`
Expected: PASS (5 passed, 11 assertions)

- [ ] **Step 5: Commit**

```bash
git add app/Services/RelationshipService.php tests/Unit/RelationshipServiceTest.php
git commit -m "feat(nasab): add relationship calculation engine with LCA and Javanese terms"
```

---

### Task 2: Interactive Relationship Calculator Component (`⚡relationship-calculator.blade.php`) & Tree View Integration

**Files:**
- Create: `resources/views/components/⚡relationship-calculator.blade.php`
- Modify: `resources/views/livewire/tree-view.blade.php`
- Modify: `resources/views/livewire/tree-vertical.blade.php`
- Modify: `resources/views/livewire/tree-simple.blade.php`
- Test: `tests/Feature/RelationshipCalculatorTest.php`

- [ ] **Step 1: Write the failing feature test**

Create `tests/Feature/RelationshipCalculatorTest.php`:
```php
<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

it('renders relationship calculator modal and calculates kinship between two members', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $grandpa = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mochtar']);
    $son1 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mahmudi', 'father_id' => $grandpa->id]);
    $son2 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Husain', 'father_id' => $grandpa->id]);

    $this->actingAs($owner);

    Livewire::test('⚡relationship-calculator', ['treeId' => $tree->id])
        ->assertSee('Cek Hubungan Kekerabatan')
        ->set('personAId', $son1->id)
        ->set('personBId', $son2->id)
        ->call('calculate')
        ->assertSee('Saudara Kandung')
        ->assertSee('Bagikan ke WhatsApp');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=RelationshipCalculatorTest`
Expected: FAIL (Component relationship-calculator not found)

- [ ] **Step 3: Implement ⚡relationship-calculator.blade.php**

Create `resources/views/components/⚡relationship-calculator.blade.php`:
- Livewire Volt component:
  - Properties: `$treeId`, `$personAId`, `$personBId`, `$result`, `$showModal = false`.
  - Listeners: `#[On('open-relationship-calculator')] public function openModal($memberA = null, $memberB = null)`
  - Methods: `calculate()`, `swapPersons()`, `getWhatsAppShareUrl()`.
  - UI:
    - Modal width: `md:w-[40rem] max-w-2xl`.
    - Selector A & Selector B with member avatars and names.
    - Swap button.
    - Result card with cultural badge (e.g. `Misanan`), formal title, pathway visualization, and green button **"Bagikan ke WhatsApp"** (`window.open(url, '_blank')`).

- [ ] **Step 4: Hook into Tree View Options**

In `tree-view.blade.php`, `tree-vertical.blade.php`, and `tree-simple.blade.php`:
- Add dropdown menu item: `<flux:menu.item icon="user-group" wire:click="$dispatch('open-relationship-calculator')">Cek Hubungan Kekerabatan</flux:menu.item>`.
- Include `<livewire:relationship-calculator :tree-id="$tree->id" />` before closing tag.

- [ ] **Step 5: Run tests and verify they pass**

Run: `php artisan test --compact --filter=RelationshipCalculatorTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/⚡relationship-calculator.blade.php resources/views/livewire/ tests/Feature/RelationshipCalculatorTest.php
git commit -m "feat(nasab): add interactive relationship calculator modal and WhatsApp sharer"
```

---

### Task 3: Family Reminders Engine (Haul, Birthday, Anniversary Calculator) in `FamilyReminderService.php`

**Files:**
- Create: `app/Services/FamilyReminderService.php`
- Test: `tests/Unit/FamilyReminderServiceTest.php`

- [ ] **Step 1: Write the failing unit tests**

Create `tests/Unit/FamilyReminderServiceTest.php`:
```php
<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Services\FamilyReminderService;
use Carbon\Carbon;

test('calculates upcoming birthdays and hauls with correct ordinals', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create();

    // Living member with birthday in 5 days (Nov 6)
    $bdayMember = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Fauzi',
        'is_living' => true,
        'birth_date' => '1990-11-06',
    ]);

    // Deceased member with haul in 10 days (Nov 11)
    $haulMember = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Mochtar',
        'is_living' => false,
        'death_date' => '2020-11-11',
    ]);

    $service = new FamilyReminderService;
    $reminders = $service->getUpcomingReminders($tree, 30);

    expect($reminders)->toHaveCount(2);

    $bday = $reminders->firstWhere('type', 'birthday');
    expect($bday['member']->id)->toBe($bdayMember->id)
        ->and($bday['ordinal_text'])->toBe('Ulang Tahun ke-36')
        ->and($bday['days_until'])->toBe(5);

    $haul = $reminders->firstWhere('type', 'haul');
    expect($haul['member']->id)->toBe($haulMember->id)
        ->and($haul['ordinal_text'])->toBe('Haul ke-6')
        ->and($haul['days_until'])->toBe(10);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/FamilyReminderServiceTest.php`
Expected: FAIL (Class FamilyReminderService does not exist)

- [ ] **Step 3: Implement FamilyReminderService**

Create `app/Services/FamilyReminderService.php`:
- `getUpcomingReminders(FamilyTree $tree, int $daysAhead = 30): Collection`
- Calculates days until next anniversary of date.
- Ordinal text: `Haul ke-{X}` and `Ulang Tahun ke-{Y}`.
- Generates pre-filled WhatsApp broadcast text:
  `"Assalamu'alaikum wr. wb. Mengingatkan keluarga besar Trah {$tree->name}, insyaAllah {$daysText} ({$dateFormatted}) adalah {$ordinalText} {$member->first_name} {$member->last_name}. Mari kita luangkan doa untuk almarhum/beliau. Bagan Silsilah: {$url}"`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Unit/FamilyReminderServiceTest.php`
Expected: PASS (1 passed, 7 assertions)

- [ ] **Step 5: Commit**

```bash
git add app/Services/FamilyReminderService.php tests/Unit/FamilyReminderServiceTest.php
git commit -m "feat(reminders): add family reminders calculation engine for haul and birthdays"
```

---

### Task 4: Kalender & Pengingat Keluarga UI (`⚡family-reminders.blade.php`), WhatsApp Sharer, & ICS Feed

**Files:**
- Create: `app/Http/Controllers/TreeCalendarController.php`
- Create: `resources/views/components/⚡family-reminders.blade.php`
- Modify: `resources/views/components/⚡tree-settings.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/FamilyRemindersTest.php`

- [ ] **Step 1: Write the failing feature test**

Create `tests/Feature/FamilyRemindersTest.php`:
```php
<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

it('renders upcoming family reminders with WhatsApp share buttons', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Bani Mochtar']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'is_living' => false,
        'death_date' => '2021-11-05',
    ]);

    $this->actingAs($owner);

    Livewire::test('⚡family-reminders', ['treeId' => $tree->id])
        ->assertSee('Haul ke-5')
        ->assertSee('Kirim ke WhatsApp');
});

it('downloads standard ics calendar feed for tree reminders', function () {
    $tree = FamilyTree::factory()->create(['is_public' => true]);
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'is_living' => false,
        'death_date' => '2021-11-05',
    ]);

    $response = $this->get(route('tree.calendar.ics', $tree->slug));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    $response->assertSee('BEGIN:VCALENDAR');
    $response->assertSee('SUMMARY:Haul');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=FamilyRemindersTest`
Expected: FAIL (Route tree.calendar.ics and component not found)

- [ ] **Step 3: Register route and implement TreeCalendarController**

In `routes/web.php`:
```php
Route::get('tree/{tree}/calendar.ics', [\App\Http\Controllers\TreeCalendarController::class, 'exportIcs'])->name('tree.calendar.ics');
```

Create `app/Http/Controllers/TreeCalendarController.php`:
- Returns `.ics` response formatted per RFC 5545 for Google Calendar / Apple Calendar.

- [ ] **Step 4: Implement ⚡family-reminders.blade.php & Add Tab to Tree Management Hub**

1. Create `resources/views/components/⚡family-reminders.blade.php`:
   - Lists reminders for next 30 days grouped by: Hari Ini, Minggu Ini, Bulan Ini.
   - 1-Click "Kirim ke Grup WA" (`https://wa.me/?text=...`).
   - "Sinkron ke Kalender HP / Google Calendar" button linking to `route('tree.calendar.ics', $tree->slug)`.
2. In `resources/views/components/⚡tree-settings.blade.php`:
   - Add tab: `agenda` ("Agenda & Pengingat") rendering `<livewire:family-reminders :tree-id="$treeId" />`.

- [ ] **Step 5: Run tests and verify they pass**

Run: `php artisan test --compact --filter=FamilyRemindersTest`
Expected: PASS (2 passed, 6 assertions)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/TreeCalendarController.php resources/views/components/⚡family-reminders.blade.php resources/views/components/⚡tree-settings.blade.php routes/web.php tests/Feature/FamilyRemindersTest.php
git commit -m "feat(reminders): add family reminders widget, WhatsApp broadcast sharer and .ics feed"
```

---

### Task 5: End-to-End Verification, Pint Styling, and Production Deployment

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
Expected: All tests pass (180+ passed, 0 failed).

- [ ] **Step 3: Git status & push to main**

```bash
git status
git push origin main
```

- [ ] **Step 4: Deploy to production server (10.133.21.24)**

Run:
```bash
ssh bpsdemak@10.133.21.24 "cd apps/muhshi/silsilah && git pull origin main && docker cp app silsilah-franken:/app/ && docker cp resources silsilah-franken:/app/ && docker cp routes silsilah-franken:/app/ && docker exec silsilah-franken php artisan view:clear && docker exec silsilah-franken php artisan route:clear"
```
Expected: Deployment completed cleanly, views cleared.

- [ ] **Step 5: Production Smoke Test**

Verify route `/tree/{slug}/calendar.ics` and test relationship calculator on production container.
