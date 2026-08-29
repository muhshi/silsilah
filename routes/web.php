<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\InvitationController;
use App\Models\FamilyTree;
use App\Models\Marriage;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

Route::view('/', 'welcome')->name('home');

// SSO Routes
Route::get('auth/google', [SocialiteController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [SocialiteController::class, 'callback']);

// Invitation Accept Routes
Route::get('invitations/accept/{token}', [InvitationController::class, 'show'])->name('invitation.accept');
Route::match(['get', 'post'], 'invitations/accept/{token}/process', [InvitationController::class, 'accept'])->name('invitation.accept.process');

// Image proxy to bypass CORS for export
Route::get('api/image-proxy', function () {
    $url = request()->query('url');

    if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
        abort(400);
    }

    try {
        $response = Http::timeout(10)->get($url);

        return response($response->body(), 200)
            ->header('Content-Type', $response->header('Content-Type') ?: 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=86400');
    } catch (Exception $e) {
        abort(502);
    }
})->name('image.proxy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('tree/{tree}', function ($tree) {
        $treeModel = is_numeric($tree)
            ? FamilyTree::where('id', $tree)->first() ?? FamilyTree::where('slug', $tree)->firstOrFail()
            : FamilyTree::where('slug', $tree)->first() ?? FamilyTree::where('id', $tree)->firstOrFail();

        return view('tree.show', ['id' => $treeModel->id, 'tree' => $treeModel]);
    })->name('tree.show');

    Route::get('tree/{tree}/vertical', function ($tree) {
        $treeModel = is_numeric($tree)
            ? FamilyTree::where('id', $tree)->first() ?? FamilyTree::where('slug', $tree)->firstOrFail()
            : FamilyTree::where('slug', $tree)->first() ?? FamilyTree::where('id', $tree)->firstOrFail();

        return view('tree.vertical', ['id' => $treeModel->id, 'tree' => $treeModel]);
    })->name('tree.vertical');

    Route::get('tree/{tree}/simple', function ($tree) {
        $treeModel = is_numeric($tree)
            ? FamilyTree::where('id', $tree)->first() ?? FamilyTree::where('slug', $tree)->firstOrFail()
            : FamilyTree::where('slug', $tree)->first() ?? FamilyTree::where('id', $tree)->firstOrFail();

        return view('tree.simple', ['id' => $treeModel->id, 'tree' => $treeModel]);
    })->name('tree.simple');
});

// Public tree view (no auth required) - Default is Horizontal
Route::get('public/tree/{slug}', function ($slug) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if (! $tree->is_public) {
        abort(404);
    }

    if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.password', $tree->slug);
    }

    return view('tree.public', ['id' => $tree->id, 'tree' => $tree, 'viewType' => 'horizontal']);
})->name('tree.public');

Route::get('public/tree/{slug}/vertical', function ($slug) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if (! $tree->is_public) {
        abort(404);
    }

    if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.password', $tree->slug);
    }

    return view('tree.public', ['id' => $tree->id, 'tree' => $tree, 'viewType' => 'vertical']);
})->name('tree.public.vertical');

Route::get('public/tree/{slug}/simple', function ($slug) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if (! $tree->is_public) {
        abort(404);
    }

    if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.password', $tree->slug);
    }

    return view('tree.public', ['id' => $tree->id, 'tree' => $tree, 'viewType' => 'simple']);
})->name('tree.public.simple');

// Password form for protected public trees
Route::get('public/tree/{slug}/password', function ($slug) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if (! $tree->is_public) {
        abort(404);
    }

    if (! $tree->view_password || session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.public', $tree->slug);
    }

    return view('tree.password', ['slug' => $slug, 'tree' => $tree]);
})->name('tree.password');

// ========== TREE EXPORT (Browsershot) ==========

// Render route — for preview/debugging (signed URL, no auth)
Route::get('tree/{tree}/export-render', function ($tree) {
    if (! request()->hasValidSignature()) {
        abort(403);
    }

    $viewType = request()->query('view', 'horizontal');
    $treeModel = is_numeric($tree)
        ? FamilyTree::with([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ])->find($tree) ?? FamilyTree::with([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ])->where('slug', $tree)->firstOrFail()
        : FamilyTree::with([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ])->where('slug', $tree)->first() ?? FamilyTree::with([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ])->findOrFail($tree);

    $allMembers = $treeModel->members;
    $wifeIdsInMarriages = collect();
    foreach ($allMembers as $m) {
        if ($m->gender === 'male') {
            foreach ($m->marriagesAsHusband as $marriage) {
                $wifeIdsInMarriages->push($marriage->wife_id);
            }
        }
    }
    $parentless = $allMembers->whereNull('father_id')->whereNull('mother_id');
    $rootMembers = $parentless->whereNotIn('id', $wifeIdsInMarriages->unique());

    return view('tree.export-render', [
        'tree' => $treeModel,
        'rootMembers' => $rootMembers,
        'allMembers' => $allMembers,
        'viewType' => $viewType,
    ]);
})->name('tree.export.render');

