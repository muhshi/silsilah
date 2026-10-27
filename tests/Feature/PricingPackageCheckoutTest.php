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

test('package outside seasonal schedule cannot be checked out', function () {
    $user = User::factory()->create();

    // Past package (expired)
    $expiredPackage = PricingPackage::create([
        'name' => 'Promo Lebaran Tahun Lalu',
        'slug' => 'promo-lebaran-lalu',
        'tree_quota' => 2,
        'price' => 10000,
        'is_active' => true,
        'starts_at' => now()->subMonths(2),
        'ends_at' => now()->subMonth(),
    ]);

    $response = $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('orders.checkout'), [
            'package_id' => $expiredPackage->id,
        ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'Paket ini berada di luar periode promo yang ditentukan.');

    // Future package (not started yet)
    $futurePackage = PricingPackage::create([
        'name' => 'Promo Lebaran Tahun Depan',
        'slug' => 'promo-lebaran-depan',
        'tree_quota' => 2,
        'price' => 10000,
        'is_active' => true,
        'starts_at' => now()->addMonth(),
        'ends_at' => now()->addMonths(2),
    ]);

    $response2 = $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('orders.checkout'), [
            'package_id' => $futurePackage->id,
        ]);

    $response2->assertRedirect(route('dashboard'));
    $response2->assertSessionHas('error', 'Paket ini berada di luar periode promo yang ditentukan.');
});

test('scopeActive filters out packages outside seasonal schedule', function () {
    $expired = PricingPackage::create([
        'name' => 'Expired Promo',
        'slug' => 'expired-promo',
        'tree_quota' => 1,
        'price' => 10000,
        'is_active' => true,
        'starts_at' => now()->subWeeks(4),
        'ends_at' => now()->subWeek(),
    ]);

    $upcoming = PricingPackage::create([
        'name' => 'Upcoming Promo',
        'slug' => 'upcoming-promo',
        'tree_quota' => 1,
        'price' => 10000,
        'is_active' => true,
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeeks(4),
    ]);

    $current = PricingPackage::create([
        'name' => 'Current Seasonal Promo',
        'slug' => 'current-seasonal-promo',
        'tree_quota' => 1,
        'price' => 10000,
        'is_active' => true,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->addDays(10),
    ]);

    $activeSlugs = PricingPackage::active()->pluck('slug')->toArray();

    expect($activeSlugs)->toContain('current-seasonal-promo')
        ->and($activeSlugs)->not->toContain('expired-promo')
        ->and($activeSlugs)->not->toContain('upcoming-promo');
});
