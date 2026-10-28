<?php

use App\Models\ActivityLog;
use App\Models\AdminActivityLog;
use App\Models\FamilyTree;
use App\Models\User;
use Livewire\Livewire;

it('blocks non-admin from accessing /admin/trees', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/trees')
        ->assertForbidden();
});

it('allows admin to visit /admin/trees and search trees', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.com']);
    $tree = FamilyTree::factory()->create(['name' => 'Pohon Silsilah Trah Budi']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($admin)
        ->get('/admin/trees')
        ->assertOk()
        ->assertSee('Pohon Silsilah Trah Budi');

    Livewire::test('admin-trees')
        ->set('search', 'Trah Budi')
        ->assertSee('Pohon Silsilah Trah Budi')
        ->set('search', 'NonExistentString')
        ->assertDontSee('Pohon Silsilah Trah Budi');
});

it('allows admin to toggle tree premium status directly from admin panel', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($admin);

    Livewire::test('admin-trees')
        ->call('openOverrideModal', $tree->id)
        ->set('overrideReason', 'bank_transfer')
        ->set('overrideNotes', 'Bukti transfer BCA via WhatsApp')
        ->call('saveOverride', true);

    expect($tree->refresh()->is_premium)->toBeTrue();

    // Verify dual logging
    expect(ActivityLog::where('family_tree_id', $tree->id)->where('action', 'admin_override_premium')->exists())->toBeTrue();
    expect(AdminActivityLog::where('target_id', $tree->id)->where('action', 'tree_premium_override')->exists())->toBeTrue();
});

it('allows admin to downgrade premium tree back to standard', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->premium()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($admin);

    Livewire::test('admin-trees')
        ->call('openOverrideModal', $tree->id)
        ->set('overrideReason', 'other')
        ->set('overrideNotes', 'Masa aktif habis / dikembalikan ke standar')
        ->call('saveOverride', false);

    expect($tree->refresh()->is_premium)->toBeFalse();
});

it('allows admin to filter trees by status', function () {
    $admin = User::factory()->admin()->create();
    $freeTree = FamilyTree::factory()->create(['name' => 'Pohon Standar Unik', 'is_premium' => false, 'is_public' => false]);
    $premiumTree = FamilyTree::factory()->premium()->create(['name' => 'Pohon Premium Unik', 'is_public' => true]);

    $this->actingAs($admin);

    Livewire::test('admin-trees')
        ->call('setFilter', 'premium')
        ->assertSee('Pohon Premium Unik')
        ->assertDontSee('Pohon Standar Unik')
        ->call('setFilter', 'free')
        ->assertSee('Pohon Standar Unik')
        ->assertDontSee('Pohon Premium Unik')
        ->call('setFilter', 'public')
        ->assertSee('Pohon Premium Unik')
        ->assertDontSee('Pohon Standar Unik')
        ->call('setFilter', 'private')
        ->assertSee('Pohon Standar Unik')
        ->assertDontSee('Pohon Premium Unik');
});
