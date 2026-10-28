<?php

use App\Models\FamilyTree;
use App\Models\User;
use App\Services\DeviceDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

const IPHONE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
const ANDROID_UA = 'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.144 Mobile Safari/537.36';
const MAC_DESKTOP_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
const WINDOWS_DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

test('DeviceDetector correctly identifies mobile devices vs desktops', function () {
    $mobileRequest1 = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => IPHONE_UA]);
    $mobileRequest2 = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => ANDROID_UA]);
    $desktopRequest1 = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => MAC_DESKTOP_UA]);
    $desktopRequest2 = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => WINDOWS_DESKTOP_UA]);
    $emptyRequest = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => '']);

    expect(DeviceDetector::isMobile($mobileRequest1))->toBeTrue()
        ->and(DeviceDetector::isMobile($mobileRequest2))->toBeTrue()
        ->and(DeviceDetector::isMobile($desktopRequest1))->toBeFalse()
        ->and(DeviceDetector::isMobile($desktopRequest2))->toBeFalse()
        ->and(DeviceDetector::isMobile($emptyRequest))->toBeFalse();
});

test('DeviceDetector resolves viewType according to query param and device', function () {
    $mobileRequest = Request::create('/tree/1', 'GET', [], [], [], ['HTTP_USER_AGENT' => IPHONE_UA]);
    $desktopRequest = Request::create('/tree/1', 'GET', [], [], [], ['HTTP_USER_AGENT' => MAC_DESKTOP_UA]);

    // Default: Mobile gets vertical (Fokus), Desktop gets horizontal (Canvas)
    expect(DeviceDetector::resolveTreeViewType($mobileRequest))->toBe('vertical')
        ->and(DeviceDetector::resolveTreeViewType($desktopRequest))->toBe('horizontal');

    // Explicit override via query parameters
    $mobileCanvas = Request::create('/tree/1', 'GET', ['view' => 'canvas'], [], [], ['HTTP_USER_AGENT' => IPHONE_UA]);
    $desktopVertical = Request::create('/tree/1', 'GET', ['view' => 'vertical'], [], [], ['HTTP_USER_AGENT' => MAC_DESKTOP_UA]);
    $simpleView = Request::create('/tree/1', 'GET', ['view' => 'simple'], [], [], ['HTTP_USER_AGENT' => IPHONE_UA]);

    expect(DeviceDetector::resolveTreeViewType($mobileCanvas))->toBe('horizontal')
        ->and(DeviceDetector::resolveTreeViewType($desktopVertical))->toBe('vertical')
        ->and(DeviceDetector::resolveTreeViewType($simpleView))->toBe('simple');
});

test('authenticated user on mobile receives Fokus (tree.vertical) by default', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $response = $this->actingAs($user)
        ->withHeaders(['User-Agent' => IPHONE_UA])
        ->get(route('tree.show', $tree));

    $response->assertOk();
    $response->assertViewIs('tree.vertical');
});

test('authenticated user on desktop receives Canvas (tree.show) by default', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $response = $this->actingAs($user)
        ->withHeaders(['User-Agent' => MAC_DESKTOP_UA])
        ->get(route('tree.show', $tree));

    $response->assertOk();
    $response->assertViewIs('tree.show');
});

test('authenticated user on mobile can access Canvas with explicit view=canvas parameter', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $response = $this->actingAs($user)
        ->withHeaders(['User-Agent' => IPHONE_UA])
        ->get(route('tree.show', ['tree' => $tree, 'view' => 'canvas']));

    $response->assertOk();
    $response->assertViewIs('tree.show');
});

test('public tree on mobile displays Fokus vertical view by default', function () {
    $tree = FamilyTree::factory()->create([
        'is_public' => true,
        'slug' => 'bani-mochtar-public',
    ]);

    $response = $this->withHeaders(['User-Agent' => ANDROID_UA])
        ->get(route('tree.public', $tree->slug));

    $response->assertOk();
    $response->assertViewIs('tree.public');
    $response->assertViewHas('viewType', 'vertical');
});

test('public tree on desktop displays Canvas horizontal view by default', function () {
    $tree = FamilyTree::factory()->create([
        'is_public' => true,
        'slug' => 'bani-mochtar-public',
    ]);

    $response = $this->withHeaders(['User-Agent' => MAC_DESKTOP_UA])
        ->get(route('tree.public', $tree->slug));

    $response->assertOk();
    $response->assertViewIs('tree.public');
    $response->assertViewHas('viewType', 'horizontal');
});

test('public tree on mobile displays Canvas when view=canvas parameter is given', function () {
    $tree = FamilyTree::factory()->create([
        'is_public' => true,
        'slug' => 'bani-mochtar-public',
    ]);

    $response = $this->withHeaders(['User-Agent' => IPHONE_UA])
        ->get(route('tree.public', ['slug' => $tree->slug, 'view' => 'canvas']));

    $response->assertOk();
    $response->assertViewIs('tree.public');
    $response->assertViewHas('viewType', 'horizontal');
});
