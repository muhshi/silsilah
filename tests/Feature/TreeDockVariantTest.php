<?php

use App\Models\FamilyTree;
use App\Models\User;

test('authenticated user can visit dock variant view (Option 2)', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Bani Soekamto']);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    $response = $this->get(route('tree.dock', $tree));
    $response->assertOk()
        ->assertSee('Bani Soekamto')
        ->assertSee('tree-view')
        ->assertSee('Standar')
        ->assertSee('Bagan')
        ->assertSee('Fokus')
        ->assertSee('Simpel')
        ->assertSee('Kalender')
        ->assertSee('bottom-4 sm:bottom-6');
});

test('public tree can be viewed with dock variant', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Publik',
        'slug' => 'keluarga-publik',
        'is_public' => true,
    ]);

    $response = $this->get(route('tree.public.dock', $tree->slug));
    $response->assertOk()
        ->assertSee('Keluarga Publik')
        ->assertSee('Standar')
        ->assertSee('Bagan')
        ->assertSee('Fokus')
        ->assertSee('Simpel')
        ->assertSee('bottom-4 sm:bottom-6');
});

test('password protected public tree redirects to password page on dock route', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Rahasia',
        'slug' => 'keluarga-rahasia',
        'is_public' => true,
        'view_password' => 'secret123',
    ]);

    $response = $this->get(route('tree.public.dock', $tree->slug));
    $response->assertRedirect(route('tree.password', $tree->slug));
});

test('private tree returns 404 on public dock route', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Privat',
        'slug' => 'keluarga-privat',
        'is_public' => false,
    ]);

    $response = $this->get(route('tree.public.dock', $tree->slug));
    $response->assertNotFound();
});
