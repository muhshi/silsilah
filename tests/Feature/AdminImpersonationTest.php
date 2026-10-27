<?php

use App\Models\User;

test('admin can impersonate regular user and sees sticky banner', function () {
    $admin = User::factory()->admin()->create([
        'name' => 'Bambang Admin',
    ]);
    $user = User::factory()->create([
        'name' => 'Siti Khadijah',
        'email' => 'siti@example.com',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.impersonate', $user));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    expect(session('impersonator_admin_id'))->toBe($admin->id);

    // Follow to dashboard and verify banner is shown
    $dashboardResponse = $this->get(route('dashboard'));
    $dashboardResponse->assertOk();
    $dashboardResponse->assertSee('Mode Bantuan');
    $dashboardResponse->assertSee('Siti Khadijah');
    $dashboardResponse->assertSee('Kembali ke Superadmin');
});

test('regular user cannot initiate impersonation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $response = $this->actingAs($user1)
        ->post(route('admin.impersonate', $user2));

    $response->assertForbidden();
    $this->assertAuthenticatedAs($user1);
});

test('admin cannot impersonate another admin', function () {
    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();

    $response = $this->actingAs($admin1)
        ->from(route('admin.users'))
        ->post(route('admin.impersonate', $admin2));

    $response->assertRedirect(route('admin.users'));
    $response->assertSessionHas('error');
    $this->assertAuthenticatedAs($admin1);
});

test('admin can leave impersonation and return to admin panel', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    // Start impersonation
    $this->actingAs($admin)->post(route('admin.impersonate', $user));
    $this->assertAuthenticatedAs($user);

    // Leave impersonation
    $response = $this->post(route('admin.leave-impersonation'));

    $response->assertRedirect(route('admin.users'));
    $this->assertAuthenticatedAs($admin);
    expect(session()->has('impersonator_admin_id'))->toBeFalse();
});
