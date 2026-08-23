<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
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

it('allows selecting mother when creating a child for a father with multiple wives', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $father = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Ayah']);
    $wife1 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'first_name' => 'Istri 1']);
    $wife2 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'female', 'first_name' => 'Istri 2']);

    Marriage::create(['husband_id' => $father->id, 'wife_id' => $wife1->id]);
    Marriage::create(['husband_id' => $father->id, 'wife_id' => $wife2->id]);

    $this->actingAs($user);

    Livewire::test('⚡member-manager', ['treeId' => $tree->id])
        ->dispatch('create-member', targetId: $father->id, relType: 'child_of')
        ->set('first_name', 'Anak Istri 2')
        ->set('other_parent_id', $wife2->id)
        ->call('save');

    $child = Member::where('first_name', 'Anak Istri 2')->first();
    expect($child)->not->toBeNull()
        ->and($child->father_id)->toBe($father->id)
        ->and($child->mother_id)->toBe($wife2->id);
});
