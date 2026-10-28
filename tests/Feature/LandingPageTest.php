<?php

use App\Models\FamilyTree;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('landing page returns 200 OK', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('landing page displays showcase of new features', function () {
    $response = $this->get(route('home'));

    $response->assertOk();

    // 1. Kalkulator Nasab & Kekerabatan
    $response->assertSee('Kalkulator Nasab');
    $response->assertSee('Kekerabatan');
    $response->assertSee('Kalkulator Kekerabatan & Nasab');

    // 2. Pengingat Haul & Milad / Ulang Tahun
    $response->assertSee('Pengingat Haul');
    $response->assertSee('Haul & Ulang Tahun');
    $response->assertSee('Pengingat Haul & Milad Keluarga');

    // 3. Ekspor Buku Silsilah PDF
    $response->assertSee('Buku Silsilah');
    $response->assertSee('Ekspor Buku Silsilah PDF');

    // 4. Gotong Royong / Usulan Data Reuni
    $response->assertSee('Gotong Royong');
    $response->assertSee('Usulan Data');
    $response->assertSee('Gotong Royong Data Saat Reuni');

    // 5. PWA & Mobile
    $response->assertSee('Otomatis Mode HP & Bisa Di-install (PWA)');

    // 6. Privasi & Keamanan
    $response->assertSee('Privasi & Keamanan Keluarga');

    // CTAs
    $response->assertSee('Mulai Buat Silsilah Gratis');
    $response->assertSee('Lihat Demo');
    $response->assertSee('Coba Demo');
    $response->assertSee('Lihat Demo Interaktif');
});

test('landing page demo CTA resolves to active public tree and renders successfully', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Raden Demak',
        'slug' => 'keluarga-raden-demak',
        'is_public' => true,
        'view_password' => null,
    ]);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Raden',
        'last_name' => 'Fatah',
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    // The demo CTA points to the public tree route
    $demoUrl = route('tree.public', $tree->slug);
    $response->assertSee($demoUrl);

    // Visiting the demo link renders the public tree successfully
    $demoResponse = $this->get($demoUrl);
    $demoResponse->assertOk();
    $demoResponse->assertSee('Keluarga Raden Demak');
});

test('landing page falls back to demo slug if no public tree exists in database', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Lihat Demo Interaktif');
    $response->assertSee(route('tree.public', 'demo'));
});
