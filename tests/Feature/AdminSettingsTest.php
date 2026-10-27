<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('superadmin can access settings page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.settings'));
    $response->assertOk();
    $response->assertSee('Pengaturan & Integrasi Bot');
    $response->assertSee('Telegram Bot Token');
});

test('non-admin cannot access settings page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.settings'));
    $response->assertForbidden();
});

test('admin can save telegram settings', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('admin-settings')
        ->set('telegram_bot_token', '999888:TEST_TOKEN_XYZ')
        ->set('telegram_admin_chat_id', '123456789')
        ->set('telegram_enabled', true)
        ->call('saveSettings');

    expect(Setting::get('telegram_bot_token'))->toBe('999888:TEST_TOKEN_XYZ')
        ->and(Setting::get('telegram_admin_chat_id'))->toBe('123456789')
        ->and(Setting::get('telegram_enabled'))->toBe('1');
});

test('admin can auto-detect chat id via telegram api', function () {
    $admin = User::factory()->admin()->create();

    Http::fake([
        'https://api.telegram.org/*/getUpdates' => Http::response([
            'ok' => true,
            'result' => [
                [
                    'update_id' => 12345,
                    'message' => [
                        'chat' => ['id' => 888777666],
                        'from' => [
                            'first_name' => 'Fulan',
                            'username' => 'fulan_tg',
                        ],
                        'text' => '/start',
                    ],
                ],
            ],
        ], 200),
    ]);

    Livewire::actingAs($admin)
        ->test('admin-settings')
        ->set('telegram_bot_token', '999888:TEST_TOKEN_XYZ')
        ->call('autoDetectChatId')
        ->assertSet('telegram_admin_chat_id', '888777666');

    expect(Setting::get('telegram_admin_chat_id'))->toBe('888777666');
});

test('admin can send test ping to telegram', function () {
    $admin = User::factory()->admin()->create();

    Http::fake([
        'https://api.telegram.org/*/sendMessage' => Http::response([
            'ok' => true,
            'result' => ['message_id' => 99],
        ], 200),
    ]);

    Livewire::actingAs($admin)
        ->test('admin-settings')
        ->set('telegram_bot_token', '999888:TEST_TOKEN_XYZ')
        ->set('telegram_admin_chat_id', '888777666')
        ->call('sendTestPing')
        ->assertSee('Pesan uji coba berhasil dikirim');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.telegram.org/bot999888:TEST_TOKEN_XYZ/sendMessage')
            && $request['chat_id'] === '888777666';
    });
});
