<?php

use App\Models\FamilyTree;
use App\Models\Order;
use App\Models\TreeInvitation;
use App\Models\User;

test('scheduled maintenance cleans expired invitations and orders', function () {
    $user = User::factory()->create();
    $tree = FamilyTree::factory()->create();

    // Expired invitation (> 30 days old)
    $expiredInvite = TreeInvitation::create([
        'family_tree_id' => $tree->id,
        'email' => 'old@example.com',
        'role' => 'viewer',
        'token' => 'old_token_123',
    ]);
    $expiredInvite->timestamps = false;
    $expiredInvite->created_at = now()->subDays(35);
    $expiredInvite->save();

    // Active invitation (< 30 days old)
    $activeInvite = TreeInvitation::create([
        'family_tree_id' => $tree->id,
        'email' => 'active@example.com',
        'role' => 'editor',
        'token' => 'active_token_123',
    ]);

    // Expired pending order (> 24 hours old)
    $expiredOrder = Order::create([
        'user_id' => $user->id,
        'order_id' => 'ORD-OLD-999',
        'amount' => 10000,
        'status' => 'pending',
    ]);
    $expiredOrder->timestamps = false;
    $expiredOrder->created_at = now()->subHours(26);
    $expiredOrder->save();

    // Fresh pending order (< 24 hours old)
    $freshOrder = Order::create([
        'user_id' => $user->id,
        'order_id' => 'ORD-FRESH-111',
        'amount' => 10000,
        'status' => 'pending',
    ]);

    // Run maintenance command
    $this->artisan('silsilah:cleanup')
        ->expectsOutputToContain('Pembersihan selesai')
        ->assertSuccessful();

    expect(TreeInvitation::find($expiredInvite->id))->toBeNull();
    expect(TreeInvitation::find($activeInvite->id))->not->toBeNull();

    $expiredOrder->refresh();
    expect($expiredOrder->status)->toBe('expired');

    $freshOrder->refresh();
    expect($freshOrder->status)->toBe('pending');
});
