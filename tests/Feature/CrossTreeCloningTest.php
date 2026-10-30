<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

test('user can search members from other owned trees and see contextual nasab details', function () {
    $user = User::factory()->create();
    $treeA = FamilyTree::factory()->create(['name' => 'Bani Kakek Ayah']);
    $treeB = FamilyTree::factory()->create(['name' => 'Bani Kakek Ibu']);
    $treeA->users()->attach($user->id, ['role' => 'owner']);
    $treeB->users()->attach($user->id, ['role' => 'owner']);

    $father = Member::factory()->create([
        'family_tree_id' => $treeA->id,
        'first_name' => 'Mansur',
        'last_name' => 'Al-Habsyi',
        'gender' => 'male',
    ]);

    $ahmad = Member::factory()->create([
        'family_tree_id' => $treeA->id,
        'first_name' => 'Ahmad',
        'last_name' => 'Muhibbin',
        'gender' => 'male',
        'father_id' => $father->id,
        'birth_place' => 'Demak',
        'birth_date' => '1990-05-12',
    ]);

    $this->actingAs($user);

    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->set('searchSourceQuery', 'Ahmad')
        ->assertSee('Ahmad Muhibbin')
        ->assertSee('Bani Kakek Ayah')
        ->assertSee('Mansur')
        ->call('selectSourceMember', $ahmad->id)
        ->assertSet('first_name', 'Ahmad')
        ->assertSet('last_name', 'Muhibbin')
        ->assertSet('gender', 'male')
        ->assertSet('birth_place', 'Demak')
        ->assertSet('birth_date', '1990-05-12')
        ->assertSet('selectedSourceMemberId', $ahmad->id);
});

test('user can clone member along with spouse and children into another tree in one action', function () {
    $user = User::factory()->create();
    $treeA = FamilyTree::factory()->create(['name' => 'Bani Kakek Ayah']);
    $treeB = FamilyTree::factory()->create(['name' => 'Bani Kakek Ibu']);
    $treeA->users()->attach($user->id, ['role' => 'owner']);
    $treeB->users()->attach($user->id, ['role' => 'owner']);

    $husband = Member::factory()->create([
        'family_tree_id' => $treeA->id,
        'first_name' => 'Saiful',
        'last_name' => 'Anwar',
        'gender' => 'male',
        'birth_place' => 'Demak',
        'birth_date' => '1990-01-01',
    ]);

    $wife = Member::factory()->create([
        'family_tree_id' => $treeA->id,
        'first_name' => 'Siti',
        'last_name' => 'Aminah',
        'gender' => 'female',
    ]);

    Marriage::create([
        'husband_id' => $husband->id,
        'wife_id' => $wife->id,
        'is_current' => true,
    ]);

    $child = Member::factory()->create([
        'family_tree_id' => $treeA->id,
        'first_name' => 'Doni',
        'last_name' => 'Anwar',
        'gender' => 'male',
        'father_id' => $husband->id,
        'mother_id' => $wife->id,
    ]);

    $this->actingAs($user);

    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('createMember')
        ->call('selectSourceMember', $husband->id)
        ->set('includeSpouses', true)
        ->set('includeChildren', true)
        ->call('save');

    // Assert Tree B now has all 3 members
    expect(Member::where('family_tree_id', $treeB->id)->count())->toBe(3);

    $clonedHusband = Member::where('family_tree_id', $treeB->id)->where('first_name', 'Saiful')->first();
    $clonedWife = Member::where('family_tree_id', $treeB->id)->where('first_name', 'Siti')->first();
    $clonedChild = Member::where('family_tree_id', $treeB->id)->where('first_name', 'Doni')->first();

    expect($clonedHusband)->not->toBeNull();
    expect($clonedHusband->source_member_id)->toBe($husband->id);
    expect($clonedHusband->source_tree_id)->toBe($treeA->id);

    expect($clonedWife)->not->toBeNull();
    expect($clonedWife->source_member_id)->toBe($wife->id);

    expect($clonedChild)->not->toBeNull();
    expect($clonedChild->father_id)->toBe($clonedHusband->id);
    expect($clonedChild->mother_id)->toBe($clonedWife->id);

    // Assert marriage relationship created in Tree B
    $marriage = Marriage::where('husband_id', $clonedHusband->id)->where('wife_id', $clonedWife->id)->first();
    expect($marriage)->not->toBeNull();
});

