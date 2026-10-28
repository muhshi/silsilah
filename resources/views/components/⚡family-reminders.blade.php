<?php

use App\Models\FamilyTree;
use App\Services\FamilyReminderService;
use Carbon\Carbon;
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

    public bool $showModal = false;

    public bool $asModal = true;

    public function mount($treeId = null, bool $asModal = true, int $daysAhead = 30, string $filterType = 'all')
    {
        $this->treeId = $treeId;
        $this->asModal = $asModal;
        $this->daysAhead = $daysAhead;
        $this->filterType = $filterType;
    }

    #[On('open-family-reminders')]
    public function openModal()
    {
        $this->showModal = true;
    }

    public function setFilter(string $type)
    {
        $this->filterType = $type;
    }

    public function setDaysAhead(int $days)
    {
        $this->daysAhead = $days;
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

    #[Computed]
    public function calendarFeedUrl(): string
    {
        if (! $this->tree) {
            return '#';
        }

        try {
            return route('tree.calendar.ics', $this->tree->slug ?: $this->tree->id);
        } catch (\Throwable) {
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
        <flux:modal wire:model="showModal" class="md:w-[48rem] max-w-3xl">
            @include('components.family-reminders-content')
        </flux:modal>
    @else
        @include('components.family-reminders-content')
    @endif
</div>
