<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

it('renders relationship calculator modal and calculates kinship between two members', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $grandpa = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mochtar']);
    $son1 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mahmudi', 'father_id' => $grandpa->id]);
    $son2 = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Husain', 'father_id' => $grandpa->id]);

    $this->actingAs($owner);

    Livewire::test('⚡relationship-calculator', ['treeId' => $tree->id])
        ->assertSee('Cek Hubungan Kekerabatan')
        ->assertSee('Mochtar')
        ->set('personAId', $son1->id)
        ->set('personBId', $son2->id)
        ->call('calculate')
        ->assertSee('Saudara Kandung')
        ->assertSee('Bagikan ke WhatsApp');
});

it('calculates self relationship when same member is selected for both persons', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $person = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Budi']);

    $this->actingAs($owner);

    Livewire::test('⚡relationship-calculator', ['treeId' => $tree->id])
        ->set('personAId', $person->id)
        ->set('personBId', $person->id)
        ->call('calculate')
        ->assertSee('Diri Sendiri')
        ->assertSee('Ini adalah profil Anda sendiri.');
});

it('allows swapping persons and recalculating', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $father = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mahmudi']);
    $son = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Fauzi', 'father_id' => $father->id]);

    $this->actingAs($owner);

    Livewire::test('⚡relationship-calculator', ['treeId' => $tree->id])
        ->set('personAId', $son->id)
        ->set('personBId', $father->id)
        ->call('calculate')
        ->assertSee('Ayah Kandung')
        ->call('swapPersons')
        ->assertSee('Anak Laki-laki');
});

it('opens modal and auto-calculates when members are passed via event', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $father = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mahmudi']);
    $son = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Fauzi', 'father_id' => $father->id]);

    $this->actingAs($owner);

    Livewire::test('⚡relationship-calculator', ['treeId' => $tree->id])
        ->assertSet('showModal', false)
        ->dispatch('open-relationship-calculator', memberA: $son->id, memberB: $father->id)
        ->assertSet('showModal', true)
        ->assertSet('personAId', $son->id)
        ->assertSet('personBId', $father->id)
        ->assertSee('Ayah Kandung');
});

it('validates that both members must be selected before calculation', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    Livewire::test('⚡relationship-calculator', ['treeId' => $tree->id])
        ->call('calculate')
        ->assertHasErrors(['personAId', 'personBId']);
});

it('generates a valid WhatsApp share URL with relationship details', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Keluarga Besar']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $father = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Mahmudi']);
    $son = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male', 'first_name' => 'Fauzi', 'father_id' => $father->id]);

    $this->actingAs($owner);

    $component = Livewire::test('⚡relationship-calculator', ['treeId' => $tree->id])
        ->set('personAId', $son->id)
        ->set('personBId', $father->id)
        ->call('calculate');

    $url = $component->instance()->getWhatsAppShareUrl();
    expect($url)
        ->toContain('https://wa.me/?text=')
        ->toContain(rawurlencode('Fauzi'))
        ->toContain(rawurlencode('Mahmudi'))
        ->toContain(rawurlencode('Ayah Kandung'));
});
