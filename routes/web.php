<?php

use App\Models\FamilyTree;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

Route::view('/', 'welcome')->name('home');

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

    Route::get('tree/{id}', function ($id) {
        return view('tree.show', compact('id'));
    })->name('tree.show');

    Route::get('tree/{id}/vertical', function ($id) {
        return view('tree.vertical', compact('id'));
    })->name('tree.vertical');

    Route::get('tree/{id}/simple', function ($id) {
        return view('tree.simple', compact('id'));
    })->name('tree.simple');
});

// Public tree view (no auth required) - Default is Horizontal
Route::get('public/tree/{slug}', function ($slug) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.password', $tree->slug);
    }

    return view('tree.public', ['id' => $tree->id, 'tree' => $tree, 'viewType' => 'horizontal']);
})->name('tree.public');

Route::get('public/tree/{slug}/vertical', function ($slug) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.password', $tree->slug);
    }

    return view('tree.public', ['id' => $tree->id, 'tree' => $tree, 'viewType' => 'vertical']);
})->name('tree.public.vertical');

Route::get('public/tree/{slug}/simple', function ($slug) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.password', $tree->slug);
    }

    return view('tree.public', ['id' => $tree->id, 'tree' => $tree, 'viewType' => 'simple']);
})->name('tree.public.simple');

// Password form for protected public trees
Route::get('public/tree/{slug}/password', function ($slug) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if (! $tree->view_password || session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.public', $tree->slug);
    }

    return view('tree.password', ['slug' => $slug, 'tree' => $tree]);
})->name('tree.password');

// ========== TREE EXPORT (Browsershot) ==========

// Render route — for preview/debugging (signed URL, no auth)
Route::get('tree/{id}/export-render', function (int $id) {
    if (! request()->hasValidSignature()) {
        abort(403);
    }

    $viewType = request()->query('view', 'horizontal');
    $tree = FamilyTree::with([
        'members',
        'members.marriagesAsHusband.wife',
        'members.marriagesAsWife.husband',
    ])->findOrFail($id);

    $allMembers = $tree->members;
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
        'tree' => $tree,
        'rootMembers' => $rootMembers,
        'allMembers' => $allMembers,
        'viewType' => $viewType,
    ]);
})->name('tree.export.render');

// Export trigger — generates PNG or PDF via Browsershot::html()
Route::get('tree/{id}/export/{format}', function (int $id, string $format) {
    set_time_limit(120);
    abort_unless(in_array($format, ['png', 'pdf']), 400);

    $tree = FamilyTree::with([
        'members',
        'members.marriagesAsHusband.wife',
        'members.marriagesAsWife.husband',
    ])->findOrFail($id);

    $viewType = request()->query('view', 'horizontal');
    $filename = Str::slug($tree->name).'-Silsilah';

    // Prepare tree data server-side (same logic as Livewire components)
    $allMembers = $tree->members;
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

    // Render HTML server-side (CSS already inlined by the view, no @vite)
    $html = view('tree.export-render', [
        'tree' => $tree,
        'rootMembers' => $rootMembers,
        'allMembers' => $allMembers,
        'viewType' => $viewType,
    ])->render();

    // ── Step 1: Inline local /storage/ images from disk ──
    $html = preg_replace_callback(
        '/src="(\/storage\/[^"]+)"/',
        function ($matches) {
            $filePath = public_path($matches[1]);
            if (file_exists($filePath)) {
                $mime = mime_content_type($filePath) ?: 'image/jpeg';

                return 'src="data:'.$mime.';base64,'.base64_encode(file_get_contents($filePath)).'"';
            }

            return $matches[0];
        },
        $html
    );

    // ── Step 2: Collect all remaining external image URLs ──
    preg_match_all('/src="(https?:\/\/[^"]+)"/', $html, $urlMatches);
    $externalUrls = array_unique($urlMatches[1] ?? []);

    // ── Step 3: Fetch ALL external images in parallel (~3s total) ──
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
            }
        }
    }

    // Remove onerror attributes (fallback images not needed in export)
    $html = preg_replace('/\s*onerror="[^"]*"/', '', $html);

    $chromePath = trim(shell_exec('node -e "console.log(require(\'puppeteer\').executablePath())"'));

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
            ->paperSize(594, 420) // A2 landscape in mm
            ->landscape()
            ->margins(10, 10, 10, 10)
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
