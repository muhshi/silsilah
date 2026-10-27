<?php

use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

test('admin can view orders management page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/orders')
        ->assertOk()
        ->assertSee('Manajemen Transaksi Duitku');
});

test('admin can filter orders by status', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $paidOrder = Order::create([
        'user_id' => $user->id,
        'order_id' => 'ORDER-PAID-001',
        'amount' => 10000,
        'quota_count' => 1,
        'status' => 'paid',
        'payment_method' => 'QRIS',
    ]);

    $pendingOrder = Order::create([
        'user_id' => $user->id,
        'order_id' => 'ORDER-PENDING-002',
        'amount' => 10000,
        'quota_count' => 1,
        'status' => 'pending',
        'payment_method' => 'QRIS',
    ]);

    Livewire::actingAs($admin)
        ->test('⚡admin-orders')
        ->set('statusFilter', 'pending')
        ->assertSee('ORDER-PENDING-002')
        ->assertDontSee('ORDER-PAID-001');
});

test('admin can manually mark order as paid and automatically grant quota', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create([
        'tree_quota' => 1,
    ]);

    $order = Order::create([
        'user_id' => $customer->id,
        'order_id' => 'ORDER-MANUAL-VERIFY',
        'amount' => 10000,
        'quota_count' => 1,
        'status' => 'pending',
        'payment_method' => 'QRIS',
    ]);

    Livewire::actingAs($admin)
        ->test('⚡admin-orders')
        ->call('manualVerifyOrder', $order->id);

    $order->refresh();
    $customer->refresh();

    expect($order->status)->toBe('paid')
        ->and($customer->tree_quota)->toBe(2);
});
