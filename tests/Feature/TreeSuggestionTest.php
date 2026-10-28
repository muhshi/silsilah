<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\TreeSuggestion;
use App\Models\User;
use App\Services\TreeSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('public visitor can submit a new child suggestion without login', function () {
    $tree = FamilyTree::factory()->create(['is_public' => true, 'slug' => 'bani-salim']);
    $parent = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Fauzi',
        'gender' => 'male',
    ]);

    $component = Livewire::test('tree-contribute-modal', ['treeId' => $tree->id])
        ->set('targetMemberId', $parent->id)
        ->set('suggestionType', 'add_child')
        ->set('firstName', 'Ahmad')
        ->set('lastName', 'Fauzi')
        ->set('gender', 'male')
        ->set('birthDate', '2024-05-10')
        ->set('isLiving', true)
        ->set('contributorName', 'Budi Santoso (Sepupu)')
        ->set('contributorContact', '08123456789')
        ->set('notes', 'Lahir bulan lalu di Demak')
        ->call('submitSuggestion');

    $component->assertHasNoErrors();

    $this->assertDatabaseHas('tree_suggestions', [
        'family_tree_id' => $tree->id,
        'target_member_id' => $parent->id,
        'type' => 'add_child',
        'status' => 'pending',
        'contributor_name' => 'Budi Santoso (Sepupu)',
        'contributor_contact' => '08123456789',
    ]);

    $suggestion = TreeSuggestion::first();
    expect($suggestion->data)->toBeArray()
        ->and($suggestion->data['first_name'])->toBe('Ahmad')
        ->and($suggestion->data['last_name'])->toBe('Fauzi')
        ->and($suggestion->data['gender'])->toBe('male')
        ->and($suggestion->data['birth_date'])->toBe('2024-05-10');
});

test('public visitor can submit an add spouse suggestion', function () {
    $tree = FamilyTree::factory()->create(['is_public' => true]);
    $husband = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ahmad',
        'gender' => 'male',
    ]);

    $component = Livewire::test('tree-contribute-modal', ['treeId' => $tree->id])
        ->set('targetMemberId', $husband->id)
        ->set('suggestionType', 'add_spouse')
        ->set('firstName', 'Siti')
        ->set('lastName', 'Aminah')
        ->set('gender', 'female')
        ->set('contributorName', 'Fatimah')
        ->set('contributorContact', '08987654321')
        ->call('submitSuggestion');

    $component->assertHasNoErrors();

    $this->assertDatabaseHas('tree_suggestions', [
        'family_tree_id' => $tree->id,
        'target_member_id' => $husband->id,
        'type' => 'add_spouse',
        'status' => 'pending',
        'contributor_name' => 'Fatimah',
    ]);
});

test('public visitor can submit edit member correction suggestion', function () {
    $tree = FamilyTree::factory()->create(['is_public' => true]);
    $member = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Mahmud',
        'gender' => 'male',
        'birth_date' => '1980-01-01',
    ]);

    $component = Livewire::test('tree-contribute-modal', ['treeId' => $tree->id])
        ->set('targetMemberId', $member->id)
        ->set('suggestionType', 'edit_member')
        ->set('firstName', 'Mahmudi')
        ->set('lastName', 'Alatas')
        ->set('gender', 'male')
        ->set('birthDate', '1982-03-15')
        ->set('contributorName', 'Hasan')
        ->call('submitSuggestion');

    $component->assertHasNoErrors();

    $this->assertDatabaseHas('tree_suggestions', [
        'family_tree_id' => $tree->id,
        'target_member_id' => $member->id,
        'type' => 'edit_member',
        'status' => 'pending',
        'contributor_name' => 'Hasan',
    ]);
});

test('suggestion submission validates required fields', function () {
    $tree = FamilyTree::factory()->create(['is_public' => true]);

    Livewire::test('tree-contribute-modal', ['treeId' => $tree->id])
        ->set('targetMemberId', null)
        ->set('firstName', '')
        ->set('contributorName', '')
        ->call('submitSuggestion')
        ->assertHasErrors(['targetMemberId', 'firstName', 'contributorName']);
});

test('tree owner can approve add_child suggestion and child is created with parents linked', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $father = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Fauzi',
        'gender' => 'male',
    ]);

    $mother = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Nurul',
        'gender' => 'female',
    ]);

    Marriage::create([
        'husband_id' => $father->id,
        'wife_id' => $mother->id,
        'is_current' => true,
    ]);

    $suggestion = TreeSuggestion::create([
        'family_tree_id' => $tree->id,
        'target_member_id' => $father->id,
        'type' => 'add_child',
        'data' => [
            'first_name' => 'Ahmad',
            'last_name' => 'Fauzi',
            'gender' => 'male',
            'birth_date' => '2024-01-01',
            'is_living' => true,
        ],
        'contributor_name' => 'Budi',
        'status' => 'pending',
    ]);

    $service = app(TreeSuggestionService::class);
    $newMember = $service->approve($suggestion, $owner);

    expect($suggestion->fresh()->status)->toBe('approved')
        ->and($suggestion->fresh()->reviewed_by)->toBe($owner->id)
        ->and($suggestion->fresh()->reviewed_at)->not->toBeNull()
        ->and($newMember->first_name)->toBe('Ahmad')
        ->and($newMember->father_id)->toBe($father->id)
        ->and($newMember->mother_id)->toBe($mother->id);

    $this->assertDatabaseHas('members', [
        'family_tree_id' => $tree->id,
        'first_name' => 'Ahmad',
        'father_id' => $father->id,
        'mother_id' => $mother->id,
    ]);
});

