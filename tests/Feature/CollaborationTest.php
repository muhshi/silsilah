<?php

use App\Mail\TreeInvitationMail;
use App\Models\ActivityLog;
use App\Models\FamilyTree;
use App\Models\TreeInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('allows owner to send collaboration invitation', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->set('inviteEmail', 'kolaborator@example.com')
        ->call('sendInvite')
        ->assertHasNoErrors();

    $invitation = TreeInvitation::where('family_tree_id', $tree->id)
        ->where('email', 'kolaborator@example.com')
        ->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation->status)->toBe('pending')
        ->and($invitation->role)->toBe('editor');

    Mail::assertSent(TreeInvitationMail::class, function ($mail) {
        return $mail->hasTo('kolaborator@example.com');
    });
});

it('allows user to accept invitation and become editor', function () {
    $owner = User::factory()->create();
    $editorUser = User::factory()->create(['email' => 'editor@example.com']);
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $invitation = TreeInvitation::create([
        'family_tree_id' => $tree->id,
        'email' => 'editor@example.com',
        'role' => 'editor',
        'token' => 'test-token-123',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($editorUser)
        ->get(route('invitation.accept.process', 'test-token-123'));

    $response->assertRedirect(route('tree.show', $tree));

    $invitation->refresh();
    expect($invitation->status)->toBe('accepted');

    $isEditor = $tree->users()->where('user_id', $editorUser->id)->wherePivot('role', 'editor')->exists();
    expect($isEditor)->toBeTrue();
});

it('allows owner to remove editor', function () {
    $owner = User::factory()->create();
    $editorUser = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);
    $tree->users()->attach($editorUser->id, ['role' => 'editor']);

    $this->actingAs($owner);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->call('removeEditor', $editorUser->id)
        ->assertHasNoErrors();

    $isAttached = $tree->users()->where('user_id', $editorUser->id)->exists();
    expect($isAttached)->toBeFalse();
});

it('allows owner to cancel pending invitation', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $invitation = TreeInvitation::create([
        'family_tree_id' => $tree->id,
        'email' => 'batal@example.com',
        'role' => 'editor',
        'token' => 'token-batal-456',
        'status' => 'pending',
    ]);

    $this->actingAs($owner);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->call('cancelInvite', $invitation->id)
        ->assertHasNoErrors();

    expect(TreeInvitation::find($invitation->id))->toBeNull();
});

it('records activity log when members are added and edited', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    Livewire::test('⚡member-manager', ['treeId' => $tree->id])
        ->set('first_name', 'Budi')
        ->set('last_name', 'Santoso')
        ->set('gender', 'male')
        ->call('save');

    $log = ActivityLog::where('family_tree_id', $tree->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->action)->toBe('member_created')
        ->and($log->user_id)->toBe($owner->id)
        ->and($log->description)->toContain('Budi Santoso');
});

it('allows creating direct link invitation without email', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->call('createLinkInvite')
        ->assertHasNoErrors();

    $invitation = TreeInvitation::where('family_tree_id', $tree->id)->whereNull('email')->first();
    expect($invitation)->not->toBeNull()
        ->and($invitation->role)->toBe('editor');
});

it('shows invitation preview page with open graph metadata', function () {
    $tree = FamilyTree::factory()->create(['name' => 'Trah Mangun']);
    $invitation = TreeInvitation::create([
        'family_tree_id' => $tree->id,
        'email' => null,
        'role' => 'editor',
        'token' => 'og-token-789',
        'status' => 'pending',
    ]);

    $this->get(route('invitation.accept', 'og-token-789'))
        ->assertSuccessful()
        ->assertSee('Undangan Kolaborasi Silsilah Trah Mangun')
        ->assertSee('og:title');
});
