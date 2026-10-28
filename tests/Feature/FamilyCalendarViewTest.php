<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\User;
use App\Services\FamilyReminderService;
use Carbon\Carbon;
use Livewire\Livewire;

it('retrieves accurate month events from FamilyReminderService for specified month and year', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create(['name' => 'Bani Hasyim']);

    // Deceased member with death date in November (Haul)
    $deceased = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ahmad',
        'last_name' => 'Dahlan',
        'gender' => 'male',
        'is_living' => false,
        'death_date' => '2021-11-05',
    ]);

    // Living member with birthday in November
    $living = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Fatimah',
        'last_name' => 'Azzahra',
        'gender' => 'female',
        'is_living' => true,
        'birth_date' => '1996-11-12',
    ]);

    // Marriage anniversary in November
    $husband = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ali',
        'gender' => 'male',
        'is_living' => true,
    ]);
    $wife = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Zahra',
        'gender' => 'female',
        'is_living' => true,
    ]);
    $marriage = Marriage::create([
        'husband_id' => $husband->id,
        'wife_id' => $wife->id,
        'marriage_date' => '2016-11-20',
        'is_current' => true,
    ]);

    // Event in a different month (December) - should be excluded
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Desember',
        'is_living' => true,
        'birth_date' => '2000-12-25',
    ]);

    $service = app(FamilyReminderService::class);
    $events = $service->getMonthEvents($tree, 2026, 11);

    expect($events)->toHaveCount(3);

    // Verify Haul
    $haul = $events->firstWhere('type', 'haul');
    expect($haul)->not->toBeNull()
        ->and($haul['ordinal'])->toBe(5)
        ->and($haul['ordinal_text'])->toBe('Haul ke-5')
        ->and($haul['title'])->toContain('Haul ke-5 Almarhum Ahmad Dahlan')
        ->and($haul['wa_share_url'])->toContain('wa.me');

    // Verify Birthday
    $birthday = $events->firstWhere('type', 'birthday');
    expect($birthday)->not->toBeNull()
        ->and($birthday['ordinal'])->toBe(30)
        ->and($birthday['ordinal_text'])->toBe('Ulang Tahun ke-30')
        ->and($birthday['title'])->toContain('Ulang Tahun ke-30 Fatimah Azzahra');

    // Verify Marriage
    $anniversary = $events->firstWhere('type', 'marriage');
    expect($anniversary)->not->toBeNull()
        ->and($anniversary['ordinal'])->toBe(10)
        ->and($anniversary['ordinal_text'])->toBe('Ulang Tahun Pernikahan ke-10')
        ->and($anniversary['title'])->toContain('Ali')
        ->and($anniversary['title'])->toContain('Zahra');
});

it('supports interactive monthly calendar grid navigation and day selection', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Keluarga Bahagia']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'is_living' => false,
        'death_date' => '2021-11-05',
    ]);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Nenek',
        'is_living' => false,
        'death_date' => '2019-12-10',
    ]);

    $this->actingAs($owner);

    $component = Livewire::test('⚡family-reminders', ['treeId' => $tree->id])
        ->assertSet('viewMode', 'calendar')
        ->assertSet('currentYear', 2026)
        ->assertSet('currentMonth', 11)
        ->assertSee('November 2026')
        ->assertSee('Haul ke-5 Kakek');

    // Navigate to next month (December 2026)
    $component->call('nextMonth')
        ->assertSet('currentMonth', 12)
        ->assertSet('currentYear', 2026)
        ->assertSee('Desember 2026')
        ->assertSee('Haul ke-7 Nenek');

    // Navigate to previous month twice (back to October 2026)
    $component->call('prevMonth')
        ->assertSet('currentMonth', 11)
        ->call('prevMonth')
        ->assertSet('currentMonth', 10)
        ->assertSee('Oktober 2026');

    // Return to today
    $component->call('goToToday')
        ->assertSet('currentMonth', 11)
        ->assertSet('currentYear', 2026)
        ->assertSet('selectedDay', 1);

    // Select day 5
    $component->call('selectDay', 5)
        ->assertSet('selectedDay', 5)
        ->assertSee('Haul ke-5 Kakek')
        ->assertSee('Kirim ke WhatsApp');

    // Deselect day 5
    $component->call('selectDay', 5)
        ->assertSet('selectedDay', null);
});

it('switches between calendar and list view modes', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Budi',
        'is_living' => true,
        'birth_date' => '1990-11-15',
    ]);

    $this->actingAs($owner);

    $component = Livewire::test('⚡family-reminders', ['treeId' => $tree->id])
        ->assertSet('viewMode', 'calendar')
        ->assertSee('Bulan Ini')
        ->call('setViewMode', 'list')
        ->assertSet('viewMode', 'list')
        ->assertSee('Rentang:')
        ->call('setViewMode', 'calendar')
        ->assertSet('viewMode', 'calendar');
});

it('renders authenticated dedicated calendar page for authorized user', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $tree = FamilyTree::factory()->create([
        'name' => 'Trah Sastro',
        'slug' => 'trah-sastro',
        'is_public' => false,
    ]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // Guest redirected to login
    $this->get(route('tree.calendar', $tree->slug))
        ->assertRedirect(route('login'));

    // Stranger forbidden
    $this->actingAs($stranger)
        ->get(route('tree.calendar', $tree->slug))
        ->assertForbidden();

    // Owner allowed
    $response = $this->actingAs($owner)
        ->get(route('tree.calendar', $tree->slug));

    $response->assertOk()
        ->assertSee('Kalender Agenda — Trah Sastro')
        ->assertSee('Kembali ke Silsilah');
});

it('renders public dedicated calendar page for public tree', function () {
    $publicTree = FamilyTree::factory()->create([
        'name' => 'Keluarga Besar Nusantara',
        'slug' => 'keluarga-nusantara',
        'is_public' => true,
    ]);

    $response = $this->get(route('tree.public.calendar', $publicTree->slug));

    $response->assertOk()
        ->assertSee('Kalender &amp; Agenda — Keluarga Besar Nusantara', false)
        ->assertSee('Bagan Silsilah');
});

it('handles password protected public tree on public calendar page', function () {
    $protectedTree = FamilyTree::factory()->create([
        'name' => 'Keluarga Terproteksi',
        'slug' => 'keluarga-terproteksi',
        'is_public' => true,
        'view_password' => 'rahasia123',
    ]);

    // Unauthenticated guest without session gets redirected to password page
    $this->get(route('tree.public.calendar', $protectedTree->slug))
        ->assertRedirect(route('tree.password', $protectedTree->slug));

    // With unlocked session, access is granted
    session(["tree_unlocked_{$protectedTree->id}" => true]);

    $this->get(route('tree.public.calendar', $protectedTree->slug))
        ->assertOk()
        ->assertSee('Kalender &amp; Agenda — Keluarga Terproteksi', false);
});
