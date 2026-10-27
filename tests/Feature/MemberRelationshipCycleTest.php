<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

test('member model correctly detects all recursive descendant ids', function () {
    $tree = FamilyTree::factory()->create();

    // Generation 1: Kakek
    $kakek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'gender' => 'male',
    ]);

    // Generation 2: Ayah (child of Kakek)
    $ayah = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ayah',
        'gender' => 'male',
        'father_id' => $kakek->id,
    ]);

    // Generation 3: Cucu (child of Ayah)
    $cucu = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Cucu',
        'gender' => 'male',
        'father_id' => $ayah->id,
    ]);

    // Generation 4: Cicit (child of Cucu)
    $cicit = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Cicit',
        'gender' => 'female',
        'father_id' => $cucu->id,
    ]);

    $descendantsOfKakek = $kakek->getAllDescendantIds();

    expect($descendantsOfKakek->all())->toContain($ayah->id, $cucu->id, $cicit->id);
    expect($descendantsOfKakek)->toHaveCount(3);

    expect($cicit->isDescendantOf($kakek))->toBeTrue();
    expect($cucu->isDescendantOf($kakek))->toBeTrue();
    expect($ayah->isDescendantOf($kakek))->toBeTrue();
    expect($kakek->isDescendantOf($cucu))->toBeFalse();
});

test('member manager excludes descendants from parent selection dropdown', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $kakek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'gender' => 'male',
    ]);

    $ayah = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ayah',
        'gender' => 'male',
        'father_id' => $kakek->id,
    ]);

    $cucu = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Cucu',
        'gender' => 'male',
        'father_id' => $ayah->id,
    ]);

    // Independent member
    $paman = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Paman',
        'gender' => 'male',
    ]);

    // When editing Kakek, availableMembers should NOT include Kakek, Ayah, or Cucu
    $component = Livewire::actingAs($user)
        ->test('⚡member-manager', [
            'treeId' => $tree->id,
        ])
        ->dispatch('edit-member', id: $kakek->id);

    $available = $component->viewData('availableMembers');
    $availableIds = $available->pluck('id')->all();

    expect($availableIds)->not->toContain($kakek->id);
    expect($availableIds)->not->toContain($ayah->id);
    expect($availableIds)->not->toContain($cucu->id);
    expect($availableIds)->toContain($paman->id);
});

test('member manager prevents saving a descendant as a parent', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $kakek = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'gender' => 'male',
    ]);

    $anak = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Anak',
        'gender' => 'male',
        'father_id' => $kakek->id,
    ]);

    // Attempt to set Anak as father of Kakek
    Livewire::actingAs($user)
        ->test('⚡member-manager', [
            'treeId' => $tree->id,
        ])
        ->dispatch('edit-member', id: $kakek->id)
        ->set('father_id', $anak->id)
        ->call('save')
        ->assertHasErrors(['father_id']);

    $kakek->refresh();
    expect($kakek->father_id)->toBeNull();
});
