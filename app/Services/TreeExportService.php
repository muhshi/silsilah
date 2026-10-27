<?php

namespace App\Services;

use App\Models\FamilyTree;
use App\Models\Marriage;
use Carbon\Carbon;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class TreeExportService
{
    /**
     * Resolve FamilyTree model with required relationships
     */
    public function findTree(string|int $tree): FamilyTree
    {
        $relations = [
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ];

        return is_numeric($tree)
            ? FamilyTree::with($relations)->find($tree) ?? FamilyTree::with($relations)->where('slug', $tree)->firstOrFail()
            : FamilyTree::with($relations)->where('slug', $tree)->first() ?? FamilyTree::with($relations)->findOrFail($tree);
    }

    /**
     * Determine root members of a tree
     */
    public function getRootMembers(FamilyTree $treeModel): Collection
    {
        $allMembers = $treeModel->members;
        $nonRootIds = collect();

        foreach ($allMembers as $m) {
            if ($m->father_id !== null || $m->mother_id !== null) {
                $nonRootIds->push($m->id);
            }
        }

        foreach ($allMembers as $m) {
            if ($m->relationLoaded('marriagesAsHusband')) {
                foreach ($m->marriagesAsHusband as $marriage) {
                    $husband = $allMembers->firstWhere('id', $marriage->husband_id);
                    $wife = $allMembers->firstWhere('id', $marriage->wife_id);
                    if ($husband && $wife) {
                        $hHasParents = $husband->father_id !== null || $husband->mother_id !== null;
                        $wHasParents = $wife->father_id !== null || $wife->mother_id !== null;
                        if ($hHasParents || $wHasParents) {
                            $nonRootIds->push($husband->id);
                            $nonRootIds->push($wife->id);
                        } else {
                            $nonRootIds->push($wife->id);
                        }
                    }
                }
            }
        }

        return $allMembers->whereNotIn('id', $nonRootIds->unique());
    }

    /**
     * Export tree data as formatted AI System Prompt markdown
     */
    public function exportPrompt(FamilyTree $treeModel): Response
    {
        $filename = Str::slug($treeModel->name).'-Silsilah';
        $allMembers = $treeModel->members;

        $marriages = Marriage::whereIn('husband_id', $allMembers->pluck('id'))
            ->orWhereIn('wife_id', $allMembers->pluck('id'))
            ->get();

        $markdown = "# Prompt AI: Diagram & Visual Silsilah Keluarga \"{$treeModel->name}\"\n\n";
        $markdown .= "> **Panduan Penggunaan**:\n";
        $markdown .= "> Upload atau salin teks dalam file ini langsung ke **ChatGPT**, **Gemini**, atau **Claude**.\n";
        $markdown .= "> Minta AI untuk membuatkan diagram Mermaid.js, Graphviz, atau prompt visual artwork silsilah keluarga lengkap.\n\n";

        $markdown .= "## 🤖 Instruksi untuk AI (System Prompt)\n";
        $markdown .= "Anda adalah seorang Visual Designer & Information Architect berpengalaman.\n";
        $markdown .= "Tugas Anda adalah memvisualisasikan seluruh silsilah keluarga **\"{$treeModel->name}\"** di bawah ini secara utuh, jelas, dan rapi dalam satu gambaran besar tanpa ada anggota keluarga yang terlewat.\n\n";
        $markdown .= "### Pilihan Format Output yang Bisa Anda Hasilkan:\n";
        $markdown .= "1. **Diagram Mermaid.js (`graph TD` atau `graph LR`)**: Agar bisa di-render langsung menjadi diagram vektor interaktif atau gambar SVG/PNG.\n";
        $markdown .= "2. **Kode Graphviz DOT / PlantUML**: Untuk di-render menjadi diagram struktur kerapatan tinggi.\n";
        $markdown .= "3. **Prompt Deskriptif Gambar (Visual Art Prompt)**: Untuk di-generate via DALL-E 3, Midjourney, atau Imagen menjadi poster karya seni lukisan/pohon silsilah keluarga klasik.\n\n";

        $markdown .= "---\n\n";
        $markdown .= "## 📊 Ringkasan Data Silsilah\n";
        $markdown .= "- **Nama Pohon**: {$treeModel->name}\n";
        if ($treeModel->description) {
            $markdown .= "- **Deskripsi**: {$treeModel->description}\n";
        }
        $markdown .= "- **Total Anggota**: {$allMembers->count()} orang\n";
        $markdown .= "- **Total Pernikahan**: {$marriages->count()} pasangan\n\n";

        $markdown .= "---\n\n";
        $markdown .= "## 👥 Daftar Anggota Keluarga & Rincian\n\n";

        foreach ($allMembers as $idx => $m) {
            $num = $idx + 1;
            $fullName = trim("{$m->first_name} {$m->last_name}");
            $gender = $m->gender === 'male' ? 'Laki-laki (Male)' : 'Perempuan (Female)';
            $status = $m->is_living ? 'Masih Hidup' : 'Wafat';

            $birth = $m->birth_date ? Carbon::parse($m->birth_date)->format('d M Y') : null;
            $death = $m->death_date ? Carbon::parse($m->death_date)->format('d M Y') : null;
            $years = '';
            if ($birth || $death) {
                $years = ' ('.($birth ?? '?').' - '.($death ?? ($m->is_living ? 'sekarang' : '?')).')';
            }

            $father = $allMembers->firstWhere('id', $m->father_id);
            $mother = $allMembers->firstWhere('id', $m->mother_id);

            $fatherName = $father ? trim("{$father->first_name} {$father->last_name}") : '-';
            $motherName = $mother ? trim("{$mother->first_name} {$mother->last_name}") : '-';

            $spouses = collect();
            if ($m->gender === 'male') {
                foreach ($m->marriagesAsHusband as $mr) {
                    $w = $allMembers->firstWhere('id', $mr->wife_id);
                    if ($w) {
                        $spouses->push(trim("{$w->first_name} {$w->last_name}"));
                    }
                }
            } else {
                foreach ($m->marriagesAsWife as $mr) {
                    $h = $allMembers->firstWhere('id', $mr->husband_id);
                    if ($h) {
                        $spouses->push(trim("{$h->first_name} {$h->last_name}"));
                    }
                }
            }
            $spouseStr = $spouses->count() > 0 ? $spouses->implode(', ') : '-';

            $childrenFilter = $m->gender === 'male' ? 'father_id' : 'mother_id';
            $children = $allMembers->where($childrenFilter, $m->id)->map(fn ($c) => trim("{$c->first_name} {$c->last_name}"));
            $childrenStr = $children->count() > 0 ? $children->implode(', ') : '-';

            $markdown .= "### {$num}. {$fullName}{$years}\n";
            $markdown .= "- **ID**: #M{$m->id}\n";
            $markdown .= "- **Jenis Kelamin**: {$gender}\n";
            $markdown .= "- **Status**: {$status}\n";
            if ($m->profession) {
                $markdown .= "- **Pekerjaan**: {$m->profession}\n";
            }
            if ($m->address) {
                $markdown .= "- **Alamat/Lokasi**: {$m->address}\n";
            }
            if ($m->bio) {
                $markdown .= "- **Catatan/Bio**: {$m->bio}\n";
            }
            $markdown .= "- **Ayah**: {$fatherName}\n";
            $markdown .= "- **Ibu**: {$motherName}\n";
            $markdown .= "- **Pasangan**: {$spouseStr}\n";
            $markdown .= "- **Anak-anak**: {$childrenStr}\n\n";
        }

        $markdown .= "---\n\n";
        $markdown .= "## 🔗 Pemetaan Hubungan (Relationship Mapping)\n\n";
        $markdown .= "### A. Hubungan Orang Tua & Anak:\n";
        foreach ($allMembers as $m) {
            $childName = trim("{$m->first_name} {$m->last_name}");
            if ($m->father_id) {
                $father = $allMembers->firstWhere('id', $m->father_id);
                if ($father) {
                    $fatherName = trim("{$father->first_name} {$father->last_name}");
                    $markdown .= "- `{$fatherName}` ➔ `{$childName}` (Ayah ➔ Anak)\n";
                }
            }
            if ($m->mother_id) {
                $mother = $allMembers->firstWhere('id', $m->mother_id);
                if ($mother) {
                    $motherName = trim("{$mother->first_name} {$mother->last_name}");
                    $markdown .= "- `{$motherName}` ➔ `{$childName}` (Ibu ➔ Anak)\n";
                }
            }
        }

        $markdown .= "\n### B. Hubungan Perkawinan (Pernikahan):\n";
        foreach ($marriages as $mr) {
            $h = $allMembers->firstWhere('id', $mr->husband_id);
            $w = $allMembers->firstWhere('id', $mr->wife_id);
            if ($h && $w) {
                $hName = trim("{$h->first_name} {$h->last_name}");
                $wName = trim("{$w->first_name} {$w->last_name}");
                $mDate = $mr->marriage_date ? ' (Tgl: '.Carbon::parse($mr->marriage_date)->format('d M Y').')' : '';
                $markdown .= "- `{$hName}` 💍 `{$wName}`{$mDate}\n";
            }
        }

        return response($markdown)
            ->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}-Prompt-AI.md\"");
    }

    /**
     * Export tree data as structured JSON
     */
    public function exportJson(FamilyTree $treeModel): JsonResponse
    {
        $filename = Str::slug($treeModel->name).'-Silsilah';
        $allMembers = $treeModel->members;

        $marriages = Marriage::whereIn('husband_id', $allMembers->pluck('id'))
            ->orWhereIn('wife_id', $allMembers->pluck('id'))
            ->get();

        $jsonData = [
            'tree' => [
                'id' => $treeModel->id,
                'name' => $treeModel->name,
                'description' => $treeModel->description,
            ],
            'members' => $allMembers->map(fn ($m) => [
                'id' => $m->id,
                'first_name' => $m->first_name,
                'last_name' => $m->last_name,
                'gender' => $m->gender,
                'birth_date' => $m->birth_date ? Carbon::parse($m->birth_date)->format('Y-m-d') : null,
                'death_date' => $m->death_date ? Carbon::parse($m->death_date)->format('Y-m-d') : null,
                'is_living' => (bool) $m->is_living,
                'birth_place' => $m->birth_place,
                'profession' => $m->profession,
                'address' => $m->address,
                'bio' => $m->bio,
                'father_id' => $m->father_id,
                'mother_id' => $m->mother_id,
                'avatar_id' => $m->avatar_id,
            ])->values(),
            'marriages' => $marriages->map(fn ($mr) => [
                'id' => $mr->id,
                'husband_id' => $mr->husband_id,
                'wife_id' => $mr->wife_id,
                'marriage_date' => $mr->marriage_date ? Carbon::parse($mr->marriage_date)->format('Y-m-d') : null,
                'is_current' => (bool) $mr->is_current,
            ])->values(),
        ];

        return response()->json($jsonData, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}.json\"",
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Render and inline images in HTML for Browsershot
     */
    public function inlineImagesForExport(string $html): string
    {
        $defaultAvatarFile = public_path('images/no_profile_pic.jpg');
        $defaultAvatarBase64 = file_exists($defaultAvatarFile)
            ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($defaultAvatarFile))
            : '';

        // 1. Inline local disk images
        $html = preg_replace_callback(
            '/src="([^"]+)"/',
            function ($matches) use ($defaultAvatarBase64) {
                $src = $matches[1];
                if (str_starts_with($src, 'data:')) {
                    return $matches[0];
                }

                $urlPath = parse_url($src, PHP_URL_PATH);
                if ($urlPath) {
                    $filePath = public_path(ltrim($urlPath, '/'));
                    if (file_exists($filePath) && is_file($filePath)) {
                        $mime = mime_content_type($filePath) ?: 'image/jpeg';

                        return 'src="data:'.$mime.';base64,'.base64_encode(file_get_contents($filePath)).'"';
                    }
                }

                if (str_contains($src, 'localhost') || str_starts_with($src, '/')) {
                    return $defaultAvatarBase64 ? 'src="'.$defaultAvatarBase64.'"' : $matches[0];
                }

                return $matches[0];
            },
            $html
        );

        // 2. Fetch external images in parallel
        preg_match_all('/src="(https?:\/\/[^"]+)"/', $html, $urlMatches);
        $externalUrls = array_filter(array_unique($urlMatches[1] ?? []), fn ($u) => ! str_contains($u, 'localhost'));

        if (count($externalUrls) > 0) {
            $responses = Http::pool(fn ($pool) => collect($externalUrls)->map(
                fn ($url) => $pool->as(md5($url))->timeout(3)->get($url)
            )->all());

            foreach ($externalUrls as $url) {
                $key = md5($url);
                if (isset($responses[$key])
                    && $responses[$key] instanceof HttpResponse
                    && $responses[$key]->successful()) {
                    $mime = $responses[$key]->header('Content-Type') ?: 'image/jpeg';
                    $dataUri = 'data:'.$mime.';base64,'.base64_encode($responses[$key]->body());
                    $html = str_replace('src="'.$url.'"', 'src="'.$dataUri.'"', $html);
                } elseif ($defaultAvatarBase64) {
                    $html = str_replace('src="'.$url.'"', 'src="'.$defaultAvatarBase64.'"', $html);
                }
            }
        }

        return preg_replace('/\s*onerror="[^"]*"/', '', $html);
    }

    /**
     * Resolve Chrome binary path
     */
    public function resolveChromePath(): ?string
    {
        $chromePaths = [
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', // macOS
            '/usr/bin/google-chrome', // Linux
            '/usr/bin/google-chrome-stable', // Linux
            '/usr/bin/chromium-browser', // Linux
            '/usr/bin/chromium', // Linux
        ];

        foreach ($chromePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return trim((string) shell_exec('node -e "console.log(require(\'puppeteer\').executablePath())"')) ?: null;
    }

    /**
     * Export tree as PDF via Browsershot
     */
    public function exportPdf(FamilyTree $treeModel, string $viewType = 'horizontal'): Response
    {
        $filename = Str::slug($treeModel->name).'-Silsilah';
        $rootMembers = $this->getRootMembers($treeModel);
        $allMembers = $treeModel->members;

        $html = view('tree.export-render', [
            'tree' => $treeModel,
            'rootMembers' => $rootMembers,
            'allMembers' => $allMembers,
            'viewType' => $viewType,
        ])->render();

        $html = $this->inlineImagesForExport($html);
        $chromePath = $this->resolveChromePath();

        $browsershot = Browsershot::html($html)
            ->setChromePath($chromePath)
            ->noSandbox()
            ->setOption('waitUntil', 'domcontentloaded')
            ->timeout(60)
            ->setDelay(300)
            ->windowSize(1920, 1080)
            ->showBackground();

        $pdfContent = $browsershot->pdf();

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.pdf\"");
    }

    /**
     * Export tree as PNG via Browsershot
     */
    public function exportPng(FamilyTree $treeModel, string $viewType = 'horizontal'): Response
    {
        $filename = Str::slug($treeModel->name).'-Silsilah';
        $rootMembers = $this->getRootMembers($treeModel);
        $allMembers = $treeModel->members;

        $html = view('tree.export-render', [
            'tree' => $treeModel,
            'rootMembers' => $rootMembers,
            'allMembers' => $allMembers,
            'viewType' => $viewType,
        ])->render();

        $html = $this->inlineImagesForExport($html);
        $chromePath = $this->resolveChromePath();

        $browsershot = Browsershot::html($html)
            ->setChromePath($chromePath)
            ->noSandbox()
            ->setOption('waitUntil', 'domcontentloaded')
            ->timeout(60)
            ->setDelay(300)
            ->windowSize(1920, 1080)
            ->showBackground();

        $imageContent = $browsershot->fullPage()->screenshot();

        return response($imageContent)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.png\"");
    }
}
