<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Services\TreeBannerExportService;
use Illuminate\Support\Facades\URL;
use Mockery\MockInterface;

test('banner preview render requires valid signature', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Sudirman',
    ]);

    // Unsigned request fails with 403
    $unsignedResponse = $this->get(route('tree.export.render', [
        'tree' => $tree->slug,
        'format' => 'banner',
    ]));
    $unsignedResponse->assertStatus(403);

    // Signed request succeeds with 200
    $signedUrl = URL::signedRoute('tree.export.render', [
        'tree' => $tree->slug,
        'format' => 'banner',
    ]);
    $signedResponse = $this->get($signedUrl);
    $signedResponse->assertOk();
});

test('banner preview renders complete poster layout with root, branches, hadith and qr code', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Bani Ahmad Dahlan',
    ]);

    // Gen 1: Kakek & Nenek
    $kakek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ahmad',
        'last_name' => 'Dahlan',
        'gender' => 'male',
        'is_living' => false,
    ]);
    $nenek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Siti',
        'last_name' => 'Walidah',
        'gender' => 'female',
        'is_living' => false,
    ]);
    Marriage::create(['husband_id' => $kakek->id, 'wife_id' => $nenek->id, 'is_current' => true]);

    // Gen 2: Anak 1
    $anak1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Siradj',
        'last_name' => 'Dahlan',
        'father_id' => $kakek->id,
        'mother_id' => $nenek->id,
        'order' => 1,
        'gender' => 'male',
        'is_living' => false,
    ]);
    $istriAnak1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ruqayyah',
        'last_name' => null,
        'gender' => 'female',
        'is_living' => false,
    ]);
    Marriage::create(['husband_id' => $anak1->id, 'wife_id' => $istriAnak1->id, 'is_current' => true]);

    // Gen 3: Cucu 1
    $cucu1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Hanan',
        'last_name' => null,
        'father_id' => $anak1->id,
        'mother_id' => $istriAnak1->id,
        'order' => 1,
        'gender' => 'male',
        'is_living' => true,
    ]);

    $signedUrl = URL::signedRoute('tree.export.render', [
        'tree' => $tree->slug,
        'format' => 'banner',
    ]);

    $response = $this->get($signedUrl);
    $response->assertOk();

    // Verify Title & Header
    $response->assertSee('SILSILAH KELUARGA BESAR');
    $response->assertSee('Bani Ahmad Dahlan');
    $response->assertSee('بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ');

    // Verify Hadith
    $response->assertSee('Barangsiapa yang ingin dilapangkan rezekinya dan dipanjangkan umurnya, hendaklah ia menyambung tali silaturahmi.');
    $response->assertSee('HR. Al-Bukhari & Muslim');

    // Verify Leluhur Utama Showcase
    $response->assertSee('Leluhur Pendiri (Generasi I)');
    $response->assertSee('Ahmad Dahlan (Alm.)');
    $response->assertSee('Siti Walidah (Almh.)');

    // Verify Column and Couple line
    $response->assertSee('Anak Ke-1');
    $response->assertSee('Siradj Dahlan (Alm.) ⚭ Ruqayyah (Almh.)');

    // Verify Descendant
    $response->assertSee('Hanan');

    // Verify QR Code container
    $response->assertSee('Versi Digital');
    $response->assertSee('<svg', false);
});

test('banner preview handles empty tree gracefully', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Pohon Kosong',
    ]);

    $signedUrl = URL::signedRoute('tree.export.render', [
        'tree' => $tree->slug,
        'format' => 'banner',
    ]);

    $response = $this->get($signedUrl);
    $response->assertOk();
    $response->assertSee('Pohon Kosong');
    $response->assertSee('Belum ada cabang keturunan (Anak/Cucu).');
});

test('export endpoint handles banner format', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Cendana',
    ]);

    // Mock TreeBannerExportService to avoid calling real Chromium/Puppeteer in headless CI
    $this->mock(TreeBannerExportService::class, function (MockInterface $mock) use ($tree) {
        $mock->shouldReceive('exportBannerPdf')
            ->once()
            ->andReturn(
                response('fake-pdf-content', 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => "attachment; filename=\"poster-silsilah-{$tree->slug}.pdf\"",
                ])
            );
    });

    $response = $this->get(route('tree.export', [
        'tree' => $tree->slug,
        'format' => 'banner',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Disposition', "attachment; filename=\"poster-silsilah-{$tree->slug}.pdf\"");
    expect($response->getContent())->toBe('fake-pdf-content');
});
