<?php

use App\Models\FamilyTree;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('shows public tree without password directly', function () {
    $tree = FamilyTree::factory()->create([
        'is_public' => true,
        'view_password' => null,
    ]);

    $this->get(route('tree.public', $tree->slug))
        ->assertSuccessful();
});

it('redirects to password form when tree has a password', function () {
    $tree = FamilyTree::factory()->create([
        'is_public' => false,
        'view_password' => Hash::make('secret123'),
    ]);

    $this->get(route('tree.public', $tree->slug))
        ->assertRedirect(route('tree.password', $tree->slug));
});

it('shows password form page', function () {
    $tree = FamilyTree::factory()->create([
        'view_password' => Hash::make('secret123'),
    ]);

    $this->get(route('tree.password', $tree->slug))
        ->assertSuccessful()
        ->assertSee('Silsilah Dilindungi Password');
});

it('redirects from password form when no password set', function () {
    $tree = FamilyTree::factory()->create([
        'view_password' => null,
    ]);

    $this->get(route('tree.password', $tree->slug))
        ->assertRedirect(route('tree.public', $tree->slug));
});

it('unlocks tree with correct hashed password via livewire', function () {
    $tree = FamilyTree::factory()->create([
        'view_password' => Hash::make('secret123'),
    ]);

    Livewire\Livewire::test('public-tree-password', ['slug' => $tree->slug])
        ->set('password', 'secret123')
        ->call('submit')
        ->assertRedirect(route('tree.public', $tree->slug));

    expect(session("tree_unlocked_{$tree->id}"))->toBeTrue();
});

it('rejects wrong password via livewire', function () {
    $tree = FamilyTree::factory()->create([
        'view_password' => Hash::make('secret123'),
    ]);

    Livewire\Livewire::test('public-tree-password', ['slug' => $tree->slug])
        ->set('password', 'wrongpass')
        ->call('submit')
        ->assertHasErrors('password');
});

it('handles legacy plain text password and auto-upgrades', function () {
    $tree = FamilyTree::factory()->create([
        'view_password' => 'plaintext123',
    ]);

    Livewire\Livewire::test('public-tree-password', ['slug' => $tree->slug])
        ->set('password', 'plaintext123')
        ->call('submit')
        ->assertRedirect(route('tree.public', $tree->slug));

    // Verify password was upgraded to bcrypt
    $tree->refresh();
    expect(Hash::check('plaintext123', $tree->view_password))->toBeTrue();
});

it('allows authenticated user to access simple view', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_public' => true]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user)
        ->get(route('tree.simple', $tree->id))
        ->assertSuccessful();
});
