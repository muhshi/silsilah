<?php

use App\Models\FamilyTree;
use App\Models\User;

test('dashboard view renders onboarding tour with create tree target', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee('x-data="onboardingTour', false);
    $response->assertSee('data-tour="create-tree-btn"', false);
    $response->assertSee('Lewati Tutorial');
});

test('empty tree canvas renders onboarding tour with first member, spouse, and child guidance', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Keluarga Baru']);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    $response = $this->get(route('tree.show', $tree->id));
    $response->assertOk();
    $response->assertSee('x-data="onboardingTour', false);
    $response->assertSee('Misi 1: Anggota Pertama');
    $response->assertSee('Misi 2: Tambah Pasangan');
    $response->assertSee('Misi 3: Tambahkan Anak');
    $response->assertSee('Lewati Tutorial');
});

test('vertical tree canvas also includes onboarding tour', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Keluarga Vertikal']);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user);

    $response = $this->get(route('tree.vertical', $tree->id));
    $response->assertOk();
    $response->assertSee('x-data="onboardingTour', false);
    $response->assertSee('Lewati Tutorial');
});
