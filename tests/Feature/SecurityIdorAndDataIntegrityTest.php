<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('user cannot update a member belonging to another tree via member manager (IDOR prevention)', function () {
    $userA = User::factory()->create();
    $treeA = FamilyTree::factory()->create();
    $treeA->users()->attach($userA->id, ['role' => 'owner']);

    $userB = User::factory()->create();
    $treeB = FamilyTree::factory()->create();
    $treeB->users()->attach($userB->id, ['role' => 'owner']);

    $victimMember = Member::factory()->create([
        'family_tree_id' => $treeB->id,
        'first_name' => 'Victim',
        'last_name' => 'Original',
        'gender' => 'male',
    ]);

    expect(function () use ($userA, $treeA, $victimMember) {
        Livewire::actingAs($userA)
            ->test('⚡member-manager', ['treeId' => $treeA->id])
            ->set('memberId', $victimMember->id)
            ->set('first_name', 'Hacked Name')
            ->set('last_name', 'Hacked')
            ->set('gender', 'male')
            ->call('save');
    })->toThrow(ModelNotFoundException::class);

    expect($victimMember->fresh()->first_name)->toBe('Victim');
    expect($victimMember->fresh()->family_tree_id)->toBe($treeB->id);
});

test('user cannot delete a member belonging to another tree via member manager (IDOR prevention)', function () {
    $userA = User::factory()->create();
    $treeA = FamilyTree::factory()->create();
    $treeA->users()->attach($userA->id, ['role' => 'owner']);

    $userB = User::factory()->create();
    $treeB = FamilyTree::factory()->create();
    $treeB->users()->attach($userB->id, ['role' => 'owner']);

    $victimMember = Member::factory()->create([
        'family_tree_id' => $treeB->id,
        'first_name' => 'Victim',
        'gender' => 'female',
    ]);

    expect(function () use ($userA, $treeA, $victimMember) {
        Livewire::actingAs($userA)
            ->test('⚡member-manager', ['treeId' => $treeA->id])
            ->set('memberId', $victimMember->id)
            ->call('deleteMember');
    })->toThrow(ModelNotFoundException::class);

    expect(Member::find($victimMember->id))->not->toBeNull();
});

test('user cannot delete or update a marriage belonging to another tree (IDOR prevention)', function () {
    $userA = User::factory()->create();
    $treeA = FamilyTree::factory()->create();
    $treeA->users()->attach($userA->id, ['role' => 'owner']);

    $userB = User::factory()->create();
    $treeB = FamilyTree::factory()->create();
    $treeB->users()->attach($userB->id, ['role' => 'owner']);

    $husband = Member::factory()->create(['family_tree_id' => $treeB->id, 'gender' => 'male']);
    $wife = Member::factory()->create(['family_tree_id' => $treeB->id, 'gender' => 'female']);
    $marriage = Marriage::create([
        'husband_id' => $husband->id,
        'wife_id' => $wife->id,
        'is_current' => true,
    ]);

    expect(function () use ($userA, $treeA, $marriage) {
        Livewire::actingAs($userA)
            ->test('⚡member-manager', ['treeId' => $treeA->id])
            ->call('deleteMarriage', $marriage->id);
    })->toThrow(ModelNotFoundException::class);

    expect(Marriage::find($marriage->id))->not->toBeNull();
});

test('saving member with death date earlier than birth date fails validation', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    Livewire::actingAs($user)
        ->test('⚡member-manager', ['treeId' => $tree->id])
        ->set('first_name', 'Invalid Chronology')
        ->set('gender', 'male')
        ->set('birth_date', '2000-01-01')
        ->set('death_date', '1990-01-01')
        ->set('is_living', false)
        ->call('save')
        ->assertHasErrors(['death_date']);
});

test('setting is_living true clears death date upon save', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $member = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Living Test',
        'gender' => 'male',
        'is_living' => false,
        'death_date' => '2020-01-01',
    ]);

    Livewire::actingAs($user)
        ->test('⚡member-manager', ['treeId' => $tree->id])
        ->set('memberId', $member->id)
        ->set('first_name', 'Living Test')
        ->set('gender', 'male')
        ->set('is_living', true)
        ->set('death_date', '2020-01-01')
        ->call('save')
        ->assertHasNoErrors();

    expect($member->fresh()->death_date)->toBeNull();
    expect($member->fresh()->is_living)->toBeTrue();
});

test('setting father_id same as mother_id fails validation', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $parent = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Single Parent',
        'gender' => 'male',
    ]);

    Livewire::actingAs($user)
        ->test('⚡member-manager', ['treeId' => $tree->id])
        ->set('first_name', 'Child')
        ->set('gender', 'female')
        ->set('father_id', $parent->id)
        ->set('mother_id', $parent->id)
        ->call('save')
        ->assertHasErrors(['father_id']);
});