// Export trigger — generates PNG, PDF, JSON, or Prompt AI (.md)
Route::get('tree/{tree}/export/{format}', function ($tree, string $format) {
    set_time_limit(120);
    abort_unless(in_array($format, ['png', 'pdf', 'json', 'prompt']), 400);

    $treeModel = is_numeric($tree)
        ? FamilyTree::with([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ])->find($tree) ?? FamilyTree::with([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ])->where('slug', $tree)->firstOrFail()
        : FamilyTree::with([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ])->where('slug', $tree)->first() ?? FamilyTree::with([
            'members',
            'members.marriagesAsHusband.wife',
            'members.marriagesAsWife.husband',
        ])->findOrFail($tree);

    $filename = Str::slug($treeModel->name).'-Silsilah';
    $allMembers = $treeModel->members;

    if ($format === 'prompt') {
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

    if ($format === 'json') {
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

    $viewType = request()->query('view', 'horizontal');

    // Prepare tree data server-side (same logic as Livewire components)
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
    $rootMembers = $allMembers->whereNotIn('id', $nonRootIds->unique());

    // Render HTML server-side (CSS already inlined by the view, no @vite)
    $html = view('tree.export-render', [
        'tree' => $treeModel,
        'rootMembers' => $rootMembers,
        'allMembers' => $allMembers,
        'viewType' => $viewType,
    ])->render();

    // ── Step 1: Base64 fallback image ──
    $defaultAvatarFile = public_path('images/no_profile_pic.jpg');
    $defaultAvatarBase64 = file_exists($defaultAvatarFile)
        ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($defaultAvatarFile))
        : '';

    // ── Step 2: Inline local images from disk (storage, images, avatar) ──
    $html = preg_replace_callback(
        '/src="([^"]+)"/',
        function ($matches) use ($defaultAvatarBase64) {
            $src = $matches[1];
            if (str_starts_with($src, 'data:')) {
                return $matches[0];
            }

            // Extract relative URL path
            $urlPath = parse_url($src, PHP_URL_PATH);
            if ($urlPath) {
                $filePath = public_path(ltrim($urlPath, '/'));
                if (file_exists($filePath) && is_file($filePath)) {
                    $mime = mime_content_type($filePath) ?: 'image/jpeg';

                    return 'src="data:'.$mime.';base64,'.base64_encode(file_get_contents($filePath)).'"';
                }
            }

            // If it's a localhost URL that wasn't found, fallback to default avatar
            if (str_contains($src, 'localhost') || str_starts_with($src, '/')) {
                return $defaultAvatarBase64 ? 'src="'.$defaultAvatarBase64.'"' : $matches[0];
            }

            return $matches[0];
        },
        $html
    );

    // ── Step 3: Collect any remaining external image URLs (e.g. CDN/HTTPS) ──
    preg_match_all('/src="(https?:\/\/[^"]+)"/', $html, $urlMatches);
    $externalUrls = array_filter(array_unique($urlMatches[1] ?? []), fn ($u) => ! str_contains($u, 'localhost'));

    // ── Step 4: Fetch external images in parallel (~3s total) ──
    if (count($externalUrls) > 0) {
        $responses = Http::pool(fn ($pool) => collect($externalUrls)->map(
            fn ($url) => $pool->as(md5($url))->timeout(3)->get($url)
        )->all());

        foreach ($externalUrls as $url) {
            $key = md5($url);
            if (isset($responses[$key])
                && $responses[$key] instanceof Response
                && $responses[$key]->successful()) {
                $mime = $responses[$key]->header('Content-Type') ?: 'image/jpeg';
                $dataUri = 'data:'.$mime.';base64,'.base64_encode($responses[$key]->body());
                $html = str_replace('src="'.$url.'"', 'src="'.$dataUri.'"', $html);
            } elseif ($defaultAvatarBase64) {
                $html = str_replace('src="'.$url.'"', 'src="'.$defaultAvatarBase64.'"', $html);
            }
        }
    }

    // Remove onerror attributes (fallback images not needed in export)
    $html = preg_replace('/\s*onerror="[^"]*"/', '', $html);

    $chromePaths = [
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', // macOS
        '/usr/bin/google-chrome', // Linux
        '/usr/bin/google-chrome-stable', // Linux
        '/usr/bin/chromium-browser', // Linux
        '/usr/bin/chromium', // Linux
    ];

    $chromePath = null;
    foreach ($chromePaths as $path) {
        if (file_exists($path)) {
            $chromePath = $path;
            break;
        }
    }

    if (! $chromePath) {
        // Fallback to puppeteer executable path if system chrome not found
        $chromePath = trim(shell_exec('node -e "console.log(require(\'puppeteer\').executablePath())"'));
    }

    $browsershot = Browsershot::html($html)
        ->setChromePath($chromePath)
        ->noSandbox()
        ->setOption('waitUntil', 'domcontentloaded')
        ->timeout(60)
        ->setDelay(300)
        ->windowSize(1920, 1080)
        ->showBackground();

    if ($format === 'pdf') {
        $pdfContent = $browsershot
            ->pdf();

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.pdf\"");
    }

    $imageContent = $browsershot->fullPage()->screenshot();

    return response($imageContent)
        ->header('Content-Type', 'image/png')
        ->header('Content-Disposition', "attachment; filename=\"{$filename}.png\"");
})->name('tree.export');

require __DIR__.'/settings.php';
