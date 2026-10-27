<?php

use App\Models\Order;
use App\Models\PricingPackage;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.duitku.merchant_code', 'DS12345');
    config()->set('services.duitku.api_key', 'test_api_key_123');
    config()->set('services.duitku.is_production', false);

    Http::fake([
        'https://api-sandbox.duitku.com/*' => Http::response([
            'statusCode' => '00',
            'statusMessage' => 'SUCCESS',
            'reference' => 'DTEST-REF-PRICE',
            'paymentUrl' => 'https://sandbox.duitku.com/payment/price123',
        ], 200),
    ]);
});

test('user can checkout dynamic package keluarga lengkap', function () {
    $user = User::factory()->create();
    $package = PricingPackage::where('slug', 'keluarga-lengkap')->firstOrFail();

    $response = $this->actingAs($user)->post(route('orders.checkout'), [
        'package_id' => $package->id,
    ]);

    $response->assertRedirect('https://sandbox.duitku.com/payment/price123');

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'package_id' => $package->id,
        'amount' => 25000,
        'quota_count' => 3,
        'status' => 'pending',
    ]);
});

test('user can checkout promo idul fitri once', function () {
    $user = User::factory()->create();
    $promoPackage = PricingPackage::where('slug', 'promo-idul-fitri')->firstOrFail();

    $response = $this->actingAs($user)->post(route('orders.checkout'), [
        'package_id' => $promoPackage->id,
    ]);

    $response->assertRedirect('https://sandbox.duitku.com/payment/price123');

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'package_id' => $promoPackage->id,
        'amount' => 10000,
        'quota_count' => 2,
    ]);
});

test('user cannot checkout promo idul fitri if already purchased and paid', function () {
    $user = User::factory()->create();
    $promoPackage = PricingPackage::where('slug', 'promo-idul-fitri')->firstOrFail();

    // Existing paid order for promo
    Order::create([
        'user_id' => $user->id,
        'package_id' => $promoPackage->id,
        'order_id' => 'ORD-EXISTING-PROMO',
        'amount' => 10000,
        'quota_count' => 2,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('orders.checkout'), [
            'package_id' => $promoPackage->id,
        ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'Anda sudah pernah menggunakan promo ini sebelumnya.');
});

test('inactive package cannot be checked out', function () {
    $user = User::factory()->create();
    $package = PricingPackage::create([
        'name' => 'Paket Tidak Aktif',
        'slug' => 'tidak-aktif',
        'tree_quota' => 10,
        'price' => 50000,
        'is_active' => false,
    ]);

    $response = $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('orders.checkout'), [
            'package_id' => $package->id,
        ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'Paket ini sedang tidak aktif.');
});
