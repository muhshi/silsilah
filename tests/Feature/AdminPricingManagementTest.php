<?php

use App\Models\PricingPackage;
use App\Models\User;
use Livewire\Livewire;

test('superadmin can access pricing management page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.pricing'));
    $response->assertOk();
    $response->assertSee('Paket & Harga Kuota');
    $response->assertSee('Promo Idul Fitri');
});

test('non-admin cannot access pricing management page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.pricing'));
    $response->assertForbidden();
});

test('admin can toggle pricing package active status', function () {
    $admin = User::factory()->admin()->create();

    $package = PricingPackage::where('slug', 'promo-idul-fitri')->firstOrFail();
    expect($package->is_active)->toBeTrue();

    Livewire::actingAs($admin)
        ->test('admin-pricing')
        ->call('toggleActive', $package->id);

    $package->refresh();
    expect($package->is_active)->toBeFalse();

    // Toggle back
    Livewire::actingAs($admin)
        ->test('admin-pricing')
        ->call('toggleActive', $package->id);

    $package->refresh();
    expect($package->is_active)->toBeTrue();
});

test('admin can update pricing package details', function () {
    $admin = User::factory()->admin()->create();

    $package = PricingPackage::where('slug', 'satuan')->firstOrFail();

    Livewire::actingAs($admin)
        ->test('admin-pricing')
        ->call('editPackage', $package->id)
        ->set('price', 12000)
        ->set('badge', 'BEST VALUE')
        ->call('updatePackage');

    $package->refresh();
    expect($package->price)->toBe(12000);
    expect($package->badge)->toBe('BEST VALUE');
});

test('admin can set seasonal schedule on pricing package', function () {
    $admin = User::factory()->admin()->create();

    $package = PricingPackage::where('slug', 'promo-idul-fitri')->firstOrFail();

    Livewire::actingAs($admin)
        ->test('admin-pricing')
        ->call('editPackage', $package->id)
        ->call('setRamadanPreset', 2026)
        ->call('updatePackage');

    $package->refresh();
    expect($package->starts_at)->not->toBeNull()
        ->and($package->ends_at)->not->toBeNull()
        ->and($package->starts_at->year)->toBe(2026);

    // Test clear schedule
    Livewire::actingAs($admin)
        ->test('admin-pricing')
        ->call('editPackage', $package->id)
        ->call('clearSchedule')
        ->call('updatePackage');

    $package->refresh();
    expect($package->starts_at)->toBeNull()
        ->and($package->ends_at)->toBeNull();
});
