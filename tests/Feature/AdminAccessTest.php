<?php

use App\Models\User;

test('new user defaults to user role and is not admin', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe('user')
        ->and($user->isAdmin())->toBeFalse();
});

test('user with admin role returns true for isAdmin', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->role)->toBe('admin')
        ->and($admin->isAdmin())->toBeTrue();
});

test('guest cannot access admin area and is redirected to login', function () {
    $this->get('/admin')
        ->assertRedirect('/login');
});

test('regular user cannot access admin area and receives 403 forbidden', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('admin user can access admin area', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

test('admin sees superadmin link in dashboard but regular user does not', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertSee('Panel Superadmin');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertDontSee('Panel Superadmin');
});
