<?php

use App\Models\FamilyEvent;
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
        ->assertDontSee('md:w-[58rem]');
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

it('renders custom family events like halal bi halal in calendar and upcoming lists', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Bani Abdurrahman']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    FamilyEvent::create([
        'family_tree_id' => $tree->id,
        'created_by' => $owner->id,
        'title' => 'Halal bi Halal Akbar 1448 H',
        'event_type' => 'halal_bi_halal',
        'event_date' => '2026-11-10',
        'start_time' => '09:00:00',
        'end_time' => '13:00:00',
        'location_name' => 'Kediaman Bpk. Ahmad',
        'location_maps_url' => 'https://maps.app.goo.gl/test',
        'description' => 'Membawa pakaian muslim nuansa hijau.',
        'contact_person' => 'Budi (08123456789)',
    ]);

    $this->actingAs($owner);

    Livewire::test('⚡family-reminders', ['treeId' => $tree->id])
        ->assertSee('Halal bi Halal Akbar 1448 H')
        ->assertSee('Kediaman Bpk. Ahmad')
        ->assertSee('09:00')
        ->assertSee('Budi (08123456789)')
        ->assertSee('Undangan WA');
});

it('allows tree managers to create, edit, and delete family events', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $owner = User::factory()->create();
    $tree = FamilyTree::factory()->create(['name' => 'Bani Abdurrahman']);
    $tree->users()->attach($owner->id, ['role' => 'owner']);

    $this->actingAs($owner);

    $component = Livewire::test('⚡family-reminders', ['treeId' => $tree->id])
        ->call('openCreateEventModal')
        ->assertSet('showEventModal', true)
        ->set('eventTitle', 'Pertemuan Arisan Trah')
        ->set('eventType', 'arisan')
        ->set('eventDate', '2026-11-15')
        ->set('startTime', '10:00')
        ->set('locationName', 'Aula Graha Keluarga')
        ->call('saveEvent')
        ->assertHasNoErrors()
        ->assertSet('showEventModal', false);

    $event = FamilyEvent::where('family_tree_id', $tree->id)->first();
    expect($event)->not->toBeNull();
    expect($event->title)->toBe('Pertemuan Arisan Trah');
    expect($event->event_type)->toBe('arisan');

    // Edit event
    $component->call('editEvent', $event->id)
        ->assertSet('showEventModal', true)
        ->assertSet('eventTitle', 'Pertemuan Arisan Trah')
        ->set('eventTitle', 'Pertemuan Arisan Trah Periode 2')
        ->call('saveEvent')
        ->assertHasNoErrors();

    expect($event->fresh()->title)->toBe('Pertemuan Arisan Trah Periode 2');

    // Delete event
    $component->call('deleteEvent', $event->id);
    expect(FamilyEvent::find($event->id))->toBeNull();
});

it('includes custom events in ics calendar feed with location and proper time', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create([
        'name' => 'Bani Mochtar',
        'slug' => 'bani-mochtar-custom',
        'is_public' => true,
    ]);

    FamilyEvent::create([
        'family_tree_id' => $tree->id,
        'title' => 'Haul Akbar Jam\'i Masyayikh',
        'event_type' => 'haul_akbar',
        'event_date' => '2026-11-20',
        'start_time' => '19:30:00',
        'end_time' => '22:00:00',
        'location_name' => 'Masjid Jami',
        'is_recurring_yearly' => false,
    ]);

    $response = $this->get(route('tree.calendar.ics', $tree->slug));
    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toContain('SUMMARY:Haul Akbar Jam\'i Masyayikh')
        ->toContain('LOCATION:Masjid Jami')
        ->toContain('DTSTART;TZID=Asia/Jakarta:20261120T193000')
        ->toContain('DTEND;TZID=Asia/Jakarta:20261120T220000');
});
