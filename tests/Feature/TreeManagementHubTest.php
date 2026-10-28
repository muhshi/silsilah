<?php

use App\Models\ActivityLog;
use App\Models\AdminActivityLog;
use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

it('renders tree management hub with statistics, license, logs, and collab tabs', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Keluarga Besar Sastrowardoyo']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->assertSee('Keluarga Besar Sastrowardoyo')
        ->assertSee('Ringkasan & Statistik')
        ->assertSee('Status & Lisensi')
        ->assertSee('Riwayat Aktivitas')
        ->assertSee('Kolaborasi')
        ->assertSee('Pengaturan')
        // Check statistics tab
        ->set('activeTab', 'stats')
        ->assertSee('Total Anggota')
        ->assertSee('Kunjungan Publik')
        // Admin override should NOT be visible to regular user
        ->assertDontSee('Super Admin Override');
});

it('renders admin quick override panel in hub when viewer is superadmin', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($admin);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('activeTab', 'license')
        ->assertSee('Super Admin Override')
        ->assertSee('Jadikan Pohon Premium Manual');
});

it('prevents regular users from executing admin premium override', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('overrideReason', 'cash_cod')
        ->call('adminOverridePremium', true)
        ->assertForbidden();

    expect($tree->fresh()->is_premium)->toBeFalse();
});

it('validates and executes admin premium override successfully', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($admin);

    // Test validation failure
    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('overrideReason', 'invalid_reason')
        ->call('adminOverridePremium', true)
        ->assertHasErrors(['overrideReason']);

    // Test successful override to premium
    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('overrideReason', 'cash_cod')
        ->set('overrideNotes', 'Pembayaran tunai di acara keluarga')
        ->call('adminOverridePremium', true)
        ->assertHasNoErrors();

    expect($tree->fresh()->is_premium)->toBeTrue();

    // Verify activity log & admin activity log
    $lastAdminLog = AdminActivityLog::latest()->first();
    expect($lastAdminLog)->not->toBeNull();
    expect($lastAdminLog->action)->toBe('tree_premium_override');
    expect($lastAdminLog->properties['reason'])->toBe('cash_cod');

    $lastTreeLog = ActivityLog::where('family_tree_id', $tree->id)->latest()->first();
    expect($lastTreeLog)->not->toBeNull();
    expect($lastTreeLog->action)->toBe('admin_override_premium');

    // Test revert back to standard
    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('overrideReason', 'other')
        ->set('overrideNotes', 'Reset permintaan user')
        ->call('adminOverridePremium', false)
        ->assertHasNoErrors();

    expect($tree->fresh()->is_premium)->toBeFalse();
});

it('calculates tree statistics accurately and optimizes query execution', function () {
    $tree = FamilyTree::factory()->create([
        'is_premium' => false,
        'views_count' => 42,
    ]);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'gender' => 'male',
        'is_living' => true,
    ]);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'gender' => 'female',
        'is_living' => false,
    ]);

    $stats = $tree->getStatistics();

    expect($stats['total_members'])->toBe(2);
    expect($stats['male_count'])->toBe(1);
    expect($stats['female_count'])->toBe(1);
    expect($stats['alive_count'])->toBe(1);
    expect($stats['deceased_count'])->toBe(1);
    expect($stats['views_count'])->toBe(42);
    expect($stats['quota_limit'])->toBe(FamilyTree::FREE_MEMBER_LIMIT);
    expect($stats['remaining_slots'])->toBe(FamilyTree::FREE_MEMBER_LIMIT - 2);
    expect($stats['is_premium'])->toBeFalse();
});

it('integrates management hub into tree views and dashboard', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Pohon Keluarga Sastro']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    // 1. Tree View (Horizontal)
    $response = $this->get(route('tree.show', $tree));
    $response->assertOk();
    $response->assertSee('Kelola Pohon (Dashboard & Statistik)');

    // 2. Tree Vertical View
    $response = $this->get(route('tree.vertical', $tree));
    $response->assertOk();
    $response->assertSee('Kelola Pohon (Dashboard & Statistik)');

    // 3. Tree Simple View
    $response = $this->get(route('tree.simple', $tree));
    $response->assertOk();
    $response->assertSee('Kelola Pohon (Dashboard & Statistik)');

    // 4. Dashboard Trees Component
    Livewire::test('⚡dashboard-trees')
        ->assertSee('Kelola Pohon (Dashboard & Statistik)');
});
