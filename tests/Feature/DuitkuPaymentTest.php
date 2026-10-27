<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('unauthenticated user cannot initiate checkout', function () {
    $response = $this->post(route('orders.checkout'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can initiate checkout with Duitku', function () {
    config()->set('services.duitku.merchant_code', 'DS12345');
    config()->set('services.duitku.api_key', 'test_api_key_123');
    config()->set('services.duitku.is_production', false);

    Http::fake([
        'https://api-sandbox.duitku.com/*' => Http::response([
            'statusCode' => '00',
            'statusMessage' => 'SUCCESS',
            'reference' => 'DTEST-REF-999',
            'paymentUrl' => 'https://sandbox.duitku.com/payment/xyz123',
        ], 200),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('orders.checkout'));

    $response->assertRedirect('https://sandbox.duitku.com/payment/xyz123');

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'amount' => 10000,
        'quota_count' => 1,
        'status' => 'pending',
        'reference' => 'DTEST-REF-999',
    ]);
});

test('duitku callback with valid hmac signature updates order and adds user quota', function () {
    $apiKey = 'test_api_key_123';
    $merchantCode = 'DS12345';
    config()->set('services.duitku.merchant_code', $merchantCode);
    config()->set('services.duitku.api_key', $apiKey);

    $user = User::factory()->create([
        'tree_quota' => 1,
    ]);

    $order = Order::create([
        'user_id' => $user->id,
        'order_id' => 'SILSILAH-ORD-TEST-001',
        'amount' => 10000,
        'quota_count' => 1,
        'status' => 'pending',
    ]);

    $amount = '10000';
    $signature = hash_hmac('sha256', $merchantCode.$amount.$order->order_id, $apiKey);

    $response = $this->post(route('duitku.callback'), [
        'merchantCode' => $merchantCode,
        'amount' => $amount,
        'merchantOrderId' => $order->order_id,
        'resultCode' => '00',
        'reference' => 'DUITKU-REF-12345',
        'signature' => $signature,
        'paymentCode' => 'SP',
    ]);

    $response->assertOk();
    $response->assertSee('OK');

    $order->refresh();
    expect($order->status)->toBe('success');
    expect($order->paid_at)->not->toBeNull();

    $user->refresh();
    expect($user->tree_quota)->toBe(2);
});

test('duitku callback rejects invalid signature', function () {
    config()->set('services.duitku.merchant_code', 'DS12345');
    config()->set('services.duitku.api_key', 'test_api_key_123');

    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'order_id' => 'SILSILAH-ORD-TEST-002',
        'amount' => 10000,
        'quota_count' => 1,
        'status' => 'pending',
    ]);

    $response = $this->post(route('duitku.callback'), [
        'merchantCode' => 'DS12345',
        'amount' => '10000',
        'merchantOrderId' => $order->order_id,
        'resultCode' => '00',
        'signature' => 'invalid_signature_here',
    ]);

    $response->assertStatus(400);
    $response->assertSee('Bad Signature');

    $order->refresh();
    expect($order->status)->toBe('pending');
});
