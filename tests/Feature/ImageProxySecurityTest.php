<?php

use Illuminate\Support\Facades\Http;

test('image proxy requires a valid url parameter', function () {
    $response = $this->get(route('image.proxy'));
    $response->assertStatus(400);

    $invalidResponse = $this->get(route('image.proxy', ['url' => 'not-a-valid-url']));
    $invalidResponse->assertStatus(400);
});

test('image proxy blocks non-http schemes', function () {
    $response = $this->get(route('image.proxy', ['url' => 'file:///etc/passwd']));
    $response->assertStatus(400);
});

test('image proxy blocks private and loopback ip addresses (SSRF prevention)', function (string $dangerousUrl) {
    $response = $this->get(route('image.proxy', ['url' => $dangerousUrl]));

    // Must be rejected with 403 Forbidden
    $response->assertStatus(403);
})->with([
    'http://127.0.0.1/secret',
    'http://localhost/admin',
    'http://10.0.0.1/internal',
    'http://192.168.1.1/router',
    'http://169.254.169.254/latest/meta-data',
    'http://0.0.0.0/test',
]);

test('image proxy fetches valid public image and returns caching headers', function () {
    $fakeImageUrl = 'https://example.com/avatar.jpg';
    $fakeImageBytes = 'fake-binary-image-data';

    Http::fake([
        $fakeImageUrl => Http::response($fakeImageBytes, 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $response = $this->get(route('image.proxy', ['url' => $fakeImageUrl]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/jpeg');
    $response->assertHeader('Cache-Control', 'max-age=86400, public');
    expect($response->getContent())->toBe($fakeImageBytes);
});

test('image proxy rejects non-image content type', function () {
    $fakeUrl = 'https://example.com/secret.json';

    Http::fake([
        $fakeUrl => Http::response('{"secret":"data"}', 200, [
            'Content-Type' => 'application/json',
        ]),
    ]);

    $response = $this->get(route('image.proxy', ['url' => $fakeUrl]));

    $response->assertStatus(422);
});
