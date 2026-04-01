<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    
    Route::get('tree/{id}', function($id) {
        return view('tree.show', compact('id'));
    })->name('tree.show');

    Route::get('tree/{id}/vertical', function($id) {
        return view('tree.vertical', compact('id'));
    })->name('tree.vertical');
});

// Public tree view (no auth required)
Route::get('public/tree/{slug}', function($slug) {
    $tree = \App\Models\FamilyTree::where('slug', $slug)->where('is_public', true)->firstOrFail();
    return view('tree.public', ['id' => $tree->id, 'tree' => $tree]);
})->name('tree.public');

require __DIR__.'/settings.php';
