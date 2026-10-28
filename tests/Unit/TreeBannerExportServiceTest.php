<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Services\TreeBannerExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('prepare banner data correctly identifies root ancestor and root spouse', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Bani Hasyim',
    ]);

    $kakek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Hasyim',
        'last_name' => 'Asyari',
        'gender' => 'male',
        'is_living' => false,
    ]);

    $nenek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Khadijah',
        'last_name' => 'Binti Ahmad',
        'gender' => 'female',
        'is_living' => false,
    ]);

    Marriage::create([
        'husband_id' => $kakek->id,
        'wife_id' => $nenek->id,
        'is_current' => true,
    ]);

    $service = app(TreeBannerExportService::class);
    $data = $service->prepareBannerData($tree);

    expect($data['root']->id)->toBe($kakek->id)
        ->and($data['rootSpouse']->id)->toBe($nenek->id)
        ->and($data['totalMembers'])->toBe(2)
        ->and($data['stats']['total_generations'])->toBe(1);
});

test('prepare banner data organizes children as columns with vertical descendants cascade', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Besar Sastro',
    ]);

    // Gen 1: Kakek & Nenek
    $kakek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Sastro',
        'gender' => 'male',
        'is_living' => false,
    ]);
    $nenek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Siti',
        'gender' => 'female',
        'is_living' => false,
    ]);
    Marriage::create(['husband_id' => $kakek->id, 'wife_id' => $nenek->id, 'is_current' => true]);

    // Gen 2: Anak 1 (Budi) married to Ani
    $anak1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Budi',
        'last_name' => 'Sastro',
        'father_id' => $kakek->id,
        'mother_id' => $nenek->id,
        'order' => 1,
        'gender' => 'male',
        'is_living' => true,
    ]);
    $istriAnak1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ani',
        'last_name' => null,
        'gender' => 'female',
        'is_living' => true,
    ]);
    Marriage::create(['husband_id' => $anak1->id, 'wife_id' => $istriAnak1->id, 'is_current' => true]);

    // Gen 2: Anak 2 (Dewi) married to Joko
    $anak2 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Dewi',
        'last_name' => 'Sastro',
        'father_id' => $kakek->id,
        'mother_id' => $nenek->id,
        'order' => 2,
        'gender' => 'female',
        'is_living' => true,
    ]);
    $suamiAnak2 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Joko',
        'last_name' => null,
        'gender' => 'male',
        'is_living' => true,
    ]);
    Marriage::create(['husband_id' => $suamiAnak2->id, 'wife_id' => $anak2->id, 'is_current' => true]);

    // Gen 3: Cucu 1 from Anak 1 (Cahyo) married to Rini
    $cucu1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Cahyo',
        'last_name' => null,
        'father_id' => $anak1->id,
        'mother_id' => $istriAnak1->id,
        'order' => 1,
        'gender' => 'male',
        'is_living' => true,
    ]);
    $istriCucu1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Rini',
        'last_name' => null,
        'gender' => 'female',
        'is_living' => true,
    ]);
    Marriage::create(['husband_id' => $cucu1->id, 'wife_id' => $istriCucu1->id, 'is_current' => true]);

    // Gen 4: Cicit from Cucu 1 (Doni)
    $cicit1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Doni',
        'father_id' => $cucu1->id,
        'mother_id' => $istriCucu1->id,
        'gender' => 'male',
        'is_living' => true,
    ]);

    $service = app(TreeBannerExportService::class);
    $data = $service->prepareBannerData($tree);

    // Verify 2 columns (Anak 1 and Anak 2)
    expect($data['columns'])->toHaveCount(2)
        ->and($data['columns'][0]['child']->id)->toBe($anak1->id)
        ->and($data['columns'][0]['couple_label'])->toContain('Budi Sastro ⚭ Ani')
        ->and($data['columns'][1]['child']->id)->toBe($anak2->id)
        ->and($data['columns'][1]['couple_label'])->toContain('Joko ⚭ Dewi Sastro');

    // Verify Cucu and Cicit in Column 0
    $col0Descendants = $data['columns'][0]['descendants'];
    expect($col0Descendants)->toHaveCount(1)
        ->and($col0Descendants[0]['member']->id)->toBe($cucu1->id)
        ->and($col0Descendants[0]['couple_label'])->toContain('Cahyo ⚭ Rini')
        ->and($col0Descendants[0]['children'])->toHaveCount(1)
        ->and($col0Descendants[0]['children'][0]['member']->id)->toBe($cicit1->id);

    // Verify Statistics
    expect($data['stats']['total_members'])->toBe(9)
        ->and($data['stats']['total_generations'])->toBe(4)
        ->and($data['stats']['total_branches'])->toBe(2)
        ->and($data['qrCodeSvg'])->toContain('<svg');
});

test('format couple label merges husband and wife with marriage symbol', function () {
    $service = app(TreeBannerExportService::class);

    $husband = new Member([
        'first_name' => 'Ahmad',
        'last_name' => 'Dahlan',
        'gender' => 'male',
        'is_living' => false,
    ]);

    $wife = new Member([
        'first_name' => 'Siti',
        'last_name' => 'Walidah',
        'gender' => 'female',
        'is_living' => false,
    ]);

    $label = $service->formatCoupleLabel($husband, $wife);
    expect($label)->toBe('Ahmad Dahlan (Alm.) ⚭ Siti Walidah (Almh.)');

    // Reverse argument order should still place husband first
    $labelReversed = $service->formatCoupleLabel($wife, $husband);
    expect($labelReversed)->toBe('Ahmad Dahlan (Alm.) ⚭ Siti Walidah (Almh.)');

    // Unmarried single person
    $single = new Member([
        'first_name' => 'Fauzi',
        'gender' => 'male',
        'is_living' => true,
    ]);
    expect($service->formatCoupleLabel($single))->toBe('Fauzi');
});
