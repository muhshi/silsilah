<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

it('can export family tree as JSON', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $father = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Bapak',
        'gender' => 'male',
    ]);

    $mother = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ibu',
        'gender' => 'female',
    ]);

    $response = $this->actingAs($user)
        ->get(route('tree.export', ['id' => $tree->id, 'format' => 'json']));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/json');
    $response->assertJsonStructure([
        'tree' => ['id', 'name'],
        'members',
        'marriages',
    ]);
});

it('auto detects opposite gender when creating a spouse', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $maleMember = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ahmad',
        'gender' => 'male',
    ]);

    Livewire::test('⚡member-manager', ['treeId' => $tree->id])
        ->dispatch('create-member', targetId: $maleMember->id, relType: 'spouse_of')
        ->assertSet('gender', 'female')
        ->assertSet('relType', 'spouse_of');
});
