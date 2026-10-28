<?php

namespace App\Services;

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Spatie\Browsershot\Browsershot;

class TreeBannerExportService
{
    public function __construct(
        protected TreeExportService $treeExportService
    ) {}

    /**
     * Prepare structured banner data for Wall Poster A1/A0 Multi-Column Branch Layout.
     *
     * @return array<string, mixed>
     */
    public function prepareBannerData(FamilyTree $tree): array
    {
        $tree->loadMissing([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ]);

        $allMembers = $tree->members;
        $rootMembers = $this->treeExportService->getRootMembers($tree);

        // 1. Resolve Root Ancestor(s) and their spouses (Gen 1)
        $root = null;
        $rootSpouse = null;

        if ($rootMembers->isNotEmpty()) {
            // Prefer male root first, or earliest birth date, or lowest ID
            $sortedRoots = $rootMembers->sort(function (Member $a, Member $b) {
                if ($a->gender !== $b->gender) {
                    return $a->gender === 'male' ? -1 : 1;
                }
                $birthA = $a->birth_date ? $a->birth_date->timestamp : PHP_INT_MAX;
                $birthB = $b->birth_date ? $b->birth_date->timestamp : PHP_INT_MAX;
                if ($birthA !== $birthB) {
                    return $birthA <=> $birthB;
                }

                return $a->id <=> $b->id;
            });

            $root = $sortedRoots->first();

            // Find root's spouse
            $rootSpouses = $this->resolveSpouses($root, $allMembers);
            if ($rootSpouses->isNotEmpty()) {
                $rootSpouse = $rootSpouses->first();
            } else {
                // If no marriage recorded, check if another root of opposite gender exists
                $fallbackSpouse = $sortedRoots->first(fn (Member $m) => $m->id !== $root->id && $m->gender !== $root->gender);
                if ($fallbackSpouse) {
                    $rootSpouse = $fallbackSpouse;
                }
            }
        }

        // Sibling ordering comparator
        $siblingComparator = function (Member $a, Member $b): int {
            $orderA = ($a->order !== null && (int) $a->order > 0) ? (int) $a->order : 999;
            $orderB = ($b->order !== null && (int) $b->order > 0) ? (int) $b->order : 999;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            $birthA = $a->birth_date ? $a->birth_date->timestamp : PHP_INT_MAX;
            $birthB = $b->birth_date ? $b->birth_date->timestamp : PHP_INT_MAX;
            if ($birthA !== $birthB) {
                return $birthA <=> $birthB;
            }

            return $a->id <=> $b->id;
        };

        // 2. Resolve children of root (Gen 2)
        $rootIds = array_filter([$root?->id, $rootSpouse?->id]);
        $children = collect();

        if (! empty($rootIds)) {
            $children = $allMembers->filter(function (Member $m) use ($rootIds) {
                return ($m->father_id !== null && in_array($m->father_id, $rootIds))
                    || ($m->mother_id !== null && in_array($m->mother_id, $rootIds));
            })->sort($siblingComparator)->values();
        }

        // Fallback: If no children linked directly to root, check for any members not in root
        if ($children->isEmpty() && $allMembers->count() > count($rootIds)) {
            $children = $allMembers->filter(fn (Member $m) => ! in_array($m->id, $rootIds) && $m->father_id === null && $m->mother_id === null)
                ->sort($siblingComparator)->values();
        }

        // Color palettes for multi-column branches
        $colorPalettes = [
            [
                'name' => 'emerald',
                'border' => '#059669',
                'header_bg' => '#ecfdf5',
                'header_text' => '#065f46',
                'badge_bg' => '#d1fae5',
                'badge_text' => '#047857',
                'accent' => '#10b981',
                'card_bg' => '#f0fdf4',
                'card_border' => '#a7f3d0',
                'line_color' => '#059669',
            ],
            [
                'name' => 'blue',
                'border' => '#2563eb',
                'header_bg' => '#eff6ff',
                'header_text' => '#1e40af',
                'badge_bg' => '#dbeafe',
                'badge_text' => '#1d4ed8',
                'accent' => '#3b82f6',
                'card_bg' => '#f8fafc',
                'card_border' => '#bfdbfe',
                'line_color' => '#2563eb',
            ],
            [
                'name' => 'amber',
                'border' => '#d97706',
                'header_bg' => '#fffbeb',
                'header_text' => '#92400e',
                'badge_bg' => '#fef3c7',
                'badge_text' => '#b45309',
                'accent' => '#f59e0b',
                'card_bg' => '#fffdf5',
                'card_border' => '#fde68a',
                'line_color' => '#d97706',
            ],
            [
                'name' => 'rose',
                'border' => '#e11d48',
                'header_bg' => '#fff1f2',
                'header_text' => '#9f1239',
                'badge_bg' => '#ffe4e6',
                'badge_text' => '#be123c',
                'accent' => '#f43f5e',
                'card_bg' => '#fff5f6',
                'card_border' => '#fecdd3',
                'line_color' => '#e11d48',
            ],
            [
                'name' => 'purple',
                'border' => '#7c3aed',
                'header_bg' => '#faf5ff',
                'header_text' => '#581c87',
                'badge_bg' => '#f3e8ff',
                'badge_text' => '#6b21a8',
                'accent' => '#8b5cf6',
                'card_bg' => '#fdfaff',
                'card_border' => '#ddd6fe',
                'line_color' => '#7c3aed',
            ],
            [
                'name' => 'teal',
                'border' => '#0d9488',
                'header_bg' => '#f0fdfa',
                'header_text' => '#115e59',
                'badge_bg' => '#ccfbf1',
                'badge_text' => '#0f766e',
                'accent' => '#14b8a6',
                'card_bg' => '#f4fbfb',
                'card_border' => '#99f6e4',
                'line_color' => '#0d9488',
            ],
            [
                'name' => 'indigo',
                'border' => '#4f46e5',
                'header_bg' => '#eef2ff',
                'header_text' => '#3730a3',
                'badge_bg' => '#e0e7ff',
                'badge_text' => '#4338ca',
                'accent' => '#6366f1',
                'card_bg' => '#f8f9ff',
                'card_border' => '#c7d2fe',
                'line_color' => '#4f46e5',
            ],
            [
                'name' => 'orange',
                'border' => '#ea580c',
                'header_bg' => '#fff7ed',
                'header_text' => '#9a3412',
                'badge_bg' => '#ffedd5',
                'badge_text' => '#c2410c',
                'accent' => '#f97316',
                'card_bg' => '#fff9f4',
                'card_border' => '#fed7aa',
                'line_color' => '#ea580c',
            ],
        ];

        // 3. Build each child column and its vertical cascade of descendants
        $columns = [];
        $visited = [];
        if ($root) {
            $visited[$root->id] = true;
        }
        if ($rootSpouse) {
            $visited[$rootSpouse->id] = true;
        }

        $maxDescendantDepth = 0;

        foreach ($children as $colIdx => $child) {
            $childSpouses = $this->resolveSpouses($child, $allMembers);
            $primarySpouse = $childSpouses->first();

            // Track visited
            $visited[$child->id] = true;
            foreach ($childSpouses as $sp) {
                $visited[$sp->id] = true;
            }

            $palette = $colorPalettes[$colIdx % count($colorPalettes)];

            // Recursive cascade of grandchildren (depth 1), great-grandchildren (depth 2), etc.
            $descendants = $this->buildDescendantTree($child, $allMembers, $siblingComparator, 1, $visited, $maxDescendantDepth);

            $branchTotalMembers = 1 + $childSpouses->count() + $this->countBranchMembers($descendants);

            $columns[] = [
                'index' => $colIdx + 1,
                'child' => $child,
                'spouses' => $childSpouses,
                'primary_spouse' => $primarySpouse,
                'couple_label' => $this->formatCoupleLabel($child, $primarySpouse),
                'years' => $this->formatYears($child, $primarySpouse),
                'branch_title' => 'Cabang '.trim("{$child->first_name} {$child->last_name}"),
                'branch_label' => 'Anak Ke-'.($colIdx + 1),
                'palette' => $palette,
                'descendants' => $descendants,
                'total_members' => $branchTotalMembers,
            ];
        }

        // 4. Calculate Statistics
        $totalGenerations = ($root ? 1 : 0) + ($children->isNotEmpty() ? 1 : 0) + $maxDescendantDepth;
        if ($totalGenerations === 0 && $allMembers->isNotEmpty()) {
            $totalGenerations = 1;
        }

        $marriagesCount = Marriage::whereIn('husband_id', $allMembers->pluck('id'))
            ->orWhereIn('wife_id', $allMembers->pluck('id'))
            ->count();

        $stats = [
            'total_members' => $allMembers->count(),
            'total_living' => $allMembers->where('is_living', true)->count(),
            'total_deceased' => $allMembers->where('is_living', false)->count(),
            'total_male' => $allMembers->where('gender', 'male')->count(),
            'total_female' => $allMembers->where('gender', 'female')->count(),
            'total_generations' => $totalGenerations,
            'total_branches' => count($columns),
            'total_marriages' => $marriagesCount,
            'printed_at' => Carbon::now()->locale('id')->isoFormat('D MMMM Y'),
        ];

        // 5. Generate QR Code pointing to public tree or tree URL
        $qrCodeSvg = $this->generateQrCodeSvg($tree);

        // 6. Hadith Silaturahmi
        $hadith = [
            'arabic' => 'مَنْ أَحَبَّ أَنْ يُبْسَطَ لَهُ فِي رِزْقِهِ، وَيُنْسَأَ لَهُ فِي أَثَرِهِ، فَلْيَصِلْ رَحِمَهُ',
            'translation' => 'Barangsiapa yang ingin dilapangkan rezekinya dan dipanjangkan umurnya, hendaklah ia menyambung tali silaturahmi.',
            'source' => 'HR. Al-Bukhari & Muslim',
        ];

        return [
            'tree' => $tree,
            'root' => $root,
            'rootSpouse' => $rootSpouse,
            'columns' => $columns,
            'totalMembers' => $allMembers->count(),
            'stats' => $stats,
            'qrCodeSvg' => $qrCodeSvg,
            'hadith' => $hadith,
        ];
    }

