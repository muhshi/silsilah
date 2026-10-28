<?php

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use App\Services\FamilyReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('calculates upcoming birthdays and hauls with correct ordinals (plan spec)', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create(['name' => 'Bani Mochtar']);

    // Living member with birthday in 5 days (Nov 6)
    $bdayMember = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Fauzi',
        'is_living' => true,
        'birth_date' => '1990-11-06',
    ]);

    // Deceased member with haul in 10 days (Nov 11)
    $haulMember = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Mochtar',
        'is_living' => false,
        'death_date' => '2020-11-11',
    ]);

    $service = new FamilyReminderService;
    $reminders = $service->getUpcomingReminders($tree, 30);

    expect($reminders)->toHaveCount(2);

    $bday = $reminders->firstWhere('type', 'birthday');
    expect($bday['member']->id)->toBe($bdayMember->id)
        ->and($bday['ordinal_text'])->toBe('Ulang Tahun ke-36')
        ->and($bday['days_until'])->toBe(5);

    $haul = $reminders->firstWhere('type', 'haul');
    expect($haul['member']->id)->toBe($haulMember->id)
        ->and($haul['ordinal_text'])->toBe('Haul ke-6')
        ->and($haul['days_until'])->toBe(10);
});

test('detects upcoming birthdays for living members within next N days with correct age and days until', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create(['name' => 'Keluarga Somad']);

    $member = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Ahmad',
        'last_name' => 'Dahlan',
        'gender' => 'male',
        'is_living' => true,
        'birth_date' => '2000-11-15',
    ]);

    $service = new FamilyReminderService;
    $events = $service->getUpcomingEvents($tree, 30);

    expect($events)->toHaveCount(1);
    $event = $events->first();

    expect($event['id'])->toBe("birthday-{$member->id}")
        ->and($event['type'])->toBe('birthday')
        ->and($event['title'])->toContain('Ulang Tahun ke-26')
        ->and($event['ordinal'])->toBe(26)
        ->and($event['days_until'])->toBe(14)
        ->and($event['date']->format('Y-m-d'))->toBe('2026-11-15')
        ->and($event['original_date']->format('Y-m-d'))->toBe('2000-11-15')
        ->and($event['member']->id)->toBe($member->id)
        ->and($event['wa_share_url'])->toContain('https://wa.me/?text=');
});

test('detects upcoming haul for deceased members calculating correct haul count and days until', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create(['name' => 'Keluarga Besar H. Mochtar']);

    // Deceased with is_living false
    $member1 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'H. Mochtar',
        'gender' => 'male',
        'is_living' => false,
        'death_date' => '2021-11-05',
    ]);

    // Deceased with death_date set even if is_living was not explicitly set to false
    $member2 = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Hj. Siti',
        'gender' => 'female',
        'is_living' => true,
        'death_date' => '2019-11-20',
    ]);

    $service = new FamilyReminderService;
    $events = $service->getUpcomingEvents($tree, 30);

    expect($events)->toHaveCount(2);

    $haul1 = $events->firstWhere('id', "haul-{$member1->id}");
    expect($haul1['type'])->toBe('haul')
        ->and($haul1['ordinal'])->toBe(5)
        ->and($haul1['ordinal_text'])->toBe('Haul ke-5')
        ->and($haul1['days_until'])->toBe(4)
        ->and($haul1['title'])->toContain('Almarhum')
        ->and($haul1['title'])->toContain('Haul ke-5');

    $haul2 = $events->firstWhere('id', "haul-{$member2->id}");
    expect($haul2['type'])->toBe('haul')
        ->and($haul2['ordinal'])->toBe(7)
        ->and($haul2['ordinal_text'])->toBe('Haul ke-7')
        ->and($haul2['days_until'])->toBe(19)
        ->and($haul2['title'])->toContain('Almarhumah')
        ->and($haul2['title'])->toContain('Haul ke-7');
});

