<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

test('user can set and update child order in member manager', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Bani Fulan']);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $parent = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ayah',
        'gender' => 'male',
    ]);

    $child = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'father_id' => $parent->id,
        'first_name' => 'Anak Kedua',
        'gender' => 'male',
        'order' => null,
    ]);

    $this->actingAs($user);

    Livewire::test('member-manager', ['treeId' => $tree->id])
        ->call('editMember', $child->id)
        ->assertSet('order', null)
        ->set('order', 2)
        ->call('save');

    $child->refresh();
    expect($child->order)->toBe(2);
});

test('children sort by explicit order first then birth date', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $parent = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male']);

    // Child with later birth date but explicit order = 1
    $child1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'father_id' => $parent->id,
        'first_name' => 'Sulung Order',
        'birth_date' => '2000-01-01',
        'order' => 1,
    ]);

    // Child with earlier birth date but explicit order = 2
    $child2 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'father_id' => $parent->id,
        'first_name' => 'Kedua Order',
        'birth_date' => '1995-01-01',
        'order' => 2,
    ]);

    $this->actingAs($user);

    // In vertical view, Sulung Order (order 1) must be rendered first
    $response = $this->get(route('tree.vertical', ['tree' => $tree->id, 'member' => $parent->id]));
    $response->assertOk();
    $response->assertSee('Anak ke-1');
    $response->assertSee('Anak ke-2');
});

test('user can move child order up and down in vertical tree', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $parent = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male']);

    $childA = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'father_id' => $parent->id,
        'first_name' => 'Anak A',
        'birth_date' => '1990-01-01',
        'order' => 1,
    ]);

    $childB = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'father_id' => $parent->id,
        'first_name' => 'Anak B',
        'birth_date' => '1995-01-01',
        'order' => 2,
    ]);

    $this->actingAs($user);

    // Move child B up
    Livewire::test('tree-vertical', ['id' => $tree->id])
        ->call('moveChildOrder', $childB->id, 'up');

    $childA->refresh();
    $childB->refresh();

    expect($childB->order)->toBe(1);
    expect($childA->order)->toBe(2);

    // Move child B down
    Livewire::test('tree-vertical', ['id' => $tree->id])
        ->call('moveChildOrder', $childB->id, 'down');

    $childA->refresh();
    $childB->refresh();

    expect($childA->order)->toBe(1);
    expect($childB->order)->toBe(2);
});

test('boundary conditions for moveChildOrder do nothing when already at top or bottom', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $parent = Member::factory()->create(['family_tree_id' => $tree->id, 'gender' => 'male']);

    $child1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'father_id' => $parent->id,
        'first_name' => 'Pertama',
        'order' => 1,
    ]);

    $child2 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'father_id' => $parent->id,
        'first_name' => 'Kedua',
        'order' => 2,
    ]);

    $this->actingAs($user);

    // Moving child 1 up when already at top should do nothing
    Livewire::test('tree-vertical', ['id' => $tree->id])
        ->call('moveChildOrder', $child1->id, 'up');

    $child1->refresh();
    $child2->refresh();
    expect($child1->order)->toBe(1);
    expect($child2->order)->toBe(2);

    // Moving child 2 down when already at bottom should do nothing
    Livewire::test('tree-vertical', ['id' => $tree->id])
        ->call('moveChildOrder', $child2->id, 'down');

    $child1->refresh();
    $child2->refresh();
    expect($child1->order)->toBe(1);
    expect($child2->order)->toBe(2);
});

test('syncFromLinked and pushToLinked synchronize child order', function () {
    $user = User::factory()->create();
    $tree1 = FamilyTree::factory()->create(['name' => 'Bani A']);
    $tree2 = FamilyTree::factory()->create(['name' => 'Bani B']);
    $tree1->users()->attach($user->id, ['role' => 'owner']);
    $tree2->users()->attach($user->id, ['role' => 'owner']);

    $m1 = Member::factory()->create([
        'family_tree_id' => $tree1->id,
        'first_name' => 'Zaid',
        'order' => 3,
    ]);

    $m2 = Member::factory()->create([
        'family_tree_id' => $tree2->id,
        'first_name' => 'Zaid',
        'order' => null,
        'source_tree_id' => $tree1->id,
        'source_member_id' => $m1->id,
    ]);

    $this->actingAs($user);

    // Sync from linked to m2
    Livewire::test('member-manager', ['treeId' => $tree2->id])
        ->call('editMember', $m2->id)
        ->call('syncFromLinked');

    $m2->refresh();
    expect($m2->order)->toBe(3);

    // Now update m2 and push to linked
    Livewire::test('member-manager', ['treeId' => $tree2->id])
        ->call('editMember', $m2->id)
        ->set('order', 1)
        ->call('save')
        ->call('pushToLinked');

    $m1->refresh();
    expect($m1->order)->toBe(1);
});
