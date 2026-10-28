<?php

use App\Models\FamilyEvent;
use App\Models\FamilyTree;
use App\Services\FamilyReminderService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int|string|null $treeId = null;

    public int $daysAhead = 30;

    public string $filterType = 'all';

    public string $viewMode = 'calendar'; // 'calendar' or 'list'

    public int $currentYear;

    public int $currentMonth;

    public ?int $selectedDay = null;

    public bool $showModal = false;

    public bool $asModal = true;

    // Custom Family Event State
    public bool $showEventModal = false;

    public ?int $editingEventId = null;

    public string $eventTitle = '';

    public string $eventType = 'halal_bi_halal';

    public ?string $eventDate = null;

    public ?string $startTime = null;

    public ?string $endTime = null;

    public ?string $locationName = null;

    public ?string $locationMapsUrl = null;

    public ?string $eventDescription = null;

    public ?string $contactPerson = null;

    public bool $isRecurringYearly = false;

    public function mount($treeId = null, bool $asModal = true, int $daysAhead = 30, string $filterType = 'all', string $viewMode = 'calendar')
    {
        $this->treeId = $treeId;
        $this->asModal = $asModal;
        $this->daysAhead = $daysAhead;
        $this->filterType = $filterType;
        $this->viewMode = $viewMode;

        $now = Carbon::now();
        $this->currentYear = $now->year;
        $this->currentMonth = $now->month;
        $this->selectedDay = null;
    }

    #[On('open-family-reminders')]
    public function openModal()
    {
        $this->showModal = true;
    }

    public function setViewMode(string $mode)
    {
        if (in_array($mode, ['calendar', 'list'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function updatedDaysAhead($value)
    {
        $this->viewMode = 'list';
    }

    public function prevMonth()
    {
        $dt = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentYear = $dt->year;
        $this->currentMonth = $dt->month;
        $this->selectedDay = null;
    }

    public function nextMonth()
    {
        $dt = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentYear = $dt->year;
        $this->currentMonth = $dt->month;
        $this->selectedDay = null;
    }

    public function goToToday()
    {
        $now = Carbon::now();
        $this->currentYear = $now->year;
        $this->currentMonth = $now->month;
        $this->selectedDay = $now->day;
    }

    public function selectDay(?int $day)
    {
        $this->selectedDay = ($this->selectedDay === $day) ? null : $day;
    }

    public function setFilter(string $type)
    {
        $this->filterType = $type;
    }

    public function setDaysAhead(int $days)
    {
        $this->daysAhead = $days;
        $this->viewMode = 'list';
    }

    #[Computed]
    public function tree(): ?FamilyTree
    {
        if (! $this->treeId) {
            return null;
        }

        return is_numeric($this->treeId)
            ? FamilyTree::find($this->treeId)
            : FamilyTree::where('slug', $this->treeId)->first();
    }

    /**
     * Upcoming events for List view.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function allEvents(): Collection
    {
        if (! $this->tree) {
            return collect();
        }

        return app(FamilyReminderService::class)->getUpcomingEvents($this->tree, $this->daysAhead);
    }

    /**
     * Filtered events for List view.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function events(): Collection
    {
        if ($this->filterType === 'all') {
            return $this->allEvents;
        }

        return $this->allEvents->where('type', $this->filterType)->values();
    }

    /**
     * All events in the current active month for Calendar view.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function monthEvents(): Collection
    {
        if (! $this->tree) {
            return collect();
        }

        return app(FamilyReminderService::class)->getMonthEvents($this->tree, $this->currentYear, $this->currentMonth);
    }

    /**
     * Filtered events in the current active month for Calendar view.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function filteredMonthEvents(): Collection
    {
        if ($this->filterType === 'all') {
            return $this->monthEvents;
        }

        return $this->monthEvents->where('type', $this->filterType)->values();
    }

    /**
     * Grouped by day of month (1 to 31).
     *
     * @return Collection<int, Collection<int, array<string, mixed>>>
     */
    #[Computed]
    public function monthEventsByDay(): Collection
    {
        return $this->filteredMonthEvents->groupBy(fn ($e) => (int) $e['date']->day);
    }

    /**
     * Generate 2D array of weeks and days for the month grid.
     *
     * @return array<int, array<int, array{
     *     day: int,
     *     date: Carbon,
     *     is_current_month: bool,
     *     is_today: bool,
     *     events: Collection
     * }>>
     */
    #[Computed]
    public function calendarWeeks(): array
    {
        $firstOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfDay();
        $lastOfMonth = $firstOfMonth->copy()->endOfMonth()->startOfDay();
        $startOfCalendar = $firstOfMonth->copy()->startOfWeek(CarbonInterface::MONDAY);
        $endOfCalendar = $lastOfMonth->copy()->endOfWeek(CarbonInterface::SUNDAY);
        $today = Carbon::today();
        $eventsByDay = $this->monthEventsByDay;

        $weeks = [];
        $current = $startOfCalendar->copy();

        while ($current->lte($endOfCalendar)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $isCurrentMonth = ($current->month === $this->currentMonth);
                $dayNum = $current->day;
                $dayEvents = $isCurrentMonth ? ($eventsByDay->get($dayNum, collect())) : collect();

                $week[] = [
                    'day' => $dayNum,
                    'date' => $current->copy(),
                    'is_current_month' => $isCurrentMonth,
                    'is_today' => $current->isSameDay($today),
                    'events' => $dayEvents,
                ];
                $current->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * Events occurring on the selected day (or all month events if none selected).
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function selectedEvents(): Collection
    {
        if ($this->selectedDay !== null) {
            return $this->monthEventsByDay->get($this->selectedDay, collect());
        }

        return $this->filteredMonthEvents;
    }

    #[Computed]
    public function monthTitle(): string
    {
        return Carbon::create($this->currentYear, $this->currentMonth, 1)->locale('id')->translatedFormat('F Y');
    }

    #[Computed]
    public function monthCounts(): array
    {
        $all = $this->monthEvents;

        return [
            'all' => $all->count(),
            'haul' => $all->where('type', 'haul')->count(),
            'birthday' => $all->where('type', 'birthday')->count(),
            'marriage' => $all->where('type', 'marriage')->count(),
            'event' => $all->where('type', 'event')->count(),
        ];
    }

    #[Computed]
    public function calendarFeedUrl(): string
    {
        if (! $this->tree) {
            return '#';
        }

        try {
            return route('tree.calendar.ics', $this->tree->slug ?: $this->tree->id);
        } catch (Throwable) {
            return url("/tree/{$this->tree->id}/calendar.ics");
        }
    }

    #[Computed]
    public function webcalFeedUrl(): string
    {
        $url = $this->calendarFeedUrl;

        return preg_replace('/^https?:\/\//i', 'webcal://', $url) ?: $url;
    }

    #[Computed]
    public function googleCalendarUrl(): string
    {
        $webcal = $this->webcalFeedUrl;

        return 'https://calendar.google.com/calendar/render?cid='.urlencode($webcal);
    }

    #[Computed]
    public function downloadIcsUrl(): string
    {
        $url = $this->calendarFeedUrl;

        return $url.(str_contains($url, '?') ? '&' : '?').'download=1';
    }

    #[Computed]
    public function calendarPageUrl(): ?string
    {
        if (! $this->tree) {
            return null;
        }

        try {
            if ($this->tree->is_public) {
                return route('tree.public.calendar', $this->tree->slug ?: $this->tree->id);
            }

            return route('tree.calendar', $this->tree->slug ?: $this->tree->id);
        } catch (Throwable) {
            return null;
        }
    }

    #[Computed]
    public function counts(): array
    {
        $all = $this->allEvents;

        return [
            'all' => $all->count(),
            'haul' => $all->where('type', 'haul')->count(),
            'birthday' => $all->where('type', 'birthday')->count(),
            'marriage' => $all->where('type', 'marriage')->count(),
            'event' => $all->where('type', 'event')->count(),
        ];
    }

    #[Computed]
    public function canManageEvents(): bool
    {
        if (! auth()->check() || ! $this->tree) {
            return false;
        }

        $user = auth()->user();

        return $user->isAdmin()
            || $this->tree->user_id === $user->id
            || $this->tree->users()->where('users.id', $user->id)->exists();
    }

    public function openCreateEventModal(?string $defaultDate = null): void
    {
        if (! $this->canManageEvents) {
            return;
        }

        $this->resetEventForm();

        if ($defaultDate) {
            $this->eventDate = $defaultDate;
        } elseif ($this->selectedDay) {
            $this->eventDate = Carbon::create($this->currentYear, $this->currentMonth, $this->selectedDay)->format('Y-m-d');
        } else {
            $this->eventDate = Carbon::today()->format('Y-m-d');
        }

        $this->showEventModal = true;
    }

    public function editEvent(int $id): void
    {
        if (! $this->canManageEvents || ! $this->tree) {
            return;
        }

        $event = FamilyEvent::where('family_tree_id', $this->tree->id)->find($id);
        if (! $event) {
            return;
        }

        $this->editingEventId = $event->id;
        $this->eventTitle = $event->title;
        $this->eventType = $event->event_type;
        $this->eventDate = $event->event_date?->format('Y-m-d');
        $this->startTime = $event->start_time;
        $this->endTime = $event->end_time;
        $this->locationName = $event->location_name;
        $this->locationMapsUrl = $event->location_maps_url;
        $this->eventDescription = $event->description;
        $this->contactPerson = $event->contact_person;
        $this->isRecurringYearly = (bool) $event->is_recurring_yearly;

        $this->showEventModal = true;
    }

    public function saveEvent(): void
    {
        if (! $this->canManageEvents || ! $this->tree) {
            return;
        }

        $this->validate([
            'eventTitle' => 'required|string|max:150',
            'eventType' => 'required|string|in:halal_bi_halal,haul_akbar,arisan,pertemuan,ziarah,lainnya',
            'eventDate' => 'required|date',
            'startTime' => 'nullable|string|max:10',
            'endTime' => 'nullable|string|max:10',
            'locationName' => 'nullable|string|max:200',
            'locationMapsUrl' => 'nullable|url|max:255',
            'eventDescription' => 'nullable|string|max:1000',
            'contactPerson' => 'nullable|string|max:150',
            'isRecurringYearly' => 'boolean',
        ]);

        $mapsUrl = $this->locationMapsUrl ? trim($this->locationMapsUrl) : null;
        if ($mapsUrl && ! preg_match('/^https?:\/\//i', $mapsUrl)) {
            $mapsUrl = 'https://'.$mapsUrl;
        }

        $data = [
            'title' => trim($this->eventTitle),
            'event_type' => $this->eventType,
            'event_date' => $this->eventDate,
            'start_time' => $this->startTime ? trim($this->startTime) : null,
            'end_time' => $this->endTime ? trim($this->endTime) : null,
            'location_name' => $this->locationName ? trim($this->locationName) : null,
            'location_maps_url' => $mapsUrl,
            'description' => $this->eventDescription ? trim($this->eventDescription) : null,
            'contact_person' => $this->contactPerson ? trim($this->contactPerson) : null,
            'is_recurring_yearly' => (bool) $this->isRecurringYearly,
        ];

        if ($this->editingEventId) {
            $event = FamilyEvent::where('family_tree_id', $this->tree->id)->find($this->editingEventId);
            $event?->update($data);
        } else {
            $data['family_tree_id'] = $this->tree->id;
            $data['created_by'] = auth()->id();
            FamilyEvent::create($data);
        }

        $this->resetEventForm();
        $this->showEventModal = false;
    }

    public function deleteEvent(int $id): void
    {
        if (! $this->canManageEvents || ! $this->tree) {
            return;
        }

        FamilyEvent::where('family_tree_id', $this->tree->id)->where('id', $id)->delete();
    }

    public function resetEventForm(): void
    {
        $this->editingEventId = null;
        $this->eventTitle = '';
        $this->eventType = 'halal_bi_halal';
        $this->eventDate = null;
        $this->startTime = null;
        $this->endTime = null;
        $this->locationName = null;
        $this->locationMapsUrl = null;
        $this->eventDescription = null;
        $this->contactPerson = null;
        $this->isRecurringYearly = false;
        $this->resetValidation();
    }
};
?>

<div>
    @if($asModal)
        <flux:modal wire:model="showModal" class="md:w-[58rem] max-w-5xl">
            @include('components.family-reminders-content')
        </flux:modal>
    @else
        @include('components.family-reminders-content')
    @endif

    {{-- Modal Tambah / Edit Acara Keluarga --}}
    <flux:modal wire:model="showEventModal" class="md:w-[34rem] max-w-xl">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingEventId ? 'Edit Agenda Acara Keluarga' : 'Tambah Agenda Acara Keluarga' }}
                </flux:heading>
                <flux:subheading>
                    Atur acara kumpul trah keluarga seperti Halal bi Halal, Haul Akbar, atau Arisan.
                </flux:subheading>
            </div>

            <form wire:submit="saveEvent" class="space-y-4">
                <flux:field>
                    <flux:label>Nama / Judul Acara</flux:label>
                    <flux:input wire:model="eventTitle" placeholder="Misal: Halal bi Halal Idul Fitri Trah" required />
                    <flux:error name="eventTitle" />
                </flux:field>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Jenis Acara</flux:label>
                        <flux:select wire:model="eventType">
                            <flux:select.option value="halal_bi_halal">🤝 Halal bi Halal</flux:select.option>
                            <flux:select.option value="haul_akbar">🕌 Haul Akbar / Jam'i</flux:select.option>
                            <flux:select.option value="arisan">☕ Arisan Trah</flux:select.option>
                            <flux:select.option value="pertemuan">👥 Pertemuan Keluarga</flux:select.option>
                            <flux:select.option value="ziarah">🤲 Ziarah Bersama</flux:select.option>
                            <flux:select.option value="lainnya">📌 Acara Lainnya</flux:select.option>
                        </flux:select>
                        <flux:error name="eventType" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Tanggal Acara</flux:label>
                        <flux:input type="date" wire:model="eventDate" required />
                        <flux:error name="eventDate" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Jam Mulai (opsional)</flux:label>
                        <flux:input type="time" wire:model="startTime" />
                        <flux:error name="startTime" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Jam Selesai (opsional)</flux:label>
                        <flux:input type="time" wire:model="endTime" />
                        <flux:error name="endTime" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Nama Tempat / Lokasi</flux:label>
                    <flux:input wire:model="locationName" placeholder="Misal: Kediaman Bpk. H. Ahmad / Gedung Pertemuan" />
                    <flux:error name="locationName" />
                </flux:field>

                <flux:field>
                    <flux:label>Link Google Maps (opsional)</flux:label>
                    <flux:input type="url" wire:model="locationMapsUrl" placeholder="https://maps.app.goo.gl/..." />
                    <flux:error name="locationMapsUrl" />
                </flux:field>

                <flux:field>
                    <flux:label>Narahubung / PIC (opsional)</flux:label>
                    <flux:input wire:model="contactPerson" placeholder="Misal: Mas Budi (0812-3456-7890)" />
                    <flux:error name="contactPerson" />
                </flux:field>

                <flux:field>
                    <flux:label>Deskripsi / Catatan Tambahan (opsional)</flux:label>
                    <flux:textarea wire:model="eventDescription" placeholder="Catatan susunan acara, dresscode, konfirmasi kehadiran, dll..." rows="2" />
                    <flux:error name="eventDescription" />
                </flux:field>

                <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                    <flux:checkbox wire:model="isRecurringYearly" label="Ulangi agenda ini otomatis setiap tahun" description="Cocok untuk tradisi tahunan seperti ziarah menjelang Ramadhan." />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <flux:button type="button" wire:click="$set('showEventModal', false)" variant="subtle">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="!bg-emerald-600 hover:!bg-emerald-700 text-white font-bold">
                        {{ $editingEventId ? 'Simpan Perubahan' : 'Tambahkan Agenda' }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