test('user can retroactively link existing member to another tree and sync data', function () {
    $user = User::factory()->create();
    $treeA = FamilyTree::factory()->create(['name' => 'Bani Kakek Ayah']);
    $treeB = FamilyTree::factory()->create(['name' => 'Bani Kakek Ibu']);
    $treeA->users()->attach($user->id, ['role' => 'owner']);
    $treeB->users()->attach($user->id, ['role' => 'owner']);

    $source = Member::factory()->create([
        'family_tree_id' => $treeA->id,
        'first_name' => 'Saiful',
        'last_name' => 'Anwar',
        'gender' => 'male',
        'profession' => 'Software Engineer',
        'birth_place' => 'Demak',
    ]);

    // Member already created earlier in Tree B with empty/partial info
    $existing = Member::factory()->create([
        'family_tree_id' => $treeB->id,
        'first_name' => 'Saiful',
        'last_name' => '',
        'gender' => 'male',
        'profession' => null,
    ]);

    $this->actingAs($user);

    // Link existing to source
    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('editMember', $existing->id)
        ->call('linkExistingMemberToSource', $source->id);

    $existing->refresh();
    expect($existing->source_member_id)->toBe($source->id);
    expect($existing->source_tree_id)->toBe($treeA->id);

    // Now update source in Tree A and trigger sync in Tree B
    $source->update([
        'profession' => 'Senior Lead Architect',
        'birth_place' => 'Semarang',
    ]);

    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('editMember', $existing->id)
        ->call('syncFromSource');

    $existing->refresh();
    expect($existing->profession)->toBe('Senior Lead Architect');
    expect($existing->birth_place)->toBe('Semarang');

    // Now test unlinking
    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('editMember', $existing->id)
        ->call('unlinkSource');

    $existing->refresh();
    expect($existing->source_member_id)->toBeNull();
    expect($existing->source_tree_id)->toBeNull();
});

test('detail modal displays source tree badge when member is linked', function () {
    $user = User::factory()->create();
    $treeA = FamilyTree::factory()->create(['name' => 'Bani Kakek Ayah']);
    $treeB = FamilyTree::factory()->create(['name' => 'Bani Kakek Ibu']);
    $treeA->users()->attach($user->id, ['role' => 'owner']);
    $treeB->users()->attach($user->id, ['role' => 'owner']);

    $source = Member::factory()->create([
        'family_tree_id' => $treeA->id,
        'first_name' => 'Umar',
        'last_name' => 'Farouq',
    ]);

    $linkedMember = Member::factory()->create([
        'family_tree_id' => $treeB->id,
        'first_name' => 'Umar',
        'last_name' => 'Farouq',
        'source_tree_id' => $treeA->id,
        'source_member_id' => $source->id,
    ]);

    $this->actingAs($user);

    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('showMember', $linkedMember->id)
        ->assertSee('Tercatat juga di Silsilah:')
        ->assertSee('Bani Kakek Ayah');
});