test('tree owner can approve add_spouse suggestion and marriage is recorded', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $husband = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Hamzah',
        'gender' => 'male',
    ]);

    $suggestion = TreeSuggestion::create([
        'family_tree_id' => $tree->id,
        'target_member_id' => $husband->id,
        'type' => 'add_spouse',
        'data' => [
            'first_name' => 'Halimah',
            'gender' => 'female',
            'is_living' => true,
        ],
        'contributor_name' => 'Zaid',
        'status' => 'pending',
    ]);

    $service = app(TreeSuggestionService::class);
    $spouse = $service->approve($suggestion, $owner);

    expect($suggestion->fresh()->status)->toBe('approved')
        ->and($spouse->first_name)->toBe('Halimah')
        ->and($spouse->gender)->toBe('female');

    $this->assertDatabaseHas('marriages', [
        'husband_id' => $husband->id,
        'wife_id' => $spouse->id,
    ]);
});

test('tree owner can approve edit_member suggestion and member details are updated', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $member = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Salahuddin',
        'birth_date' => '1990-01-01',
    ]);

    $suggestion = TreeSuggestion::create([
        'family_tree_id' => $tree->id,
        'target_member_id' => $member->id,
        'type' => 'edit_member',
        'data' => [
            'first_name' => 'Shalahuddin Al-Ayyubi',
            'birth_date' => '1990-05-20',
        ],
        'contributor_name' => 'Kerabat',
        'status' => 'pending',
    ]);

    $service = app(TreeSuggestionService::class);
    $service->approve($suggestion, $owner);

    expect($suggestion->fresh()->status)->toBe('approved')
        ->and($member->fresh()->first_name)->toBe('Shalahuddin Al-Ayyubi')
        ->and($member->fresh()->birth_date->format('Y-m-d'))->toBe('1990-05-20');
});

test('tree owner can reject suggestion without database changes', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $member = Member::factory()->create(['family_tree_id' => $tree->id, 'first_name' => 'Ali']);

    $suggestion = TreeSuggestion::create([
        'family_tree_id' => $tree->id,
        'target_member_id' => $member->id,
        'type' => 'add_child',
        'data' => ['first_name' => 'Palsu', 'gender' => 'male'],
        'contributor_name' => 'Spammer',
        'status' => 'pending',
    ]);

    $service = app(TreeSuggestionService::class);
    $service->reject($suggestion, $owner);

    expect($suggestion->fresh()->status)->toBe('rejected')
        ->and($suggestion->fresh()->reviewed_by)->toBe($owner->id)
        ->and($suggestion->fresh()->reviewed_at)->not->toBeNull();

    $this->assertDatabaseMissing('members', [
        'first_name' => 'Palsu',
    ]);
});

test('tree owner can approve and reject suggestions via tree-settings component', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $member = Member::factory()->create(['family_tree_id' => $tree->id, 'first_name' => 'Salim', 'gender' => 'male']);

    $sug1 = TreeSuggestion::create([
        'family_tree_id' => $tree->id,
        'target_member_id' => $member->id,
        'type' => 'add_child',
        'data' => ['first_name' => 'Anak Salim', 'gender' => 'male'],
        'contributor_name' => 'Kerabat A',
        'status' => 'pending',
    ]);

    $sug2 = TreeSuggestion::create([
        'family_tree_id' => $tree->id,
        'target_member_id' => $member->id,
        'type' => 'add_spouse',
        'data' => ['first_name' => 'Istri Salim', 'gender' => 'female'],
        'contributor_name' => 'Kerabat B',
        'status' => 'pending',
    ]);

    $this->actingAs($owner);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('activeTab', 'suggestions')
        ->assertSee('Anak Salim')
        ->assertSee('Istri Salim')
        ->call('approveSuggestion', $sug1->id)
        ->call('rejectSuggestion', $sug2->id);

    expect($sug1->fresh()->status)->toBe('approved')
        ->and($sug2->fresh()->status)->toBe('rejected');

    $this->assertDatabaseHas('members', [
        'family_tree_id' => $tree->id,
        'first_name' => 'Anak Salim',
        'father_id' => $member->id,
    ]);
});

test('non-owner is unauthorized to approve or reject suggestions in tree-settings', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $member = Member::factory()->create(['family_tree_id' => $tree->id, 'first_name' => 'Salim', 'gender' => 'male']);

    $suggestion = TreeSuggestion::create([
        'family_tree_id' => $tree->id,
        'target_member_id' => $member->id,
        'type' => 'add_child',
        'data' => ['first_name' => 'Anak Baru', 'gender' => 'male'],
        'contributor_name' => 'Kerabat',
        'status' => 'pending',
    ]);

    $this->actingAs($stranger);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->call('approveSuggestion', $suggestion->id)
        ->assertForbidden();

    expect($suggestion->fresh()->status)->toBe('pending');
});

test('public tree views include contribute data menu item and modal component', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Bani Salim',
        'slug' => 'bani-salim',
        'is_public' => true,
    ]);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Salim',
    ]);

    $response = $this->get(route('tree.public', $tree->slug));
    $response->assertOk();
    $response->assertSee('Lengkapi / Usulkan Data');
});