test('detects upcoming wedding anniversaries from marriages with marriage_date', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create(['name' => 'Bani Salim']);

    $husband = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Budi',
        'last_name' => 'Santoso',
        'gender' => 'male',
        'is_living' => true,
    ]);

    $wife = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Siti',
        'last_name' => 'Aminah',
        'gender' => 'female',
        'is_living' => true,
    ]);

    $marriage = Marriage::create([
        'husband_id' => $husband->id,
        'wife_id' => $wife->id,
        'marriage_date' => '2016-11-10',
        'is_current' => true,
    ]);

    $service = new FamilyReminderService;
    $events = $service->getUpcomingEvents($tree, 30);

    expect($events)->toHaveCount(1);
    $event = $events->first();

    expect($event['id'])->toBe("marriage-{$marriage->id}")
        ->and($event['type'])->toBe('marriage')
        ->and($event['ordinal'])->toBe(10)
        ->and($event['ordinal_text'])->toBe('Ulang Tahun Pernikahan ke-10')
        ->and($event['title'])->toContain('Ulang Tahun Pernikahan ke-10')
        ->and($event['title'])->toContain('Budi')
        ->and($event['title'])->toContain('Siti')
        ->and($event['days_until'])->toBe(9)
        ->and($event['member']->id)->toBe($husband->id)
        ->and($event['spouse']->id)->toBe($wife->id);
});

test('orders events chronologically by days_until ascending', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create();

    // Event in 20 days
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Far',
        'is_living' => true,
        'birth_date' => '1995-11-21',
    ]);

    // Event in 2 days
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Near',
        'is_living' => true,
        'birth_date' => '1995-11-03',
    ]);

    // Event in 10 days
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Middle',
        'is_living' => true,
        'birth_date' => '1995-11-11',
    ]);

    $service = new FamilyReminderService;
    $events = $service->getUpcomingEvents($tree, 30);

    expect($events)->toHaveCount(3)
        ->and($events->pluck('days_until')->all())->toBe([2, 10, 20]);
});

test('handles year boundary transitions correctly (e.g. late December to early January)', function () {
    Carbon::setTestNow(Carbon::parse('2026-12-25'));

    $tree = FamilyTree::factory()->create();

    // Birthday on Jan 5 (11 days ahead, next year 2027)
    $member = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'JanuaryBaby',
        'is_living' => true,
        'birth_date' => '1990-01-05',
    ]);

    $service = new FamilyReminderService;
    $events = $service->getUpcomingEvents($tree, 30);

    expect($events)->toHaveCount(1);
    $event = $events->first();

    expect($event['days_until'])->toBe(11)
        ->and($event['date']->format('Y-m-d'))->toBe('2027-01-05')
        ->and($event['ordinal'])->toBe(37) // 2027 - 1990
        ->and($event['ordinal_text'])->toBe('Ulang Tahun ke-37');
});

test('ignores members with missing or unknown dates', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create();

    // Missing birth date
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'NoBirthDate',
        'is_living' => true,
        'birth_date' => null,
    ]);

    // Deceased without death date
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'NoDeathDate',
        'is_living' => false,
        'death_date' => null,
    ]);

    // Member outside 30 days window
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'LaterInYear',
        'is_living' => true,
        'birth_date' => '1990-12-15',
    ]);

    $service = new FamilyReminderService;
    $events = $service->getUpcomingEvents($tree, 30);

    expect($events)->toBeEmpty();
});