test('bidirectional linking allows member in either tree to see link and sync data', function () {
    $user = User::factory()->create();
    $treeA = FamilyTree::factory()->create(['name' => 'Bani Mochtar']);
    $treeB = FamilyTree::factory()->create(['name' => 'Bani Syukri']);
    $treeA->users()->attach($user->id, ['role' => 'owner']);
    $treeB->users()->attach($user->id, ['role' => 'owner']);

    $memberA = Member::factory()->create([
        'family_tree_id' => $treeA->id,
        'first_name' => 'M Abdul',
        'last_name' => 'Muhshi',
        'profession' => 'Engineer',
    ]);

    $memberB = Member::factory()->create([
        'family_tree_id' => $treeB->id,
        'first_name' => 'M Abdul',
        'last_name' => 'Muhshi',
        'profession' => null,
    ]);

    $this->actingAs($user);

    // Link from Tree A to Tree B
    Livewire::test('member-manager', ['treeId' => $treeA->id])
        ->call('editMember', $memberA->id)
        ->call('linkExistingMemberToSource', $memberB->id);

    // Now test opening memberB in Tree B! It must also see the link to Tree A
    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('editMember', $memberB->id)
        ->assertSee('Bani Mochtar')
        ->assertSee('Tarik Data');

    // Update profession in Member A and sync from Tree B
    $memberA->update(['profession' => 'Senior Developer']);

    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('editMember', $memberB->id)
        ->call('syncFromLinked');

    $memberB->refresh();
    expect($memberB->profession)->toBe('Senior Developer');

    // Also test push to linked from Tree B
    $memberB->update(['bio' => 'Family Genealogist']);

    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('editMember', $memberB->id)
        ->call('pushToLinked');

    $memberA->refresh();
    expect($memberA->bio)->toBe('Family Genealogist');
});

test('marriage date synchronizes across linked trees and reflects on both spouses', function () {
    $user = User::factory()->create();
    $treeA = FamilyTree::factory()->create(['name' => 'Bani Mochtar']);
    $treeB = FamilyTree::factory()->create(['name' => 'Bani Syukri']);
    $treeA->users()->attach($user->id, ['role' => 'owner']);
    $treeB->users()->attach($user->id, ['role' => 'owner']);

    // Husband & Wife in Tree A with marriage_date
    $husbandA = Member::factory()->create(['family_tree_id' => $treeA->id, 'first_name' => 'Abdul', 'gender' => 'male']);
    $wifeA = Member::factory()->create(['family_tree_id' => $treeA->id, 'first_name' => 'Ainur', 'gender' => 'female']);
    $marriageA = Marriage::create([
        'husband_id' => $husbandA->id,
        'wife_id' => $wifeA->id,
        'marriage_date' => '2021-01-03',
        'is_current' => true,
    ]);

    // Husband & Wife in Tree B without marriage_date
    $husbandB = Member::factory()->create([
        'family_tree_id' => $treeB->id,
        'first_name' => 'Abdul',
        'gender' => 'male',
        'source_tree_id' => $treeA->id,
        'source_member_id' => $husbandA->id,
    ]);
    $wifeB = Member::factory()->create([
        'family_tree_id' => $treeB->id,
        'first_name' => 'Ainur',
        'gender' => 'female',
        'source_tree_id' => $treeA->id,
        'source_member_id' => $wifeA->id,
    ]);
    $marriageB = Marriage::create([
        'husband_id' => $husbandB->id,
        'wife_id' => $wifeB->id,
        'marriage_date' => null,
        'is_current' => true,
    ]);

    $this->actingAs($user);

    // Sync from husbandB in Tree B
    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('editMember', $husbandB->id)
        ->call('syncFromLinked');

    $marriageB->refresh();
    expect($marriageB->marriage_date->format('Y-m-d'))->toBe('2021-01-03');

    // Both husband and wife share this exact marriage_date in form
    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('editMember', $wifeB->id)
        ->assertSet('marriage_date', '2021-01-03');

    // Update marriage date directly via updateMarriageDate
    Livewire::test('member-manager', ['treeId' => $treeB->id])
        ->call('updateMarriageDate', $marriageB->id, '2022-05-20');

    $marriageB->refresh();
    expect($marriageB->marriage_date->format('Y-m-d'))->toBe('2022-05-20');
});
