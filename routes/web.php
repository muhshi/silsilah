<?php

use App\Http\Controllers\AdminBackupController;
use App\Http\Controllers\AdminImpersonationController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DuitkuCallbackController;
use App\Http\Controllers\DuitkuCheckoutController;
use App\Http\Controllers\ImageProxyController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\TreeCalendarController;
use App\Http\Controllers\TreeExportController;
use App\Models\FamilyTree;
use App\Services\DeviceDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $demoTree = FamilyTree::where('is_public', true)
        ->whereNull('view_password')
        ->first();

    return view('welcome', compact('demoTree'));
})->name('home');

// PWA Routes
Route::get('manifest.json', function () {
    return response(file_get_contents(public_path('manifest.json')), 200, [
        'Content-Type' => 'application/manifest+json',
    ]);
});

Route::get('sw.js', function () {
    return response(file_get_contents(public_path('sw.js')), 200, [
        'Content-Type' => 'application/javascript; charset=utf-8',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
});

// Legal & Compliance Pages
Route::view('privacy', 'pages.legal.privacy')->name('legal.privacy');
Route::view('terms', 'pages.legal.terms')->name('legal.terms');
Route::view('refund', 'pages.legal.refund')->name('legal.refund');

// SSO Routes
Route::get('auth/google', [SocialiteController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [SocialiteController::class, 'callback']);

// Invitation Accept Routes
Route::get('invitations/accept/{token}', [InvitationController::class, 'show'])->name('invitation.accept');
Route::match(['get', 'post'], 'invitations/accept/{token}/process', [InvitationController::class, 'accept'])->name('invitation.accept.process');

// Duitku Payment Gateway Routes
Route::post('api/duitku/callback', [DuitkuCallbackController::class, 'handle'])->name('duitku.callback');
Route::post('orders/checkout', [DuitkuCheckoutController::class, 'checkout'])->middleware(['auth'])->name('orders.checkout');
Route::post('orders/validate-code', [DuitkuCheckoutController::class, 'validateCode'])->middleware(['auth'])->name('orders.validate-code');

// Secure image proxy to bypass CORS for export (with SSRF protection)
Route::get('api/image-proxy', [ImageProxyController::class, 'proxy'])->name('image.proxy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('tree/{tree}', function (Request $request, $tree) {
        $treeModel = is_numeric($tree)
            ? FamilyTree::where('id', $tree)->first() ?? FamilyTree::where('slug', $tree)->firstOrFail()
            : FamilyTree::where('slug', $tree)->first() ?? FamilyTree::where('id', $tree)->firstOrFail();

        $viewType = DeviceDetector::resolveTreeViewType($request);

        if ($viewType === 'vertical') {
            return view('tree.vertical', ['id' => $treeModel->id, 'tree' => $treeModel]);
        }

        if ($viewType === 'simple') {
            return view('tree.simple', ['id' => $treeModel->id, 'tree' => $treeModel]);
        }

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

// Superadmin Area
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::view('users', 'admin.users')->name('users');
    Route::view('trees', 'admin.trees')->name('trees');
    Route::view('orders', 'admin.orders')->name('orders');
    Route::view('pricing', 'admin.pricing')->name('pricing');
    Route::view('vouchers', 'admin.vouchers')->name('vouchers');
    Route::view('activities', 'admin.activities')->name('activities');
    Route::view('settings', 'admin.settings')->name('settings');
    Route::get('backup/download', [AdminBackupController::class, 'download'])->name('backup.download');
    Route::post('impersonate/{user}', [AdminImpersonationController::class, 'impersonate'])->name('impersonate');
});

Route::post('admin/leave-impersonation', [AdminImpersonationController::class, 'leave'])
    ->middleware(['auth'])
    ->name('admin.leave-impersonation');

// Public tree view (no auth required) - Default is Horizontal
$recordTreeVisit = function (FamilyTree $tree) {
    $sessionKey = "tree_viewed_{$tree->id}";
    if (! session()->has($sessionKey)) {
        $tree->incrementViews();
        session()->put($sessionKey, true);
    }
};

Route::get('public/tree/{slug}', function (Request $request, $slug) use ($recordTreeVisit) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if (! $tree->is_public) {
        abort(404);
    }

    if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.password', $tree->slug);
    }

    $recordTreeVisit($tree);

    $viewType = DeviceDetector::resolveTreeViewType($request);

    return view('tree.public', ['id' => $tree->id, 'tree' => $tree, 'viewType' => $viewType]);
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

// ========== TREE EXPORT (Browsershot & Data) ==========
Route::get('tree/{tree}/export-render', [TreeExportController::class, 'renderPreview'])->name('tree.export.render');
Route::get('tree/{tree}/export/{format}', [TreeExportController::class, 'export'])->name('tree.export');

// ========== TREE CALENDAR (.ics feed) ==========
Route::get('tree/{tree}/calendar.ics', [TreeCalendarController::class, 'feed'])->name('tree.calendar.ics');

require __DIR__.'/settings.php';
