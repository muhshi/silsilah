<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public tree renders dynamic OpenGraph and Twitter meta tags for WhatsApp previews', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Bani Mochtar',
        'is_public' => true,
        'slug' => 'bani-mochtar',
        'description' => 'Silsilah keturunan H. Mochtar Demak',
    ]);

    $response = $this->get(route('tree.public', $tree->slug));

    $response->assertOk();
    $response->assertSee('<title>Bani Mochtar — Pohon Silsilah Keluarga</title>', false);
    $response->assertSee('<meta property="og:title" content="Bani Mochtar — Silsilah Keluarga"', false);
    $response->assertSee('<meta property="og:description" content="Silsilah keturunan H. Mochtar Demak"', false);
    $response->assertSee('<meta property="og:url" content="'.route('tree.public', $tree->slug).'"', false);
    $response->assertSee('<meta property="og:image" content="'.asset('images/logo.png').'"', false);
    $response->assertSee('<meta name="twitter:card" content="summary"', false);
    $response->assertSee('<meta name="twitter:title" content="Bani Mochtar — Silsilah Keluarga"', false);
    $response->assertSee('<meta name="twitter:description" content="Silsilah keturunan H. Mochtar Demak"', false);
    $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
    $response->assertSee('<meta name="theme-color" content="#059669">', false);
    $response->assertSee('navigator.serviceWorker.register(\'/sw.js\')', false);
});

test('public tree without description provides friendly member count fallback in og:description', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Bani Salim',
        'is_public' => true,
        'slug' => 'bani-salim',
        'description' => null,
    ]);

    Member::factory()->count(3)->create([
        'family_tree_id' => $tree->id,
    ]);

    $response = $this->get(route('tree.public', $tree->slug));

    $response->assertOk();
    $response->assertSee('<meta property="og:title" content="Bani Salim — Silsilah Keluarga"', false);
    $response->assertSee('<meta property="og:description" content="Bagan silsilah dan direktori keluarga Bani Salim (3 anggota)."', false);
});

test('manifest.json endpoint returns valid PWA manifest structure', function () {
    $response = $this->get('/manifest.json');

    $response->assertOk();
    $data = $response->json();

    expect($data)->toBeArray()
        ->and($data['name'])->toBe('Silsilah Keluarga')
        ->and($data['short_name'])->toBe('Silsilah')
        ->and($data['start_url'])->toBe('/')
        ->and($data['display'])->toBe('standalone')
        ->and($data['theme_color'])->toBe('#059669')
        ->and($data['icons'])->toBeArray()
        ->and(count($data['icons']))->toBeGreaterThanOrEqual(2);

    $sizes = array_column($data['icons'], 'sizes');
    expect($sizes)->toContain('192x192')
        ->and($sizes)->toContain('512x512');
});

test('sw.js endpoint serves lightweight service worker script', function () {
    $response = $this->get('/sw.js');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
    expect($response->getContent())->toContain('CACHE_NAME')
        ->and($response->getContent())->toContain('self.addEventListener');
});

test('authenticated dashboard layout includes PWA manifest and theme meta', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
    $response->assertSee('<meta name="theme-color" content="#059669">', false);
    $response->assertSee('navigator.serviceWorker.register(\'/sw.js\')', false);
});

test('welcome landing page includes PWA manifest and service worker registration', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
    $response->assertSee('<meta name="theme-color" content="#059669">', false);
    $response->assertSee('navigator.serviceWorker.register(\'/sw.js\')', false);
});
