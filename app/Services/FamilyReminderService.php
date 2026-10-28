<?php

namespace App\Services;

use App\Models\FamilyTree;
use App\Models\Marriage;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FamilyReminderService
{
    /**
     * Get upcoming family events (birthdays, hauls, wedding anniversaries) within the next N days.
     *
     * @return Collection<int, array{
     *     id: string,
     *     type: 'haul'|'birthday'|'marriage',
     *     title: string,
     *     member: Member|null,
     *     spouse: Member|null,
     *     date: Carbon,
     *     original_date: Carbon,
     *     days_until: int,
     *     ordinal: int,
     *     ordinal_text: string,
     *     description: string,
     *     wa_share_url: string,
     *     whatsapp_message: string,
     * }>
     */
    public function getUpcomingEvents(FamilyTree $tree, int $daysAhead = 30): Collection
    {
        $today = Carbon::today();
        $events = collect();

        // 1. Process Members (Birthdays & Hauls)
        $members = $tree->relationLoaded('members')
            ? $tree->members->filter(fn ($m) => ! empty($m->birth_date) || ! empty($m->death_date))
            : $tree->members()
                ->where(function ($query) {
                    $query->whereNotNull('birth_date')
                        ->orWhereNotNull('death_date');
                })
                ->get();

        foreach ($members as $member) {
            $isDeceased = ($member->is_living === false)
                || ($member->is_alive === false)
                || ! empty($member->death_date);

            if ($isDeceased) {
                // Haul for deceased member
                if (! empty($member->death_date)) {
                    $originalDate = Carbon::parse($member->death_date)->startOfDay();
                    $occurrence = $this->calculateUpcomingOccurrence($originalDate, $today, $daysAhead);

                    if ($occurrence) {
                        $prefix = ($member->gender === 'female') ? 'Almarhumah' : 'Almarhum';
                        $fullName = $this->getFullName($member);
                        $displayName = preg_match('/almarhum/i', $fullName) ? $fullName : "{$prefix} {$fullName}";

                        $ordinalText = "Haul ke-{$occurrence['ordinal']}";
                        $title = "Haul ke-{$occurrence['ordinal']} {$displayName}";
                        $daysText = $this->getRelativeDaysText($occurrence['days_until']);
                        $description = "Peringatan {$ordinalText} {$displayName} ({$daysText})";

                        $eventData = [
                            'id' => "haul-{$member->id}",
                            'type' => 'haul',
                            'title' => $title,
                            'member' => $member,
                            'spouse' => null,
                            'date' => $occurrence['date'],
                            'original_date' => $originalDate,
                            'days_until' => $occurrence['days_until'],
                            'ordinal' => $occurrence['ordinal'],
                            'ordinal_text' => $ordinalText,
                            'description' => $description,
                        ];

                        $waMessage = $this->formatWhatsAppMessage($eventData, $tree);
                        $eventData['whatsapp_message'] = $waMessage;
                        $eventData['wa_share_url'] = 'https://wa.me/?text='.rawurlencode($waMessage);

                        $events->push($eventData);
                    }
                }
            } else {
                // Birthday for living member
                if (! empty($member->birth_date) && empty($member->is_birth_date_unknown)) {
                    $originalDate = Carbon::parse($member->birth_date)->startOfDay();
                    $occurrence = $this->calculateUpcomingOccurrence($originalDate, $today, $daysAhead);

                    if ($occurrence) {
                        $fullName = $this->getFullName($member);
                        $ordinalText = "Ulang Tahun ke-{$occurrence['ordinal']}";
                        $title = "Ulang Tahun ke-{$occurrence['ordinal']} {$fullName}";
                        $daysText = $this->getRelativeDaysText($occurrence['days_until']);
                        $description = "{$ordinalText} {$fullName} ({$daysText})";

                        $eventData = [
                            'id' => "birthday-{$member->id}",
                            'type' => 'birthday',
                            'title' => $title,
                            'member' => $member,
                            'spouse' => null,
                            'date' => $occurrence['date'],
                            'original_date' => $originalDate,
                            'days_until' => $occurrence['days_until'],
                            'ordinal' => $occurrence['ordinal'],
                            'ordinal_text' => $ordinalText,
                            'description' => $description,
                        ];

                        $waMessage = $this->formatWhatsAppMessage($eventData, $tree);
                        $eventData['whatsapp_message'] = $waMessage;
                        $eventData['wa_share_url'] = 'https://wa.me/?text='.rawurlencode($waMessage);

                        $events->push($eventData);
                    }
                }
            }
        }

        // 2. Process Marriages (Wedding Anniversaries)
        $memberIds = $tree->relationLoaded('members')
            ? $tree->members->pluck('id')
            : $tree->members()->pluck('id');

        $marriages = Marriage::with(['husband', 'wife'])
            ->where(function ($query) use ($memberIds) {
                $query->whereIn('husband_id', $memberIds)
                    ->orWhereIn('wife_id', $memberIds);
            })
            ->whereNotNull('marriage_date')
            ->where('is_current', true)
            ->get();

        foreach ($marriages as $marriage) {
            $originalDate = Carbon::parse($marriage->marriage_date)->startOfDay();
            $occurrence = $this->calculateUpcomingOccurrence($originalDate, $today, $daysAhead);

            if ($occurrence) {
                $husbandName = $this->getFullName($marriage->husband) ?: 'Suami';
                $wifeName = $this->getFullName($marriage->wife) ?: 'Istri';

                $ordinalText = "Ulang Tahun Pernikahan ke-{$occurrence['ordinal']}";
                $title = "Ulang Tahun Pernikahan ke-{$occurrence['ordinal']} {$husbandName} & {$wifeName}";
                $daysText = $this->getRelativeDaysText($occurrence['days_until']);
                $description = "{$ordinalText} {$husbandName} & {$wifeName} ({$daysText})";

                $eventData = [
                    'id' => "marriage-{$marriage->id}",
                    'type' => 'marriage',
                    'title' => $title,
                    'member' => $marriage->husband,
                    'spouse' => $marriage->wife,
                    'date' => $occurrence['date'],
                    'original_date' => $originalDate,
                    'days_until' => $occurrence['days_until'],
                    'ordinal' => $occurrence['ordinal'],
                    'ordinal_text' => $ordinalText,
                    'description' => $description,
                ];

                $waMessage = $this->formatWhatsAppMessage($eventData, $tree);
                $eventData['whatsapp_message'] = $waMessage;
                $eventData['wa_share_url'] = 'https://wa.me/?text='.rawurlencode($waMessage);

                $events->push($eventData);
            }
        }

        // 3. Process Custom Family Events (Halal bi Halal, Haul Akbar, Arisan, etc.)
        $customEvents = $tree->relationLoaded('events')
            ? $tree->events
            : $tree->events()->get();

        foreach ($customEvents as $event) {
            $originalDate = Carbon::parse($event->event_date)->startOfDay();

            if ($event->is_recurring_yearly) {
                $occurrence = $this->calculateUpcomingOccurrence($originalDate, $today, $daysAhead);
                if ($occurrence) {
                    $targetDate = $occurrence['date'];
                    $daysUntil = $occurrence['days_until'];
                    $ordinal = $occurrence['ordinal'];
                } else {
                    continue;
                }
            } else {
                $targetDate = $originalDate;
                $daysUntil = (int) $today->diffInDays($targetDate, false);
                $ordinal = 1;
                if ($daysUntil < 0 || $daysUntil > $daysAhead) {
                    continue;
                }
            }

            $title = $event->title;
            $timeText = $event->start_time ? Carbon::parse($event->start_time)->format('H:i').($event->end_time ? ' - '.Carbon::parse($event->end_time)->format('H:i') : ' WIB') : null;
            $description = "{$event->type_label}: {$title}".($timeText ? " ({$timeText})" : '').($event->location_name ? " di {$event->location_name}" : '');

            $eventData = [
                'id' => "event-{$event->id}",
                'type' => 'event',
                'event_model' => $event,
                'custom_type' => $event->event_type,
                'custom_label' => $event->type_label,
                'custom_emoji' => $event->type_emoji,
                'title' => $title,
                'member' => null,
                'spouse' => null,
                'date' => $targetDate,
                'original_date' => $originalDate,
                'days_until' => $daysUntil,
                'ordinal' => $ordinal,
                'ordinal_text' => $event->type_label,
                'description' => $description,
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'location_name' => $event->location_name,
                'location_maps_url' => $event->location_maps_url,
                'contact_person' => $event->contact_person,
                'is_recurring_yearly' => (bool) $event->is_recurring_yearly,
            ];

            $waMessage = $this->formatWhatsAppMessage($eventData, $tree);
            $eventData['whatsapp_message'] = $waMessage;
            $eventData['wa_share_url'] = 'https://wa.me/?text='.rawurlencode($waMessage);

            $events->push($eventData);
        }

        return $events->sortBy('days_until')->values();
    }

    /**
     * Get all family events occurring in a specific calendar month and year.
     *
     * @return Collection<int, array{
     *     id: string,
     *     type: 'haul'|'birthday'|'marriage',
     *     title: string,
     *     member: Member|null,
     *     spouse: Member|null,
     *     date: Carbon,
     *     original_date: Carbon,
     *     days_until: int,
     *     ordinal: int,
     *     ordinal_text: string,
     *     description: string,
     *     wa_share_url: string,
     *     whatsapp_message: string,
     * }>
     */
    public function getMonthEvents(FamilyTree $tree, int $year, int $month): Collection
    {
        $today = Carbon::today();
        $events = collect();
        $daysInTargetMonth = Carbon::create($year, $month, 1)->daysInMonth;

        // 1. Process Members (Birthdays & Hauls)
        $members = $tree->relationLoaded('members')
            ? $tree->members->filter(fn ($m) => ! empty($m->birth_date) || ! empty($m->death_date))
            : $tree->members()
                ->where(function ($query) {
                    $query->whereNotNull('birth_date')
                        ->orWhereNotNull('death_date');
                })
                ->get();

        foreach ($members as $member) {
            $isDeceased = ($member->is_living === false)
                || ($member->is_alive === false)
                || ! empty($member->death_date);

            if ($isDeceased) {
                // Haul for deceased member
                if (! empty($member->death_date)) {
                    $originalDate = Carbon::parse($member->death_date)->startOfDay();

                    if ($originalDate->month === $month && $originalDate->year <= $year) {
                        $day = min($originalDate->day, $daysInTargetMonth);
                        $targetDate = Carbon::create($year, $month, $day)->startOfDay();
                        $ordinal = max(1, $year - $originalDate->year);
                        $daysUntil = (int) $today->diffInDays($targetDate, false);

                        $prefix = ($member->gender === 'female') ? 'Almarhumah' : 'Almarhum';
                        $fullName = $this->getFullName($member);
                        $displayName = preg_match('/almarhum/i', $fullName) ? $fullName : "{$prefix} {$fullName}";

                        $ordinalText = "Haul ke-{$ordinal}";
                        $title = "Haul ke-{$ordinal} {$displayName}";
                        $daysText = $this->getRelativeDaysText($daysUntil);
                        $description = "Peringatan {$ordinalText} {$displayName} ({$daysText})";

                        $eventData = [
                            'id' => "haul-{$member->id}",
                            'type' => 'haul',
                            'title' => $title,
                            'member' => $member,
                            'spouse' => null,
                            'date' => $targetDate,
                            'original_date' => $originalDate,
                            'days_until' => $daysUntil,
                            'ordinal' => $ordinal,
                            'ordinal_text' => $ordinalText,
                            'description' => $description,
                        ];

                        $waMessage = $this->formatWhatsAppMessage($eventData, $tree);
                        $eventData['whatsapp_message'] = $waMessage;
                        $eventData['wa_share_url'] = 'https://wa.me/?text='.rawurlencode($waMessage);

                        $events->push($eventData);
                    }
                }
            } else {
                // Birthday for living member
                if (! empty($member->birth_date) && empty($member->is_birth_date_unknown)) {
                    $originalDate = Carbon::parse($member->birth_date)->startOfDay();

                    if ($originalDate->month === $month && $originalDate->year <= $year) {
                        $day = min($originalDate->day, $daysInTargetMonth);
                        $targetDate = Carbon::create($year, $month, $day)->startOfDay();
                        $ordinal = max(1, $year - $originalDate->year);
                        $daysUntil = (int) $today->diffInDays($targetDate, false);

                        $fullName = $this->getFullName($member);
                        $ordinalText = "Ulang Tahun ke-{$ordinal}";
                        $title = "Ulang Tahun ke-{$ordinal} {$fullName}";
                        $daysText = $this->getRelativeDaysText($daysUntil);
                        $description = "{$ordinalText} {$fullName} ({$daysText})";

                        $eventData = [
                            'id' => "birthday-{$member->id}",
                            'type' => 'birthday',
                            'title' => $title,
                            'member' => $member,
                            'spouse' => null,
                            'date' => $targetDate,
                            'original_date' => $originalDate,
                            'days_until' => $daysUntil,
                            'ordinal' => $ordinal,
                            'ordinal_text' => $ordinalText,
                            'description' => $description,
                        ];

                        $waMessage = $this->formatWhatsAppMessage($eventData, $tree);
                        $eventData['whatsapp_message'] = $waMessage;
                        $eventData['wa_share_url'] = 'https://wa.me/?text='.rawurlencode($waMessage);

                        $events->push($eventData);
                    }
                }
            }
        }

        // 2. Process Marriages (Wedding Anniversaries)
        $memberIds = $tree->relationLoaded('members')
            ? $tree->members->pluck('id')
            : $tree->members()->pluck('id');

        $marriages = Marriage::with(['husband', 'wife'])
            ->where(function ($query) use ($memberIds) {
                $query->whereIn('husband_id', $memberIds)
                    ->orWhereIn('wife_id', $memberIds);
            })
            ->whereNotNull('marriage_date')
            ->where('is_current', true)
            ->get();

        foreach ($marriages as $marriage) {
            $originalDate = Carbon::parse($marriage->marriage_date)->startOfDay();

            if ($originalDate->month === $month && $originalDate->year <= $year) {
                $day = min($originalDate->day, $daysInTargetMonth);
                $targetDate = Carbon::create($year, $month, $day)->startOfDay();
                $ordinal = max(1, $year - $originalDate->year);
                $daysUntil = (int) $today->diffInDays($targetDate, false);

                $husbandName = $this->getFullName($marriage->husband) ?: 'Suami';
                $wifeName = $this->getFullName($marriage->wife) ?: 'Istri';

                $ordinalText = "Ulang Tahun Pernikahan ke-{$ordinal}";
                $title = "Ulang Tahun Pernikahan ke-{$ordinal} {$husbandName} & {$wifeName}";
                $daysText = $this->getRelativeDaysText($daysUntil);
                $description = "{$ordinalText} {$husbandName} & {$wifeName} ({$daysText})";

                $eventData = [
                    'id' => "marriage-{$marriage->id}",
                    'type' => 'marriage',
                    'title' => $title,
                    'member' => $marriage->husband,
                    'spouse' => $marriage->wife,
                    'date' => $targetDate,
                    'original_date' => $originalDate,
                    'days_until' => $daysUntil,
                    'ordinal' => $ordinal,
                    'ordinal_text' => $ordinalText,
                    'description' => $description,
                ];

                $waMessage = $this->formatWhatsAppMessage($eventData, $tree);
                $eventData['whatsapp_message'] = $waMessage;
                $eventData['wa_share_url'] = 'https://wa.me/?text='.rawurlencode($waMessage);

                $events->push($eventData);
            }
        }

        // 3. Process Custom Family Events
        $customEvents = $tree->relationLoaded('events')
            ? $tree->events
            : $tree->events()->get();

        foreach ($customEvents as $event) {
            $originalDate = Carbon::parse($event->event_date)->startOfDay();
            $matches = false;
            $targetDate = null;

            if ($event->is_recurring_yearly) {
                if ($originalDate->month === $month && $originalDate->year <= $year) {
                    $day = min($originalDate->day, $daysInTargetMonth);
                    $targetDate = Carbon::create($year, $month, $day)->startOfDay();
                    $matches = true;
                }
            } else {
                if ($originalDate->year === $year && $originalDate->month === $month) {
                    $targetDate = $originalDate;
                    $matches = true;
                }
            }

            if ($matches && $targetDate) {
                $daysUntil = (int) $today->diffInDays($targetDate, false);
                $ordinal = max(1, $year - $originalDate->year);
                $title = $event->title;
                $timeText = $event->start_time ? Carbon::parse($event->start_time)->format('H:i').($event->end_time ? ' - '.Carbon::parse($event->end_time)->format('H:i') : ' WIB') : null;
                $description = "{$event->type_label}: {$title}".($timeText ? " ({$timeText})" : '').($event->location_name ? " di {$event->location_name}" : '');

                $eventData = [
                    'id' => "event-{$event->id}",
                    'type' => 'event',
                    'event_model' => $event,
                    'custom_type' => $event->event_type,
                    'custom_label' => $event->type_label,
                    'custom_emoji' => $event->type_emoji,
                    'title' => $title,
                    'member' => null,
                    'spouse' => null,
                    'date' => $targetDate,
                    'original_date' => $originalDate,
                    'days_until' => $daysUntil,
                    'ordinal' => $ordinal,
                    'ordinal_text' => $event->type_label,
                    'description' => $description,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'location_name' => $event->location_name,
                    'location_maps_url' => $event->location_maps_url,
                    'contact_person' => $event->contact_person,
                    'is_recurring_yearly' => (bool) $event->is_recurring_yearly,
                ];

                $waMessage = $this->formatWhatsAppMessage($eventData, $tree);
                $eventData['whatsapp_message'] = $waMessage;
                $eventData['wa_share_url'] = 'https://wa.me/?text='.rawurlencode($waMessage);

                $events->push($eventData);
            }
        }

        return $events->sortBy(fn ($e) => $e['date']->timestamp)->values();
    }

    /**
     * Alias for getUpcomingEvents for backwards/plan compatibility.
     *
     * @return Collection<int, array{
     *     id: string,
     *     type: 'haul'|'birthday'|'marriage',
     *     title: string,
     *     member: Member|null,
     *     spouse: Member|null,
     *     date: Carbon,
     *     original_date: Carbon,
     *     days_until: int,
     *     ordinal: int,
     *     ordinal_text: string,
     *     description: string,
     *     wa_share_url: string,
     *     whatsapp_message: string,
     * }>
     */
    public function getUpcomingReminders(FamilyTree $tree, int $daysAhead = 30): Collection
    {
        return $this->getUpcomingEvents($tree, $daysAhead);
    }

    /**
     * Format a polite, culturally appropriate WhatsApp broadcast message.
     *
     * @param  array{
     *     id: string,
     *     type: 'haul'|'birthday'|'marriage',
     *     title: string,
     *     member: Member|null,
     *     spouse: Member|null,
     *     date: Carbon,
     *     original_date: Carbon,
     *     days_until: int,
     *     ordinal: int,
     *     ordinal_text: string,
     *     description: string,
     * }  $event
     */
    public function formatWhatsAppMessage(array $event, FamilyTree $tree): string
    {
        $daysText = $this->getRelativeDaysText($event['days_until']);
        $dateFormatted = Carbon::parse($event['date'])->locale('id')->translatedFormat('d F Y');

        try {
            $treeUrl = route('tree.show', ['tree' => $tree->slug ?: $tree->id]);
        } catch (\Throwable) {
            $treeUrl = url('/tree/'.($tree->slug ?: $tree->id));
        }

        switch ($event['type']) {
            case 'haul':
                $prefix = ($event['member']?->gender === 'female') ? 'Almarhumah' : 'Almarhum';
                $name = $this->getFullName($event['member']);
                $displayName = preg_match('/almarhum/i', $name) ? $name : "{$prefix} {$name}";

                return "Assalamu'alaikum wr. wb.\n\n"
                    ."Mengingatkan keluarga besar Trah {$tree->name}, insyaAllah {$daysText} ({$dateFormatted}) adalah {$event['ordinal_text']} {$displayName}.\n\n"
                    ."Mari kita luangkan waktu sejenak untuk membacakan Surat Al-Fatihah dan memanjatkan doa terbaik untuk beliau:\n"
                    ."Semoga Allah SWT mengampuni segala khilafnya, menerima seluruh amal ibadahnya, melapangkan kuburnya, dan menempatkan beliau di tempat termulia di sisi-Nya. Aamiin ya Rabbal 'Alamin.\n\n"
                    ."Al-Fatihah... 🤲\n\n"
                    ."Bagan Silsilah: {$treeUrl}";

            case 'birthday':
                $name = $this->getFullName($event['member']);

                return "Assalamu'alaikum wr. wb.\n\n"
                    ."Mengingatkan keluarga besar Trah {$tree->name}, insyaAllah {$daysText} ({$dateFormatted}) adalah {$event['ordinal_text']} {$name}.\n\n"
                    ."Barakallahu fii umrik. Semoga senantiasa diberikan umur yang berkah, kesehatan, kebahagiaan, kemudahan rizki, dan selalu dalam lindungan Allah SWT. Aamiin ya Rabbal 'Alamin. 🤲🎂\n\n"
                    ."Bagan Silsilah: {$treeUrl}";

            case 'marriage':
                $husband = $this->getFullName($event['member']) ?: 'Suami';
                $wife = $this->getFullName($event['spouse']) ?: 'Istri';

                return "Assalamu'alaikum wr. wb.\n\n"
                    ."Mengingatkan keluarga besar Trah {$tree->name}, insyaAllah {$daysText} ({$dateFormatted}) adalah {$event['ordinal_text']} ({$husband} & {$wife}).\n\n"
                    ."Barakallahu laka wa baraka 'alaika wa jama'a bainakuma fii khoir. Semoga menjadi keluarga yang senantiasa sakinah, mawaddah, warahmah, selalu rukun, bahagia, dan penuh berkah hingga akhir hayat. Aamiin ya Rabbal 'Alamin. 🤲💐\n\n"
                    ."Bagan Silsilah: {$treeUrl}";

            case 'event':
                $dayName = Carbon::parse($event['date'])->locale('id')->translatedFormat('l');
                $timeStr = ! empty($event['start_time'])
                    ? Carbon::parse($event['start_time'])->format('H:i').(! empty($event['end_time']) ? ' - '.Carbon::parse($event['end_time'])->format('H:i') : '').' WIB'
                    : 'Waktu menyusul';
                $location = ! empty($event['location_name']) ? $event['location_name'] : '-';
                $maps = ! empty($event['location_maps_url']) ? "\n📍 Google Maps: {$event['location_maps_url']}" : '';
                $pic = ! empty($event['contact_person']) ? "\n📞 Narahubung / PIC: {$event['contact_person']}" : '';
                $desc = ! empty($event['event_model']?->description) ? "\n\nCatatan:\n".$event['event_model']->description : '';

                return "Assalamu'alaikum wr. wb.\n\n"
                    ."Undangan Agenda Keluarga Besar Trah {$tree->name}:\n\n"
                    ."*{$event['title']}*\n"
                    ."({$event['ordinal_text']})\n\n"
                    ."🗓 Hari / Tgl: {$dayName}, {$dateFormatted}\n"
                    ."⏰ Waktu: {$timeStr}\n"
                    ."🏠 Tempat: {$location}{$maps}{$pic}{$desc}\n\n"
                    ."Kehadiran dan doa restu keluarga besar sangat kami harapkan demi mempererat tali silaturahmi & ukhuwah kekeluargaan.\n\n"
                    ."Wassalamu'alaikum wr. wb.\n\n"
                    ."Bagan Silsilah: {$treeUrl}";

            default:
                return "Assalamu'alaikum wr. wb.\n\n"
                    ."Mengingatkan agenda keluarga besar Trah {$tree->name}: {$event['title']} pada {$daysText} ({$dateFormatted}).\n\n"
                    ."Bagan Silsilah: {$treeUrl}";
        }
    }

    /**
     * Generate an RFC 5545 compliant iCalendar (.ics) feed string.
     *
     * @param  Collection<int, array<string, mixed>>|null  $events
     */
    public function generateIcsFeed(FamilyTree $tree, ?Collection $events = null): string
    {
        $events = $events ?? $this->getUpcomingEvents($tree, 366);

        $treeName = $this->escapeIcsText($tree->name);
        $dtstamp = now()->utc()->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Silsilah Family Tree//Kalender Keluarga//ID',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            "X-WR-CALNAME:Agenda Trah {$treeName}",
            'X-WR-TIMEZONE:Asia/Jakarta',
        ];

        foreach ($events as $event) {
            $uid = ($event['id'] ?? uniqid('event-')).'@silsilah.family';
            $summary = $this->escapeIcsText($event['title'] ?? 'Agenda Keluarga');
            $description = $this->escapeIcsText($event['description'] ?? '');

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = "UID:{$uid}";
            $lines[] = "DTSTAMP:{$dtstamp}";

            if (! empty($event['start_time'])) {
                $dateStr = Carbon::parse($event['date'])->format('Ymd');
                $timeStr = Carbon::parse($event['start_time'])->format('His');
                $lines[] = "DTSTART;TZID=Asia/Jakarta:{$dateStr}T{$timeStr}";
                if (! empty($event['end_time'])) {
                    $endTimeStr = Carbon::parse($event['end_time'])->format('His');
                    $lines[] = "DTEND;TZID=Asia/Jakarta:{$dateStr}T{$endTimeStr}";
                }
            } else {
                $dtstart = Carbon::parse($event['date'])->format('Ymd');
                $lines[] = "DTSTART;VALUE=DATE:{$dtstart}";
            }

            if (($event['type'] ?? '') !== 'event' || ! empty($event['is_recurring_yearly'])) {
                $lines[] = 'RRULE:FREQ=YEARLY';
            }

            if (! empty($event['location_name'])) {
                $lines[] = 'LOCATION:'.$this->escapeIcsText($event['location_name']);
            }

            $lines[] = "SUMMARY:{$summary}";
            $lines[] = "DESCRIPTION:{$description}";
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * Calculate upcoming anniversary occurrence.
     *
     * @return array{date: Carbon, days_until: int, ordinal: int}|null
     */
    protected function calculateUpcomingOccurrence(?Carbon $originalDate, Carbon $today, int $daysAhead): ?array
    {
        if (! $originalDate) {
            return null;
        }

        $targetYear = $today->year;
        $daysInTargetMonth = Carbon::create($targetYear, $originalDate->month, 1)->daysInMonth;
        $safeDay = min($originalDate->day, $daysInTargetMonth);
        $occurrenceThisYear = Carbon::create($targetYear, $originalDate->month, $safeDay)->startOfDay();

        if ($occurrenceThisYear->lt($today)) {
            // Occurrence in current year has passed, check next year
            $targetYear = $today->year + 1;
            $daysInNextYearMonth = Carbon::create($targetYear, $originalDate->month, 1)->daysInMonth;
            $safeNextDay = min($originalDate->day, $daysInNextYearMonth);
            $targetDate = Carbon::create($targetYear, $originalDate->month, $safeNextDay)->startOfDay();
        } else {
            $targetDate = $occurrenceThisYear;
        }

        $daysUntil = (int) $today->diffInDays($targetDate, false);

        if ($daysUntil < 0 || $daysUntil > $daysAhead) {
            return null;
        }

        $ordinal = max(1, $targetDate->year - $originalDate->year);

        return [
            'date' => $targetDate,
            'days_until' => $daysUntil,
            'ordinal' => $ordinal,
        ];
    }

    protected function getFullName(?Member $member): string
    {
        if (! $member) {
            return '';
        }

        return trim($member->first_name.' '.($member->last_name ?? ''));
    }

    protected function getRelativeDaysText(int $daysUntil): string
    {
        if ($daysUntil === 0) {
            return 'hari ini';
        }

        if ($daysUntil === 1) {
            return 'besok';
        }

        if ($daysUntil === 2) {
            return 'lusa';
        }

        if ($daysUntil < 0) {
            $abs = abs($daysUntil);

            return "{$abs} hari yang lalu";
        }

        return "{$daysUntil} hari lagi";
    }

    protected function escapeIcsText(string $text): string
    {
        $text = str_replace(['\\', ';', ','], ['\\\\', '\;', '\,'], $text);

        return str_replace(["\r\n", "\n", "\r"], '\n', $text);
    }
}
