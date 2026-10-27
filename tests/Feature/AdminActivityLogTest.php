<?php

use App\Models\AdminActivityLog;
use App\Models\User;
use Livewire\Livewire;

test('admin can access activity log page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/activities')
        ->assertOk()
        ->assertSee('Log Aktivitas Administrator');
});

test('admin can filter and search activity logs', function () {
    $admin = User::factory()->admin()->create();

    AdminActivityLog::log(
        action: 'quota_adjusted',
        description: 'Menambahkan kuota khusus pelanggan VIP',
        targetType: 'User',
        targetId: $admin->id
    );

    AdminActivityLog::log(
        action: 'role_changed',
        description: 'Perubahan role user menjadi admin sistem',
        targetType: 'User',
        targetId: $admin->id
    );

    Livewire::actingAs($admin)
        ->test('⚡admin-activities')
        ->assertSee('Menambahkan kuota khusus pelanggan VIP')
        ->assertSee('Perubahan role user menjadi admin sistem')
        ->set('actionFilter', 'quota')
        ->assertSee('Menambahkan kuota khusus pelanggan VIP')
        ->assertDontSee('Perubahan role user menjadi admin sistem')
        ->set('actionFilter', 'role')
        ->assertSee('Perubahan role user menjadi admin sistem')
        ->assertDontSee('Menambahkan kuota khusus pelanggan VIP')
        ->set('actionFilter', 'all')
        ->set('search', 'VIP')
        ->assertSee('Menambahkan kuota khusus pelanggan VIP')
        ->assertDontSee('Perubahan role user menjadi admin sistem');
});

test('non-admin cannot access activity log page', function () {
    $user = User::factory()->create([
        'role' => 'user',
    ]);

    $this->actingAs($user)
        ->get('/admin/activities')
        ->assertForbidden();
});
