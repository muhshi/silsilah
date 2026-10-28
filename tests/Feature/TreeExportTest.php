<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\User;
use App\Services\TreeExportService;
use Illuminate\Support\Facades\URL;

test('can export tree data as json', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Bahagia',
    ]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $member = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Budi',
        'last_name' => 'Santoso',
    ]);

    $response = $this->get(route('tree.export', [
        'tree' => $tree->slug,
        'format' => 'json',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/json');
    $response->assertJsonPath('tree.name', 'Keluarga Bahagia');
    $response->assertJsonFragment([
        'first_name' => 'Budi',
        'last_name' => 'Santoso',
    ]);
});

test('can export tree data as ai prompt markdown', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Bahagia',
    ]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Budi',
        'last_name' => 'Santoso',
    ]);

    $response = $this->get(route('tree.export', [
        'tree' => $tree->slug,
        'format' => 'prompt',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    $response->assertSee('Prompt AI: Diagram & Visual Silsilah Keluarga', false);
    $response->assertSee('Budi Santoso');
});

test('export rejects invalid format with 400', function () {
    $tree = FamilyTree::factory()->create();

    $response = $this->get(route('tree.export', [
        'tree' => $tree->slug,
        'format' => 'invalid_format',
    ]));

    $response->assertStatus(400);
});

test('export render requires valid signature', function () {
    $tree = FamilyTree::factory()->create();

    // Unsigned request should fail with 403
    $unsignedResponse = $this->get(route('tree.export.render', [
        'tree' => $tree->slug,
    ]));
    $unsignedResponse->assertStatus(403);

    // Signed request should succeed
    $signedUrl = URL::signedRoute('tree.export.render', [
        'tree' => $tree->slug,
    ]);
    $signedResponse = $this->get($signedUrl);
    $signedResponse->assertOk();
});

test('can preview book render with valid signature', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Bani Ahmad',
    ]);

    $kakek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ahmad',
        'last_name' => 'Dahlan',
        'gender' => 'male',
        'is_living' => false,
    ]);

    $anak = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Fauzi',
        'last_name' => 'Ahmad',
        'father_id' => $kakek->id,
        'gender' => 'male',
        'is_living' => true,
    ]);

    $signedUrl = URL::signedRoute('tree.export.render', [
        'tree' => $tree->slug,
        'format' => 'book',
    ]);

    $response = $this->get($signedUrl);
    $response->assertOk();
    $response->assertSee('BUKU SILSILAH KELUARGA');
    $response->assertSee('BANI AHMAD');
    $response->assertSee('MUKADIMAH & KEUTAMAAN SILATURAHMI');
    $response->assertSee('INDEKS ANGGOTA KELUARGA (A — Z)');
    $response->assertSee('Ahmad Dahlan');
    $response->assertSee('Fauzi Ahmad');
});

test('tree export service accurately calculates generations for book format', function () {
    $tree = FamilyTree::factory()->create(['name' => 'Keluarga Utama']);

    $kakek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'gender' => 'male',
    ]);

    $nenek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Nenek',
        'gender' => 'female',
    ]);

    Marriage::create([
        'husband_id' => $kakek->id,
        'wife_id' => $nenek->id,
        'is_current' => true,
    ]);

    $anak = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Anak',
        'father_id' => $kakek->id,
        'mother_id' => $nenek->id,
        'gender' => 'male',
    ]);

    $cucu = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Cucu',
        'father_id' => $anak->id,
        'gender' => 'female',
    ]);

    $service = new TreeExportService;
    $treeModel = $service->findTree($tree->id);
    $bookData = $service->prepareBookData($treeModel);

    expect($bookData['stats']['total_members'])->toBe(4)
        ->and($bookData['stats']['total_generations'])->toBe(3)
        ->and($bookData['memberGenerations'][$kakek->id])->toBe(1)
        ->and($bookData['memberGenerations'][$nenek->id])->toBe(1)
        ->and($bookData['memberGenerations'][$anak->id])->toBe(2)
        ->and($bookData['memberGenerations'][$cucu->id])->toBe(3);
});
