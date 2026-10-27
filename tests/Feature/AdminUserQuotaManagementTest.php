<?php

use App\Models\User;
use Livewire\Livewire;

test('admin can view user management page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('Manajemen Pengguna & Kuota');
});

test('admin can search users by name and email', function () {
    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->create([
        'name' => 'Budi Santoso Spesifik',
        'email' => 'budisantoso@example.com',
    ]);
    $otherUser = User::factory()->create([
        'name' => 'Cahyo Wibowo',
        'email' => 'cahyo@example.com',
    ]);

    Livewire::actingAs($admin)
        ->test('⚡admin-users')
        ->set('search', 'Budi Santoso')
        ->assertSee('Budi Santoso Spesifik')
        ->assertDontSee('Cahyo Wibowo');
});

test('admin can adjust user tree quota manually', function () {
    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->create([
        'tree_quota' => 1,
    ]);

    Livewire::actingAs($admin)
        ->test('⚡admin-users')
        ->call('setTargetUser', $targetUser->id)
        ->set('newQuota', 3)
        ->set('reason', 'Kompensasi pembayaran QRIS')
        ->call('saveQuota');

    $targetUser->refresh();
    expect($targetUser->tree_quota)->toBe(3);

    $this->assertDatabaseHas('admin_activity_logs', [
        'admin_id' => $admin->id,
        'action' => 'quota_adjusted',
        'target_id' => $targetUser->id,
    ]);
});

test('admin can quickly add quota to user (+1)', function () {
    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->create([
        'tree_quota' => 2,
    ]);

    Livewire::actingAs($admin)
        ->test('⚡admin-users')
        ->call('quickAddQuota', $targetUser->id, 1);

    $targetUser->refresh();
    expect($targetUser->tree_quota)->toBe(3);

    $this->assertDatabaseHas('admin_activity_logs', [
        'admin_id' => $admin->id,
        'action' => 'quota_quick_added',
        'target_id' => $targetUser->id,
    ]);
});

test('admin can filter users by quota status', function () {
    $admin = User::factory()->admin()->create();
    $userWithQuota = User::factory()->create([
        'name' => 'User Banyak Kuota',
        'tree_quota' => 5,
    ]);
    $userExhausted = User::factory()->create([
        'name' => 'User Kuota Nol',
        'tree_quota' => 0,
    ]);

    Livewire::actingAs($admin)
        ->test('⚡admin-users')
        ->set('quotaFilter', 'exhausted')
        ->assertSee('User Kuota Nol')
        ->assertDontSee('User Banyak Kuota')
        ->set('quotaFilter', 'available')
        ->assertSee('User Banyak Kuota')
        ->assertDontSee('User Kuota Nol');
});

test('admin can toggle role and records audit log', function () {
    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->create([
        'role' => 'user',
    ]);

    Livewire::actingAs($admin)
        ->test('⚡admin-users')
        ->call('toggleAdminRole', $targetUser->id);

    $targetUser->refresh();
    expect($targetUser->role)->toBe('admin');

    $this->assertDatabaseHas('admin_activity_logs', [
        'admin_id' => $admin->id,
        'action' => 'role_changed',
        'target_id' => $targetUser->id,
    ]);
});
