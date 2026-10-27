<?php

use App\Models\Order;
use App\Models\PricingPackage;
use App\Models\User;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Http;

test('telegram service skips notification if not configured', function () {
    config()->set('services.telegram.bot_token', null);
    config()->set('services.telegram.admin_chat_id', null);

    Http::fake();

    $service = new TelegramNotificationService;
    expect($service->isConfigured())->toBeFalse();

    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'order_id' => 'ORD-TG-SKIP',
        'amount' => 10000,
        'quota_count' => 1,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $result = $service->sendOrderPaidNotification($order);
    expect($result)->toBeFalse();

    Http::assertNothingSent();
});

test('telegram service sends message when payment is paid', function () {
    config()->set('services.telegram.bot_token', '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11');
    config()->set('services.telegram.admin_chat_id', '987654321');

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $package = PricingPackage::where('slug', 'keluarga-lengkap')->first();
    $user = User::factory()->create([
        'name' => 'Fulan bin Fulan',
        'email' => 'fulan@example.com',
    ]);

    $order = Order::create([
        'user_id' => $user->id,
        'package_id' => $package?->id,
        'order_id' => 'ORD-TG-SUCCESS-01',
        'amount' => 25000,
        'quota_count' => 3,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $service = new TelegramNotificationService;
    $result = $service->sendOrderPaidNotification($order);

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.telegram.org/bot123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11/sendMessage')
            && $request['chat_id'] === '987654321'
            && str_contains($request['text'], 'ORD-TG-SUCCESS-01')
            && str_contains($request['text'], 'Fulan bin Fulan')
            && str_contains($request['text'], '25.000');
    });
});

test('telegram service handles http error gracefully without exception', function () {
    config()->set('services.telegram.bot_token', '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11');
    config()->set('services.telegram.admin_chat_id', '987654321');

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
    ]);

    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'order_id' => 'ORD-TG-FAIL',
        'amount' => 10000,
        'quota_count' => 1,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $service = new TelegramNotificationService;
    $result = $service->sendOrderPaidNotification($order);

    expect($result)->toBeFalse();
});
