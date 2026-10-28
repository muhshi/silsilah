<?php

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
        ];
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
</div>
