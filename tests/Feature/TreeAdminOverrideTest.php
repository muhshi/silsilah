<?php

use App\Models\ActivityLog;
use App\Models\AdminActivityLog;
use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

it('provides comprehensive tree statistics', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['views_count' => 42]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // Create 3 male (2 alive, 1 deceased) and 2 female (alive) members
    Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'is_alive' => true]);
    Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'is_alive' => true]);
    Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'is_alive' => false]);
    Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'is_alive' => true]);
    Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'is_alive' => true]);

    $stats = $tree->getStatistics();

    expect($stats['total_members'])->toBe(5)
        ->and($stats['male_count'])->toBe(3)
        ->and($stats['female_count'])->toBe(2)
        ->and($stats['alive_count'])->toBe(4)
        ->and($stats['deceased_count'])->toBe(1)
        ->and($stats['views_count'])->toBe(42);
});

it('allows admin to manually override free tree to premium with reason and logs', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($admin);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('overrideReason', 'cash_cod')
        ->set('overrideNotes', 'Bayar tunai Rp 5.000 pas acara temu trah')
        ->call('adminOverridePremium', true)
        ->assertHasNoErrors();

    expect($tree->refresh()->is_premium)->toBeTrue();

    // Verify ActivityLog entry on tree
    $activity = ActivityLog::where('family_tree_id', $tree->id)
        ->where('action', 'admin_override_premium')
        ->first();
    expect($activity)->not->toBeNull()
        ->and($activity->description)->toContain('Bayar tunai Rp 5.000');

    // Verify AdminActivityLog entry
    $adminLog = AdminActivityLog::where('target_id', $tree->id)
        ->where('action', 'tree_premium_override')
        ->first();
    expect($adminLog)->not->toBeNull()
        ->and($adminLog->admin_id)->toBe($admin->id);
});

it('blocks non-admin user from executing admin override', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('overrideReason', 'cash_cod')
        ->call('adminOverridePremium', true)
        ->assertForbidden();

    expect($tree->refresh()->is_premium)->toBeFalse();
});
