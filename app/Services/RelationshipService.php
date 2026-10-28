<?php

namespace App\Services;

use App\Models\Marriage;
use App\Models\Member;
use Illuminate\Support\Collection;

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
        $treeIds = array_filter(array_unique([$from->family_tree_id, $to->family_tree_id]));
        /** @var Collection<int, Member> $allMembers */
        $allMembers = ! empty($treeIds)
            ? Member::whereIn('family_tree_id', $treeIds)->get()->keyBy('id')
            : collect();

        if (! $allMembers->has($from->id)) {
            $allMembers->put($from->id, $from);
        }
        if (! $allMembers->has($to->id)) {
            $allMembers->put($to->id, $to);
        }

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
            $lca = $allMembers->get($bestLcaId) ?? Member::find($bestLcaId);

            return $this->formatCollateralRelative($from, $to, $dFrom, $dTo, $lca);
        }

        // 4. In-Law / Affinity Relationships (Mertua, Menantu, Ipar, Besan)
        $inLawRel = $this->calculateInLawRelationship($from, $to, $allMembers);
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
     * @param  Collection<int, Member>  $allMembers
     * @return array<int, array{distance: int, path: array<int, int>}>
     */
    protected function getAncestorsWithDistance(Member $member, Collection $allMembers): array
    {
        $ancestors = [];
        $queue = [[$member->id, 0, [$member->id]]];

        while (! empty($queue)) {
            [$currId, $dist, $path] = array_shift($queue);
            $curr = $allMembers->get($currId) ?? Member::find($currId);
            if (! $curr) {
                continue;
            }

            $allMembers->put($curr->id, $curr);

            foreach (array_filter([$curr->father_id, $curr->mother_id]) as $pId) {
                if (! isset($ancestors[$pId])) {
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

    /**
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

    /**
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

    /**
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
     * @param  Collection<int, Member>  $allMembers
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
     * }|null
     */
    protected function calculateInLawRelationship(Member $from, Member $to, Collection $allMembers): ?array
    {
        // 1. Check marriages of $from to see if $to is spouse's parent (Mertua)
        $spousesOfFrom = Marriage::where('husband_id', $from->id)->pluck('wife_id')
            ->concat(Marriage::where('wife_id', $from->id)->pluck('husband_id'))
            ->filter();

        foreach ($spousesOfFrom as $spId) {
            $spouse = $allMembers->get($spId) ?? Member::find($spId);
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

        // 2. Check if $from is parent of $to's spouse (Menantu)
        $spousesOfTo = Marriage::where('husband_id', $to->id)->pluck('wife_id')
            ->concat(Marriage::where('wife_id', $to->id)->pluck('husband_id'))
            ->filter();

        foreach ($spousesOfTo as $spId) {
            $spouse = $allMembers->get($spId) ?? Member::find($spId);
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

        // 3. Saudara Ipar: $to is sibling of $from's spouse
        foreach ($spousesOfFrom as $spId) {
            $spouse = $allMembers->get($spId) ?? Member::find($spId);
            if ($spouse && ($spouse->father_id || $spouse->mother_id)) {
                $sharesParent = ($spouse->father_id && $spouse->father_id === $to->father_id) ||
                               ($spouse->mother_id && $spouse->mother_id === $to->mother_id);
                if ($sharesParent) {
                    $isMale = $to->gender === 'male';

                    return [
                        'title' => 'Saudara Ipar',
                        'formal_title' => 'Ipar (Saudara dari Pasangan)',
                        'cultural_term' => $isMale ? 'Kakang/Adhi Ipar' : 'Mbakyu/Adhi Ipar',
                        'lineage_side' => 'Saudara Pasangan',
                        'degree' => 2,
                        'generational_diff' => 0,
                        'path' => [$from->first_name, $spouse->first_name, $to->first_name],
                        'is_blood' => false,
                        'description' => "{$to->first_name} adalah saudara ipar dari {$from->first_name} (saudara dari {$spouse->first_name}).",
                    ];
                }
            }
        }

        // 4. Saudara Ipar: $to is married to a sibling of $from
        if ($from->father_id || $from->mother_id) {
            foreach ($spousesOfTo as $spId) {
                $spouseOfTo = $allMembers->get($spId) ?? Member::find($spId);
                if ($spouseOfTo && (
                    ($from->father_id && $from->father_id === $spouseOfTo->father_id) ||
                    ($from->mother_id && $from->mother_id === $spouseOfTo->mother_id)
                )) {
                    $isMale = $to->gender === 'male';

                    return [
                        'title' => 'Saudara Ipar',
                        'formal_title' => 'Ipar (Pasangan Saudara)',
                        'cultural_term' => $isMale ? 'Kakang/Adhi Ipar' : 'Mbakyu/Adhi Ipar',
                        'lineage_side' => 'Pasangan Saudara',
                        'degree' => 2,
                        'generational_diff' => 0,
                        'path' => [$from->first_name, $spouseOfTo->first_name, $to->first_name],
                        'is_blood' => false,
                        'description' => "{$to->first_name} adalah saudara ipar dari {$from->first_name} (pasangan dari {$spouseOfTo->first_name}).",
                    ];
                }
            }
        }

        // 5. Besan: $from and $to are parents of a married couple
        $childrenOfFrom = $allMembers->filter(fn ($m) => $m->father_id === $from->id || $m->mother_id === $from->id);
        foreach ($childrenOfFrom as $child) {
            $childSpouses = Marriage::where('husband_id', $child->id)->pluck('wife_id')
                ->concat(Marriage::where('wife_id', $child->id)->pluck('husband_id'));
            foreach ($childSpouses as $cSpId) {
                $cSp = $allMembers->get($cSpId) ?? Member::find($cSpId);
                if ($cSp && ($cSp->father_id === $to->id || $cSp->mother_id === $to->id)) {
                    return [
                        'title' => 'Besan',
                        'formal_title' => 'Besan (Orang Tua Menantu)',
                        'cultural_term' => 'Besan',
                        'lineage_side' => 'Besan',
                        'degree' => 3,
                        'generational_diff' => 0,
                        'path' => [$from->first_name, $child->first_name, $cSp->first_name, $to->first_name],
                        'is_blood' => false,
                        'description' => "{$from->first_name} dan {$to->first_name} adalah besan (orang tua dari pasangan suami-istri {$child->first_name} dan {$cSp->first_name}).",
                    ];
                }
            }
        }

        return null;
    }
}
