<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('image proxy endpoint enforces rate limiting after 60 requests', function () {
    Http::fake([
        'https://example.com/*' => Http::response('fake-image-bytes', 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    for ($i = 0; $i < 60; $i++) {
        $this->get(route('image.proxy', ['url' => 'https://example.com/test.jpg']));
    }

    $response = $this->get(route('image.proxy', ['url' => 'https://example.com/test.jpg']));
    $response->assertStatus(429);
});

test('orders validate code endpoint enforces rate limiting after 10 requests', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($user)->post(route('orders.validate-code'), [
            'code' => 'TESTCODE',
        ]);
    }

    $response = $this->actingAs($user)->post(route('orders.validate-code'), [
        'code' => 'TESTCODE',
    ]);
    $response->assertStatus(429);
});

test('orders checkout endpoint enforces rate limiting after 10 requests', function () {
    Http::fake([
        'https://api-sandbox.duitku.com/*' => Http::response([
            'statusCode' => '00',
            'statusMessage' => 'SUCCESS',
            'reference' => 'DTEST-REF-999',
            'paymentUrl' => 'https://sandbox.duitku.com/payment/xyz123',
        ], 200),
    ]);

    $user = User::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($user)->post(route('orders.checkout'), [
            'custom_quota' => 1,
        ]);
    }

    $response = $this->actingAs($user)->post(route('orders.checkout'), [
        'custom_quota' => 1,
    ]);
    $response->assertStatus(429);
});
