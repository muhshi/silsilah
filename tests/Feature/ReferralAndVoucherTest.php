<?php

use App\Models\PricingPackage;
use App\Models\User;
use App\Models\Voucher;
use App\Services\DuitkuService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('services.duitku.merchant_code', 'DS12345');
    config()->set('services.duitku.api_key', 'test_api_key_123');
    config()->set('services.duitku.is_production', false);

    Http::fake([
        'https://api-sandbox.duitku.com/*' => Http::response([
            'statusCode' => '00',
            'statusMessage' => 'SUCCESS',
            'reference' => 'DTEST-REF-PROMO',
            'paymentUrl' => 'https://sandbox.duitku.com/payment/promo123',
        ], 200),
    ]);
});

test('user model automatically generates unique referral code on creation', function () {
    $user = User::factory()->create();

    expect($user->referral_code)->not->toBeNull()
        ->and($user->referral_code)->toStartWith('SIL-')
        ->and(strlen($user->referral_code))->toBe(10);
});

test('visiting site with referral parameter captures referral code in session', function () {
    $referrer = User::factory()->create();

    $response = $this->get('/?ref='.$referrer->referral_code);
    $response->assertSessionHas('referral_code', $referrer->referral_code);
});

test('buyer can apply 15% discount using friend referral code on regular package', function () {
    $referrer = User::factory()->create();
    $buyer = User::factory()->create();

    $package = PricingPackage::where('slug', 'satuan')->firstOrFail(); // Rp 10.000

    $response = $this->actingAs($buyer)->post(route('orders.checkout'), [
        'package_id' => $package->id,
        'code' => $referrer->referral_code,
    ]);

    $response->assertRedirect('https://sandbox.duitku.com/payment/promo123');

    // Expected 15% discount: 10.000 - 1.500 = 8.500
    $this->assertDatabaseHas('orders', [
        'user_id' => $buyer->id,
        'package_id' => $package->id,
        'original_amount' => 10000,
        'discount_amount' => 1500,
        'amount' => 8500,
        'referral_code' => $referrer->referral_code,
        'referrer_id' => $referrer->id,
        'quota_count' => 1,
    ]);
});

test('user cannot use self referral code', function () {
    $user = User::factory()->create();
    $package = PricingPackage::where('slug', 'satuan')->firstOrFail();

    $response = $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('orders.checkout'), [
            'package_id' => $package->id,
            'code' => $user->referral_code,
        ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'Anda tidak dapat menggunakan kode referral milik sendiri.');
});

test('referral code cannot be stacked with BOGO package', function () {
    $referrer = User::factory()->create();
    $buyer = User::factory()->create();
    $bogoPackage = PricingPackage::where('slug', 'promo-idul-fitri')->firstOrFail();

    $response = $this->actingAs($buyer)
        ->from(route('dashboard'))
        ->post(route('orders.checkout'), [
            'package_id' => $bogoPackage->id,
            'code' => $referrer->referral_code,
        ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'Diskon referral tidak dapat digabungkan dengan promo Beli 1 Gratis 1.');
});

test('buyer can purchase custom quota with dynamic pricing', function () {
    $user = User::factory()->create();

    // 4 trees = 4 * 8.500 = Rp 34.000
    $response = $this->actingAs($user)->post(route('orders.checkout'), [
        'custom_quota' => 4,
    ]);

    $response->assertRedirect('https://sandbox.duitku.com/payment/promo123');

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'quota_count' => 4,
        'amount' => 34000,
        'is_custom_quota' => true,
    ]);
});

test('referrer receives +1 tree quota once referee completes paid order', function () {
    $referrer = User::factory()->create(['tree_quota' => 1]);
    $buyer = User::factory()->create(['tree_quota' => 1]);

    $package = PricingPackage::where('slug', 'satuan')->firstOrFail();

    $order = app(DuitkuService::class)->createInvoice(
        user: $buyer,
        quotaCount: 1,
        amount: 8500,
        packageId: $package->id,
        originalAmount: 10000,
        discountAmount: 1500,
        referralCode: $referrer->referral_code,
        referrerId: $referrer->id
    );

    // Process payment
    app(DuitkuService::class)->processSuccessfulPayment($order->order_id, ['paymentCode' => 'QRIS']);

    $buyer->refresh();
    $referrer->refresh();

    // Buyer got +1 tree quota
    expect($buyer->tree_quota)->toBe(2);

    // Referrer got +1 free tree quota bonus!
    expect($referrer->tree_quota)->toBe(2);
});

test('system voucher applies discount correctly', function () {
    $user = User::factory()->create();
    $package = PricingPackage::where('slug', 'satuan')->firstOrFail(); // 10.000

    $voucher = Voucher::create([
        'code' => 'TESTVOUCHER20',
        'name' => 'Diskon 20%',
        'type' => 'percentage',
        'value' => 20,
        'min_amount' => 10000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('orders.checkout'), [
        'package_id' => $package->id,
        'code' => 'TESTVOUCHER20',
    ]);

    $response->assertRedirect('https://sandbox.duitku.com/payment/promo123');

    // 20% of 10.000 = 2.000 discount, net 8.000
    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'voucher_code' => 'TESTVOUCHER20',
        'discount_amount' => 2000,
        'amount' => 8000,
    ]);
});

test('validate code endpoint returns real-time calculations', function () {
    $referrer = User::factory()->create();
    $buyer = User::factory()->create();
    $package = PricingPackage::where('slug', 'keluarga-lengkap')->firstOrFail(); // Rp 25.000

    $response = $this->actingAs($buyer)->postJson(route('orders.validate-code'), [
        'code' => $referrer->referral_code,
        'package_id' => $package->id,
    ]);

    $response->assertOk();
    $response->assertJson([
        'valid' => true,
        'type' => 'referral',
        'discount' => 3750, // 15% of 25.000
        'original_amount' => 25000,
        'final_amount' => 21250,
    ]);
});

test('superadmin can manage vouchers in admin panel', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.vouchers'));
    $response->assertOk();
    $response->assertSee('Voucher & Kode Diskon');

    Livewire::actingAs($admin)
        ->test('admin-vouchers')
        ->set('code', 'SUPERHEMAT50')
        ->set('name', 'Diskon 50% Gila')
        ->set('type', 'percentage')
        ->set('value', 50)
        ->set('min_amount', 10000)
        ->call('saveVoucher');

    expect(Voucher::where('code', 'SUPERHEMAT50')->exists())->toBeTrue();
});
