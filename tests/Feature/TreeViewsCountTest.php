<?php

use App\Models\FamilyTree;
use App\Models\User;

it('increments views_count when public tree is visited', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create([
        'is_public' => true,
        'views_count' => 0,
    ]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // First visit: views_count becomes 1
    $this->get(route('tree.public', $tree->slug))
        ->assertOk();

    expect($tree->refresh()->views_count)->toBe(1);

    // Refresh in same session: views_count should not double count
    $this->get(route('tree.public', $tree->slug))
        ->assertOk();

    expect($tree->refresh()->views_count)->toBe(1);
});

it('does not increment views_count if private tree is accessed by guest', function () {
    $tree = FamilyTree::factory()->create([
        'is_public' => false,
        'views_count' => 0,
    ]);

    $this->get(route('tree.public', $tree->slug))
        ->assertNotFound();

    expect($tree->refresh()->views_count)->toBe(0);
});

it('increments views_count when public vertical or simple routes are visited', function () {
    $tree = FamilyTree::factory()->create([
        'is_public' => true,
        'views_count' => 0,
    ]);

    $this->get(route('tree.public.vertical', $tree->slug))
        ->assertOk();

    expect($tree->refresh()->views_count)->toBe(1);

    // Switching route within same session doesn't increment
    $this->get(route('tree.public.simple', $tree->slug))
        ->assertOk();

    expect($tree->refresh()->views_count)->toBe(1);
});
