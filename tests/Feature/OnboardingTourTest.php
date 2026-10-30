<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;

test('dashboard renders onboarding tour component, create tree trigger, and manual guide button', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-tour="create-tree-btn"', false)
        ->assertSee("onboardingTour('dashboard'", false)
        ->assertSee('Panduan');
});

test('empty tree canvas shows empty state CTA with tour hook and settings trigger', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user)
        ->get(route('tree.show', $tree->id))
        ->assertOk()
        ->assertSee('data-tour="empty-tree-cta"', false)
        ->assertSee('Tambah Anggota Pertama')
        ->assertSee('data-tour="add-member-top"', false)
        ->assertSee("onboardingTour('canvas'", false)
        ->assertSee('Panduan Tutorial Silsilah');
});

test('tree canvas with members renders member cards and quick action tour hooks', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $root = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'last_name' => 'Leluhur',
        'gender' => 'male',
    ]);

    $this->actingAs($user)
        ->get(route('tree.show', $tree->id))
        ->assertOk()
        ->assertSee('data-tour="member-card"', false)
        ->assertSee('data-tour="spouse-action-btn"', false)
        ->assertSee('data-tour="child-action-btn"', false);
});
