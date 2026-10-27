<?php

use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

test('admin can switch date range filter on dashboard', function () {
    $admin = User::factory()->admin()->create();

    // Past order (15 days ago)
    $pastUser = User::factory()->create([
        'created_at' => now()->subDays(15),
    ]);
    Order::create([
        'user_id' => $pastUser->id,
        'order_id' => 'ORDER-PAST-15D',
        'amount' => 50000,
        'quota_count' => 5,
        'status' => 'paid',
        'payment_method' => 'QRIS',
        'created_at' => now()->subDays(15),
    ]);

    // Today order
    $todayUser = User::factory()->create([
        'created_at' => now(),
    ]);
    Order::create([
        'user_id' => $todayUser->id,
        'order_id' => 'ORDER-TODAY-01',
        'amount' => 10000,
        'quota_count' => 1,
        'status' => 'paid',
        'payment_method' => 'QRIS',
        'created_at' => now(),
    ]);

    // Test 'all': should include both (Rp 60.000)
    Livewire::actingAs($admin)
        ->test('⚡admin-dashboard')
        ->set('period', 'all')
        ->assertSee('60.000');

    // Test 'today': should only include today (Rp 10.000)
    Livewire::actingAs($admin)
        ->test('⚡admin-dashboard')
        ->set('period', 'today')
        ->assertSee('10.000')
        ->assertSee('Periode: Hari Ini');

    // Test '7d': today order included (Rp 10.000), past order excluded
    Livewire::actingAs($admin)
        ->test('⚡admin-dashboard')
        ->set('period', '7d')
        ->assertSee('10.000')
        ->assertSee('Periode: 7 Hari Terakhir');

    // Test '30d': both included (Rp 60.000)
    Livewire::actingAs($admin)
        ->test('⚡admin-dashboard')
        ->set('period', '30d')
        ->assertSee('60.000')
        ->assertSee('Periode: 30 Hari Terakhir');
});
