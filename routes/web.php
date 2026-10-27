<?php

use App\Http\Controllers\AdminBackupController;
use App\Http\Controllers\AdminImpersonationController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DuitkuCallbackController;
use App\Http\Controllers\DuitkuCheckoutController;
use App\Http\Controllers\ImageProxyController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\TreeExportController;
use App\Models\FamilyTree;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

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

// Secure image proxy to bypass CORS for export (with SSRF protection)
Route::get('api/image-proxy', [ImageProxyController::class, 'proxy'])->name('image.proxy');

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

// Superadmin Area
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::view('users', 'admin.users')->name('users');
    Route::view('orders', 'admin.orders')->name('orders');
    Route::view('pricing', 'admin.pricing')->name('pricing');
    Route::view('activities', 'admin.activities')->name('activities');
    Route::view('settings', 'admin.settings')->name('settings');
    Route::get('backup/download', [AdminBackupController::class, 'download'])->name('backup.download');
    Route::post('impersonate/{user}', [AdminImpersonationController::class, 'impersonate'])->name('impersonate');
});

Route::post('admin/leave-impersonation', [AdminImpersonationController::class, 'leave'])
    ->middleware(['auth'])
    ->name('admin.leave-impersonation');

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

// ========== TREE EXPORT (Browsershot & Data) ==========
Route::get('tree/{tree}/export-render', [TreeExportController::class, 'renderPreview'])->name('tree.export.render');
Route::get('tree/{tree}/export/{format}', [TreeExportController::class, 'export'])->name('tree.export');

require __DIR__.'/settings.php';
