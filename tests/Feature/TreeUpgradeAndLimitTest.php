<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\Order;
use App\Models\User;
use App\Services\DuitkuService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('allows non-premium free tree to add members up to 100 members', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // Create 99 members
    Member::factory()->count(99)->create([
        'family_tree_id' => $tree->id,
    ]);

    expect($tree->members()->count())->toBe(99)
        ->and($tree->hasReachedMemberLimit())->toBeFalse()
        ->and($tree->canAddMembers(1))->toBeTrue();

    $this->actingAs($owner);

    // Add 100th member
    Livewire::test('⚡member-manager', ['treeId' => $tree->id])
        ->set('first_name', 'Anggota')
        ->set('last_name', 'Ke-100')
        ->set('gender', 'male')
        ->call('save')
        ->assertHasNoErrors();

    expect($tree->refresh()->members()->count())->toBe(100)
        ->and($tree->hasReachedMemberLimit())->toBeTrue()
        ->and($tree->canAddMembers(1))->toBeFalse();
});

it('blocks non-premium free tree from adding the 101st member', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // Create 100 members
    Member::factory()->count(100)->create([
        'family_tree_id' => $tree->id,
    ]);

    expect($tree->hasReachedMemberLimit())->toBeTrue();

    $this->actingAs($owner);

    // Attempt to add 101st member
    Livewire::test('⚡member-manager', ['treeId' => $tree->id])
        ->set('first_name', 'Anggota')
        ->set('last_name', 'Ke-101')
        ->set('gender', 'female')
        ->call('save')
        ->assertHasErrors(['first_name']);

    expect($tree->refresh()->members()->count())->toBe(100);
});

it('allows premium tree to add more than 100 members without any limit', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->premium()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // Create 100 members
    Member::factory()->count(100)->create([
        'family_tree_id' => $tree->id,
    ]);

    expect($tree->hasReachedMemberLimit())->toBeFalse()
        ->and($tree->canAddMembers(1))->toBeTrue();

    $this->actingAs($owner);

    // Add 101st member on premium tree
    Livewire::test('⚡member-manager', ['treeId' => $tree->id])
        ->set('first_name', 'Anggota')
        ->set('last_name', 'Ke-101')
        ->set('gender', 'female')
        ->call('save')
        ->assertHasNoErrors();

    expect($tree->refresh()->members()->count())->toBe(101);
});

it('allows user to initiate Duitku checkout for tree upgrade with 5K price', function () {
    Http::fake([
        '*' => Http::response([
            'statusCode' => '00',
            'statusMessage' => 'SUCCESS',
            'paymentUrl' => 'https://sandbox.duitku.com/payment/test-upgrade-url',
            'reference' => 'TEST-UPGRADE-REF',
        ], 200),
    ]);

    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $response = $this->actingAs($user)
        ->postJson(route('orders.checkout'), [
            'target_tree_id' => $tree->id,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'payment_url' => 'https://sandbox.duitku.com/payment/test-upgrade-url',
        ]);

    $order = Order::where('target_tree_id', $tree->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->order_type)->toBe('tree_upgrade')
        ->and($order->amount)->toBe(5000)
        ->and($order->quota_count)->toBe(0)
        ->and($order->status)->toBe('pending');
});

it('blocks tree upgrade checkout if user is not the tree owner or tree is already premium', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // Stranger tries to upgrade
    $res1 = $this->actingAs($stranger)->postJson(route('orders.checkout'), [
        'target_tree_id' => $tree->id,
    ]);
    $res1->assertStatus(403);

    // Owner tries to upgrade an already premium tree
    $tree->update(['is_premium' => true]);
    $res2 = $this->actingAs($owner)->postJson(route('orders.checkout'), [
        'target_tree_id' => $tree->id,
    ]);
    $res2->assertStatus(422)
        ->assertJsonFragment(['message' => 'Pohon silsilah ini sudah berstatus Premium.']);
});

it('applies referral discount to tree upgrade order', function () {
    Http::fake([
        '*' => Http::response([
            'statusCode' => '00',
            'statusMessage' => 'SUCCESS',
            'paymentUrl' => 'https://sandbox.duitku.com/payment/test-ref-url',
            'reference' => 'TEST-REF',
        ], 200),
    ]);

    $referrer = User::factory()->create(['referral_code' => 'SIL-PROMO1']);
    $user = User::factory()->create();

    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    // Validate code endpoint for tree upgrade
    $valRes = $this->actingAs($user)->postJson(route('orders.validate-code'), [
        'code' => 'SIL-PROMO1',
        'target_tree_id' => $tree->id,
    ]);

    // 15% of 5000 is 750 => final is 4250
    $valRes->assertOk()
        ->assertJson([
            'valid' => true,
            'original_amount' => 5000,
            'discount' => 750,
            'final_amount' => 4250,
        ]);

    // Checkout with referral code
    $checkoutRes = $this->actingAs($user)->postJson(route('orders.checkout'), [
        'target_tree_id' => $tree->id,
        'code' => 'SIL-PROMO1',
    ]);

    $checkoutRes->assertOk();

    $order = Order::where('target_tree_id', $tree->id)->first();
    expect($order->amount)->toBe(4250)
        ->and($order->discount_amount)->toBe(750)
        ->and($order->referrer_id)->toBe($referrer->id);
});

it('processes Duitku callback for tree upgrade, sets is_premium true and does not increment tree quota', function () {
    $user = User::factory()->create(['tree_quota' => 1]);
    $tree = FamilyTree::factory()->create(['is_premium' => false]);
    $tree->users()->attach($user->id, ['role' => 'owner']);

    $order = Order::create([
        'user_id' => $user->id,
        'target_tree_id' => $tree->id,
        'order_type' => 'tree_upgrade',
        'order_id' => 'SILSILAH-UPGRADE-123',
        'amount' => 5000,
        'quota_count' => 0,
        'status' => 'pending',
    ]);

    $duitkuService = app(DuitkuService::class);
    $duitkuService->processSuccessfulPayment('SILSILAH-UPGRADE-123', [
        'paymentCode' => 'QRIS',
        'reference' => 'DUITKU-REF-999',
    ]);

    $order->refresh();
    $tree->refresh();
    $user->refresh();

    expect($order->status)->toBe('paid')
        ->and($tree->is_premium)->toBeTrue()
        ->and($user->tree_quota)->toBe(1); // quota count unchanged
});

it('creates the first tree as standard free and subsequent purchased trees as premium', function () {
    $user = User::factory()->create(['tree_quota' => 2]);
    $this->actingAs($user);

    // Tree 1: First tree (Free/Standard)
    Livewire::test('⚡dashboard-trees')
        ->set('name', 'Pohon Pertama (Gratis)')
        ->call('createTree');

    $tree1 = FamilyTree::where('name', 'Pohon Pertama (Gratis)')->first();
    expect($tree1)->not->toBeNull()
        ->and($tree1->is_premium)->toBeFalse();

    // Tree 2: Subsequent tree created with purchased quota
    Livewire::test('⚡dashboard-trees')
        ->set('name', 'Pohon Kedua (Kuota Berbayar)')
        ->call('createTree');

    $tree2 = FamilyTree::where('name', 'Pohon Kedua (Kuota Berbayar)')->first();
    expect($tree2)->not->toBeNull()
        ->and($tree2->is_premium)->toBeTrue();
});
