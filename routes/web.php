<?php

use App\Models\FamilyTree;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

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

// Public tree view (no auth required)
Route::get('public/tree/{slug}', function ($slug) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    // If tree has a password and session not verified → redirect to password form
    if ($tree->view_password && ! session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.password', $tree->slug);
    }

    return view('tree.public', ['id' => $tree->id, 'tree' => $tree]);
})->name('tree.public');

// Password form for protected public trees
Route::get('public/tree/{slug}/password', function ($slug) {
    $tree = FamilyTree::where('slug', $slug)->firstOrFail();

    if (! $tree->view_password || session("tree_unlocked_{$tree->id}")) {
        return redirect()->route('tree.public', $tree->slug);
    }

    return view('tree.password', ['slug' => $slug, 'tree' => $tree]);
})->name('tree.password');

require __DIR__.'/settings.php';
