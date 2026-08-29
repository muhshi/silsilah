<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

it('can navigate focus and switch tabs in vertical mobile view', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Bani Soekamto']);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $grandfather = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'KakekLegendaris',
        'gender' => 'male',
    ]);

    $father = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Bambang',
        'last_name' => 'Soekamto',
        'gender' => 'male',
        'father_id' => $grandfather->id,
    ]);

    $mother = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Siti',
        'gender' => 'female',
    ]);

    Marriage::create([
        'husband_id' => $father->id,
        'wife_id' => $mother->id,
    ]);

    $child = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Rian',
        'last_name' => 'Bambang',
        'gender' => 'male',
        'father_id' => $father->id,
        'mother_id' => $mother->id,
    ]);

    $this->actingAs($user);

    // Test tree-vertical component
    Livewire::test('tree-vertical', ['id' => $tree->id])
        ->assertSee('Bani Soekamto')
        ->assertSee('Fokus Explorer')
        ->assertSee('Direktori')
        ->call('focusOn', $father->id)
        ->assertSet('focusMemberId', $father->id)
        ->assertSet('tab', 'explorer')
        ->assertSee('KakekLegendaris')
        ->assertSee('Bambang')
        ->assertSee('Rian')
        ->call('setTab', 'directory')
        ->assertSet('tab', 'directory')
        ->set('search', 'Rian')
        ->assertSee('Rian Bambang')
        ->set('search', 'TidakAdaOrangIniSamaSekali')
        ->assertSee('Tidak ada anggota yang cocok dengan pencarian.');
});

it('can add a new parent using relType parent_of', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $person = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Andi',
        'gender' => 'male',
        'father_id' => null,
        'mother_id' => null,
    ]);

    $this->actingAs($user);

    // Create Father via parent_of relation
    Livewire::test('⚡member-manager', ['treeId' => $tree->id])
        ->dispatch('create-member', targetId: $person->id, relType: 'parent_of')
        ->set('first_name', 'Pak Budi')
        ->set('gender', 'male')
        ->call('save')
        ->assertHasNoErrors();

    $person->refresh();
    $father = Member::where('first_name', 'Pak Budi')->first();

    expect($father)->not->toBeNull()
        ->and($person->father_id)->toBe($father->id);

    // Create Mother via parent_of relation
    Livewire::test('⚡member-manager', ['treeId' => $tree->id])
        ->dispatch('create-member', targetId: $person->id, relType: 'parent_of')
        ->set('first_name', 'Ibu Ratna')
        ->set('gender', 'female')
        ->call('save')
        ->assertHasNoErrors();

    $person->refresh();
    $mother = Member::where('first_name', 'Ibu Ratna')->first();

    expect($mother)->not->toBeNull()
        ->and($person->mother_id)->toBe($mother->id);
});
