<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

test('dashboard renders responsive metrics, action buttons, and tree cards properly', function () {
    $user = User::factory()->create(['tree_quota' => 5]);
    $tree1 = FamilyTree::factory()->create(['name' => 'Keluarga Ahmad', 'is_public' => true, 'is_premium' => true]);
    $tree2 = FamilyTree::factory()->create(['name' => 'Keluarga Budi', 'is_public' => false, 'is_premium' => false]);
    $tree1->users()->attach($user->id, ['role' => 'owner']);
    $tree2->users()->attach($user->id, ['role' => 'owner']);

    Member::factory()->count(10)->create(['family_tree_id' => $tree1->id]);
    Member::factory()->count(8)->create(['family_tree_id' => $tree2->id]);

    $this->actingAs($user);

    $component = Livewire::test('dashboard-trees')
        ->assertSee('Silsilah Keluarga')
        ->assertSee('Buat Silsilah')
        ->assertSee('Beli Kuota')
        ->assertSee('Total Silsilah')
        ->assertSee('Total Anggota')
        ->assertSee('Pohon Premium')
        ->assertSee('18')
        ->assertSee('Keluarga Ahmad')
        ->assertSee('Keluarga Budi');

    $html = $component->html();

    // Assert 3-column metric grid exists on mobile
    expect($html)->toContain('grid grid-cols-3 gap-2');
    // Assert action buttons have 2-column mobile layout
    expect($html)->toContain('grid grid-cols-2 sm:flex');
    // Assert focus mode title updated
    expect($html)->toContain('Mode Fokus Eksplorasi');
});