    /**
     * Export tree as an elegant Wall Poster A1 Landscape PDF via Browsershot.
     */
    public function exportBannerPdf(FamilyTree $tree, string $paperSize = 'A1'): Response
    {
        $filename = "poster-silsilah-{$tree->slug}";
        $bannerData = $this->prepareBannerData($tree);

        $html = view('tree.export-banner', $bannerData)->render();
        $html = $this->treeExportService->inlineImagesForExport($html);
        $chromePath = $this->treeExportService->resolveChromePath();

        $dimensions = match (strtoupper($paperSize)) {
            'A0' => ['width' => 1189, 'height' => 841],
            'A2' => ['width' => 594, 'height' => 420],
            default => ['width' => 841, 'height' => 594], // A1 Landscape
        };

        $browsershot = Browsershot::html($html)
            ->setChromePath($chromePath)
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->timeout(120)
            ->setDelay(500)
            ->paperSize($dimensions['width'], $dimensions['height'], 'mm')
            ->landscape()
            ->margins(0, 0, 0, 0)
            ->showBackground();

        $pdfContent = $browsershot->pdf();

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.pdf\"");
    }

    /**
     * Recursively build descendant tree nodes.
     *
     * @param  array<int, bool>  $visited
     * @return array<int, array<string, mixed>>
     */
    protected function buildDescendantTree(
        Member $parent,
        Collection $allMembers,
        callable $siblingComparator,
        int $depth,
        array &$visited,
        int &$maxDescendantDepth
    ): array {
        $spouses = $this->resolveSpouses($parent, $allMembers);
        $parentIds = array_filter(array_merge([$parent->id], $spouses->pluck('id')->all()));

        $children = $allMembers->filter(function (Member $m) use ($parentIds, $visited) {
            if (isset($visited[$m->id])) {
                return false;
            }

            return in_array($m->father_id, $parentIds) || in_array($m->mother_id, $parentIds);
        })->sort($siblingComparator)->values();

        $nodes = [];

        foreach ($children as $child) {
            if ($depth > $maxDescendantDepth) {
                $maxDescendantDepth = $depth;
            }

            $visited[$child->id] = true;
            $childSpouses = $this->resolveSpouses($child, $allMembers);
            foreach ($childSpouses as $sp) {
                $visited[$sp->id] = true;
            }

            $primarySpouse = $childSpouses->first();
            $subChildren = $this->buildDescendantTree($child, $allMembers, $siblingComparator, $depth + 1, $visited, $maxDescendantDepth);

            $nodes[] = [
                'member' => $child,
                'spouses' => $childSpouses,
                'primary_spouse' => $primarySpouse,
                'couple_label' => $this->formatCoupleLabel($child, $primarySpouse),
                'years' => $this->formatYears($child, $primarySpouse),
                'depth' => $depth,
                'generation_term' => match ($depth) {
                    1 => 'Cucu',
                    2 => 'Cicit',
                    3 => 'Canggah',
                    4 => 'Wareng',
                    default => "Turunan {$depth}",
                },
                'children' => $subChildren,
            ];
        }

        return $nodes;
    }

    /**
     * Count total members in a descendant node tree (including spouses).
     *
     * @param  array<int, array<string, mixed>>  $descendants
     */
    protected function countBranchMembers(array $descendants): int
    {
        $count = 0;
        foreach ($descendants as $node) {
            $count += 1 + ($node['spouses'] ? $node['spouses']->count() : 0);
            if (! empty($node['children'])) {
                $count += $this->countBranchMembers($node['children']);
            }
        }

        return $count;
    }

    /**
     * Resolve spouses of a member from loaded marriages.
     *
     * @return Collection<int, Member>
     */
    public function resolveSpouses(Member $member, Collection $allMembers): Collection
    {
        $spouseIds = collect();

        if ($member->gender === 'male' || $member->relationLoaded('marriagesAsHusband')) {
            foreach ($member->marriagesAsHusband as $mr) {
                if ($mr->wife_id) {
                    $spouseIds->push($mr->wife_id);
                }
            }
        }

        if ($member->gender === 'female' || $member->relationLoaded('marriagesAsWife')) {
            foreach ($member->marriagesAsWife as $mr) {
                if ($mr->husband_id) {
                    $spouseIds->push($mr->husband_id);
                }
            }
        }

        if ($spouseIds->isEmpty()) {
            return collect();
        }

        return $allMembers->whereIn('id', $spouseIds->unique())->values();
    }

    /**
     * Format a single member's name with (Alm./Almh.) if deceased.
     */
    public function formatMemberName(Member $member): string
    {
        $name = trim("{$member->first_name} {$member->last_name}");
        if (! $member->is_living) {
            $suffix = $member->gender === 'female' ? '(Almh.)' : '(Alm.)';
            $name .= " {$suffix}";
        }

        return $name;
    }

    /**
     * Combine husband and wife in one line: [Nama Suami] ⚭ [Nama Istri].
     */
    public function formatCoupleLabel(Member $member, ?Member $spouse = null): string
    {
        if (! $spouse) {
            return $this->formatMemberName($member);
        }

        // Put husband first if genders are distinct, else member first
        if ($member->gender === 'male' || $spouse->gender === 'female') {
            $husband = $member;
            $wife = $spouse;
        } else {
            $husband = $spouse;
            $wife = $member;
        }

        return $this->formatMemberName($husband).' ⚭ '.$this->formatMemberName($wife);
    }

    /**
     * Format birth and death years for member (and spouse).
     */
    public function formatYears(Member $member, ?Member $spouse = null): ?string
    {
        $formatSingle = function (Member $m): ?string {
            $birth = $m->birth_date ? Carbon::parse($m->birth_date)->format('Y') : null;
            $death = $m->death_date ? Carbon::parse($m->death_date)->format('Y') : null;

            if ($birth && $death) {
                return "{$birth}–{$death}";
            }
            if ($birth) {
                return $m->is_living ? "l. {$birth}" : "{$birth}–?";
            }
            if ($death) {
                return "w. {$death}";
            }

            return null;
        };

        $mYears = $formatSingle($member);
        if ($spouse) {
            $sYears = $formatSingle($spouse);
            if ($mYears && $sYears) {
                return "({$mYears} / {$sYears})";
            }
            if ($mYears) {
                return "({$mYears})";
            }
            if ($sYears) {
                return "({$sYears})";
            }

            return null;
        }

        return $mYears ? "({$mYears})" : null;
    }

    /**
     * Generate inline SVG QR code for public tree view.
     */
    public function generateQrCodeSvg(FamilyTree $tree): string
    {
        $targetUrl = route('tree.public', ['slug' => $tree->slug]);

        try {
            $renderer = new ImageRenderer(
                new RendererStyle(140, 1),
                new SvgImageBackEnd
            );
            $writer = new Writer($renderer);
            $svg = $writer->writeString($targetUrl);

            return trim(preg_replace('/<\?xml[^>]*\?>/', '', $svg));
        } catch (\Throwable) {
            return '';
        }
    }
}
