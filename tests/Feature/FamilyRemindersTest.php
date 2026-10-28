<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

it('downloads standard ics calendar feed for public tree reminders', function () {
    $tree = FamilyTree::factory()->create([
        'name' => 'Bani Mochtar',
        'slug' => 'bani-mochtar',
        'is_public' => true,
    ]);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'is_living' => false,
        'death_date' => now()->addDays(5)->subYears(5)->format('Y-m-d'),
    ]);

    $response = $this->get(route('tree.calendar.ics', $tree->slug));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    $response->assertHeader('Content-Disposition', 'inline; filename="silsilah-bani-mochtar.ics"');
    $content = $response->getContent();
    expect($content)->toContain('BEGIN:VCALENDAR')
        ->toContain('VERSION:2.0')
        ->toContain('PRODID:-//Silsilah Family Tree//Kalender Keluarga//ID')
        ->toContain('BEGIN:VEVENT')
        ->toContain('SUMMARY:Haul')
        ->toContain('RRULE:FREQ=YEARLY')
        ->toContain('END:VCALENDAR');
});

it('restricts calendar feed for private tree unless authorized', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $tree = FamilyTree::factory()->create([
        'name' => 'Keluarga Privat',
        'slug' => 'keluarga-privat',
        'is_public' => false,
    ]);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // Guest cannot access private calendar
    $this->get(route('tree.calendar.ics', $tree->slug))
        ->assertForbidden();

    // Stranger cannot access private calendar
    $this->actingAs($stranger)
        ->get(route('tree.calendar.ics', $tree->slug))
        ->assertForbidden();

    // Owner can access private calendar
    $this->actingAs($owner)
        ->get(route('tree.calendar.ics', $tree->slug))
        ->assertOk();
});

it('renders upcoming family reminders with WhatsApp share buttons', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Bani Mochtar']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'is_living' => false,
        'death_date' => '2021-11-05',
    ]);

    $this->actingAs($owner);

    Livewire::test('⚡family-reminders', ['treeId' => $tree->id])
        ->assertSee('Haul ke-5')
        ->assertSee('Kirim ke WhatsApp');
});

it('renders all types of events: haul, birthday, and wedding anniversary', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Trah Sukses']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // Haul in 4 days
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Mbah',
        'last_name' => 'Kakung',
        'is_living' => false,
        'death_date' => '2016-11-05',
    ]);

    // Birthday in 9 days
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ahmad',
        'last_name' => 'Fauzi',
        'is_living' => true,
        'birth_date' => '1996-11-10',
    ]);

    // Anniversary in 14 days
    $husband = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Joko',
        'last_name' => null,
        'gender' => 'male',
        'is_living' => true,
    ]);
    $wife = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Siti',
        'last_name' => null,
        'gender' => 'female',
        'is_living' => true,
    ]);
    Marriage::create([
        'husband_id' => $husband->id,
        'wife_id' => $wife->id,
        'marriage_date' => '2018-11-15',
        'is_current' => true,
    ]);

    $this->actingAs($owner);

    Livewire::test('⚡family-reminders', ['treeId' => $tree->id])
        ->assertSee('Haul ke-10')
        ->assertSee('Ulang Tahun ke-30 Ahmad Fauzi')
        ->assertSee('Ulang Tahun Pernikahan ke-8 Joko & Siti')
        ->assertSee('Sinkron ke Kalender')
        ->assertSee('https://wa.me/?text=');
});

it('filters reminders by event type', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'AlmHaji',
        'is_living' => false,
        'death_date' => '2020-11-05',
    ]);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'BocahUltah',
        'is_living' => true,
        'birth_date' => '2015-11-10',
    ]);

    $this->actingAs($owner);

    $component = Livewire::test('⚡family-reminders', ['treeId' => $tree->id]);

    // Default 'all' shows both
    $component->assertSee('AlmHaji')
        ->assertSee('BocahUltah');

    // Filter to 'haul' only
    $component->set('filterType', 'haul')
        ->assertSee('AlmHaji')
        ->assertDontSee('BocahUltah');

    // Filter to 'birthday' only
    $component->set('filterType', 'birthday')
        ->assertSee('BocahUltah')
        ->assertDontSee('AlmHaji');

    // Filter to 'marriage' only
    $component->set('filterType', 'marriage')
        ->assertDontSee('AlmHaji')
        ->assertDontSee('BocahUltah');
});

it('adjusts window of upcoming events with daysAhead', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    // Event in 10 days
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Dekat',
        'is_living' => true,
        'birth_date' => '2000-11-11',
    ]);

    // Event in 50 days (Dec 21)
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Jauh',
        'is_living' => true,
        'birth_date' => '2000-12-21',
    ]);

    $this->actingAs($owner);

    $component = Livewire::test('⚡family-reminders', ['treeId' => $tree->id, 'daysAhead' => 30]);

    $component->assertSee('Dekat')
        ->assertDontSee('Jauh');

    $component->set('daysAhead', 60)
        ->assertSee('Dekat')
        ->assertSee('Jauh');
});

it('renders in embedded mode without modal wrapper', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Bani Mochtar']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'is_living' => false,
        'death_date' => '2021-11-05',
    ]);

    $this->actingAs($owner);

    Livewire::test('⚡family-reminders', ['treeId' => $tree->id, 'asModal' => false])
        ->assertSee('Haul ke-5')
        ->assertSee('Kirim ke WhatsApp')
        ->assertDontSee('data-flux-modal');
});

it('can switch to reminders tab in tree settings', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Bani Mochtar']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek',
        'is_living' => false,
        'death_date' => '2021-11-05',
    ]);

    $this->actingAs($owner);

    Livewire::test('⚡tree-settings', ['treeId' => $tree->id])
        ->assertSee('Agenda &amp; Pengingat', false)
        ->set('activeTab', 'reminders')
        ->assertSee('Haul ke-5')
        ->assertSee('Sinkron ke Kalender');
});

it('serves ics feed as attachment when download param is true', function () {
    $tree = FamilyTree::factory()->create(['is_public' => true]);

    $response = $this->get(route('tree.calendar.ics', ['tree' => $tree->slug, 'download' => 1]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment;');
});

it('opens modal on open-family-reminders event', function () {
    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create();
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    Livewire::test('⚡family-reminders', ['treeId' => $tree->id])
        ->assertSet('showModal', false)
        ->dispatch('open-family-reminders')
        ->assertSet('showModal', true);
});