test('formats polite, culturally appropriate WhatsApp broadcast messages', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create(['name' => 'Bani Mochtar']);

    $deceased = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Mochtar',
        'gender' => 'male',
        'is_living' => false,
        'death_date' => '2021-11-05',
    ]);

    $living = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Fauzi',
        'gender' => 'male',
        'is_living' => true,
        'birth_date' => '1990-11-06',
    ]);

    $wife = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Aisyah',
        'gender' => 'female',
        'is_living' => true,
    ]);

    $marriage = Marriage::create([
        'husband_id' => $living->id,
        'wife_id' => $wife->id,
        'marriage_date' => '2016-11-10',
        'is_current' => true,
    ]);

    $service = new FamilyReminderService;
    $events = $service->getUpcomingEvents($tree, 30);

    $haulEvent = $events->firstWhere('id', "haul-{$deceased->id}");
    $haulMsg = $service->formatWhatsAppMessage($haulEvent, $tree);

    expect($haulMsg)->toContain("Assalamu'alaikum")
        ->and($haulMsg)->toContain('Bani Mochtar')
        ->and($haulMsg)->toContain('Haul ke-5')
        ->and($haulMsg)->toContain('Fatihah');

    $bdayEvent = $events->firstWhere('id', "birthday-{$living->id}");
    $bdayMsg = $service->formatWhatsAppMessage($bdayEvent, $tree);

    expect($bdayMsg)->toContain("Assalamu'alaikum")
        ->and($bdayMsg)->toContain('Bani Mochtar')
        ->and($bdayMsg)->toContain('Ulang Tahun ke-36')
        ->and($bdayMsg)->toContain('kesehatan')
        ->and($bdayMsg)->toContain('berkah');

    $marriageEvent = $events->firstWhere('id', "marriage-{$marriage->id}");
    $marriageMsg = $service->formatWhatsAppMessage($marriageEvent, $tree);

    expect($marriageMsg)->toContain("Assalamu'alaikum")
        ->and($marriageMsg)->toContain('Bani Mochtar')
        ->and($marriageMsg)->toContain('Ulang Tahun Pernikahan ke-10')
        ->and($marriageMsg)->toContain('sakinah');
});

test('generates standard RFC 5545 ICS calendar feed', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create(['name' => 'Bani Mochtar']);

    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Kakek Mochtar',
        'is_living' => false,
        'death_date' => '2021-11-05',
    ]);

    $service = new FamilyReminderService;
    $events = $service->getUpcomingEvents($tree, 30);
    $ics = $service->generateIcsFeed($tree, $events);

    expect($ics)->toContain('BEGIN:VCALENDAR')
        ->and($ics)->toContain('VERSION:2.0')
        ->and($ics)->toContain('BEGIN:VEVENT')
        ->and($ics)->toContain('SUMMARY')
        ->and($ics)->toContain('DESCRIPTION')
        ->and($ics)->toContain('DTSTART;VALUE=DATE')
        ->and($ics)->toContain('RRULE:FREQ=YEARLY')
        ->and($ics)->toContain('END:VEVENT')
        ->and($ics)->toContain('END:VCALENDAR');

    // Also verify generateIcsFeed works when $events parameter is omitted
    $autoIcs = $service->generateIcsFeed($tree);
    expect($autoIcs)->toContain('BEGIN:VCALENDAR')
        ->and($autoIcs)->toContain('BEGIN:VEVENT');
});

test('handles events occurring today with days_until 0', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create();

    $member = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'TodayBaby',
        'is_living' => true,
        'birth_date' => '2000-11-01',
    ]);

    $service = new FamilyReminderService;
    $events = $service->getUpcomingEvents($tree, 30);

    expect($events)->toHaveCount(1);
    $event = $events->first();

    expect($event['days_until'])->toBe(0)
        ->and($event['description'])->toContain('hari ini')
        ->and($event['ordinal'])->toBe(26);
});

test('handles leap day birthdays in non-leap years gracefully', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-20'));

    $tree = FamilyTree::factory()->create();

    $member = Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'LeapBaby',
        'is_living' => true,
        'birth_date' => '2000-02-29',
    ]);

    $service = new FamilyReminderService;
    $events = $service->getUpcomingEvents($tree, 30);

    expect($events)->toHaveCount(1);
    $event = $events->first();

    expect($event['date']->format('Y-m-d'))->toBe('2026-02-28')
        ->and($event['days_until'])->toBe(8)
        ->and($event['ordinal'])->toBe(26);
});

test('does not celebrate birthdays for deceased members with both birth and death dates', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01'));

    $tree = FamilyTree::factory()->create();

    // Deceased member with birthday in 3 days, and death in 5 months
    Member::factory()->create([
        'family_tree_id' => $tree->id,
        'first_name' => 'Alm. Soekarno',
        'is_living' => false,
        'birth_date' => '1901-11-04',
        'death_date' => '1970-06-21',
    ]);

    $service = new FamilyReminderService;
    $events = $service->getUpcomingEvents($tree, 30);

    // Death date (June) is not in November, and birthday must NOT be triggered
    expect($events)->toBeEmpty();
});
