<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Services\RelationshipService;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public $treeId;

    public $personAId = null;

    public $personBId = null;

    public $result = null;

    public $showModal = false;

    public $searchA = '';

    public $searchB = '';

    public function mount($treeId)
    {
        $this->treeId = $treeId;
    }

    #[On('open-relationship-calculator')]
    public function openModal($memberA = null, $memberB = null)
    {
        if ($memberA !== null) {
            $this->personAId = $memberA;
        }
        if ($memberB !== null) {
            $this->personBId = $memberB;
        }

        $this->showModal = true;

        if ($this->personAId && $this->personBId) {
            $this->calculate();
        }
    }

    public function calculate()
    {
        $this->validate([
            'personAId' => 'required',
            'personBId' => 'required',
        ], [
            'personAId.required' => 'Pilih anggota pertama terlebih dahulu.',
            'personBId.required' => 'Pilih anggota kedua terlebih dahulu.',
        ]);

        $personA = Member::find($this->personAId);
        $personB = Member::find($this->personBId);

        if (! $personA || ! $personB) {
            $this->result = null;

            return;
        }

        $this->result = app(RelationshipService::class)->calculate($personA, $personB);
    }

    public function swapPersons()
    {
        $temp = $this->personAId;
        $this->personAId = $this->personBId;
        $this->personBId = $temp;

        if ($this->personAId && $this->personBId) {
            $this->calculate();
        } else {
            $this->result = null;
        }
    }

    public function getWhatsAppShareUrl(): string
    {
        if (! $this->result || ! $this->personAId || ! $this->personBId) {
            return '#';
        }

        $personA = Member::find($this->personAId);
        $personB = Member::find($this->personBId);

        if (! $personA || ! $personB) {
            return '#';
        }

        $tree = FamilyTree::find($this->treeId);
        $treeUrl = $tree
            ? ($tree->is_public && $tree->slug ? route('tree.public', $tree->slug) : route('tree.show', $tree))
            : url('/');

        $text = "Halo keluarga! Tahukah kamu? Hubungan antara *{$personA->first_name}* dan *{$personB->first_name}* di silsilah keluarga adalah *{$this->result['title']}* ({$this->result['formal_title']}).\n\nPenjelasan: {$this->result['description']}\n\nCek silsilah lengkap di: {$treeUrl}";

        return 'https://wa.me/?text='.rawurlencode($text);
    }

    public function getMembersProperty()
    {
        if (! $this->treeId) {
            return collect();
        }

        return Member::where('family_tree_id', $this->treeId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function getMembersAProperty()
    {
        if (empty($this->searchA)) {
            return $this->members;
        }

        $term = strtolower(trim($this->searchA));

        return $this->members->filter(fn ($m) =>
            $m->id == $this->personAId ||
            str_contains(strtolower("{$m->first_name} {$m->last_name}"), $term) ||
            str_contains((string) $m->id, $term)
        );
    }

    public function getMembersBProperty()
    {
        if (empty($this->searchB)) {
            return $this->members;
        }

        $term = strtolower(trim($this->searchB));

        return $this->members->filter(fn ($m) =>
            $m->id == $this->personBId ||
            str_contains(strtolower("{$m->first_name} {$m->last_name}"), $term) ||
            str_contains((string) $m->id, $term)
        );
    }
};
?>

<div>
    <flux:modal wire:model="showModal" class="md:w-[42rem] max-w-2xl">
        <div class="space-y-6">
            {{-- Modal Heading --}}
            <div class="pb-2 border-b border-zinc-200 dark:border-zinc-800">
                <flux:heading size="lg">Cek Hubungan Kekerabatan / Nasab</flux:heading>
                <flux:subheading>Cari tahu sebutan kekerabatan dan sapaan adat antara dua anggota keluarga.</flux:subheading>
            </div>

            {{-- Selectors --}}
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-[1fr,auto,1fr] gap-3 items-end">
                    <div class="space-y-1.5">
                        <flux:label>Anggota Pertama (Subjek)</flux:label>
                        <flux:input wire:model.live.debounce.250ms="searchA" placeholder="Filter nama..." size="sm" icon="magnifying-glass" clearable />
                        <flux:select wire:model="personAId" placeholder="-- Pilih Anggota A --">
                            <flux:select.option value="">-- Pilih Anggota A --</flux:select.option>
                            @foreach($this->membersA as $m)
                                <flux:select.option value="{{ $m->id }}">
                                    #M{{ $m->id }} - {{ trim($m->first_name . ' ' . $m->last_name) }} ({{ $m->gender === 'male' ? 'Laki-laki' : 'Perempuan' }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('personAId') <flux:error>{{ $message }}</flux:error> @enderror
                    </div>

                    <div class="flex justify-center pb-1">
                        <flux:button type="button" wire:click="swapPersons" icon="arrows-right-left" size="sm" class="!rounded-full hover:rotate-180 transition-transform duration-300" title="Tukar Posisi" />
                    </div>

                    <div class="space-y-1.5">
                        <flux:label>Anggota Kedua (Target)</flux:label>
                        <flux:input wire:model.live.debounce.250ms="searchB" placeholder="Filter nama..." size="sm" icon="magnifying-glass" clearable />
                        <flux:select wire:model="personBId" placeholder="-- Pilih Anggota B --">
                            <flux:select.option value="">-- Pilih Anggota B --</flux:select.option>
                            @foreach($this->membersB as $m)
                                <flux:select.option value="{{ $m->id }}">
                                    #M{{ $m->id }} - {{ trim($m->first_name . ' ' . $m->last_name) }} ({{ $m->gender === 'male' ? 'Laki-laki' : 'Perempuan' }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('personBId') <flux:error>{{ $message }}</flux:error> @enderror
                    </div>
                </div>

                <div class="flex justify-end pt-1">
                    <flux:button type="button" wire:click="calculate" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 font-bold shadow-sm">
                        Hitung Hubungan
                    </flux:button>
                </div>
            </div>

            {{-- Result Card --}}
            @if($result)
                <div class="p-5 rounded-2xl bg-zinc-50 dark:bg-zinc-800/70 border border-zinc-200 dark:border-zinc-700/80 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Hasil Hubungan</span>
                            <h3 class="text-2xl font-black text-zinc-900 dark:text-white mt-0.5">{{ $result['title'] }}</h3>
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $result['formal_title'] }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if(!empty($result['cultural_term']))
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                    {{ $result['cultural_term'] }}
                                </span>
                            @endif
                            @if(!empty($result['lineage_side']))
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-700">
                                    {{ $result['lineage_side'] }}
                                </span>
                            @endif
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ ($result['is_blood'] ?? false) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300' }}">
                                {{ ($result['is_blood'] ?? false) ? 'Nasab (Darah)' : 'Bukan Nasab (Semenda)' }}
                            </span>
                        </div>
                    </div>

                    <div class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed border-t border-zinc-200 dark:border-zinc-700/70 pt-3">
                        {{ $result['description'] }}
                    </div>

                    @if(!empty($result['path']) && count($result['path']) > 1)
                        <div class="space-y-1.5 pt-1">
                            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Jalur Silsilah:</span>
                            <div class="flex items-center flex-wrap gap-1.5 p-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700/60 text-xs">
                                @foreach($result['path'] as $step)
                                    <span class="font-medium px-2 py-1 rounded-lg {{ $loop->first ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 font-bold' : ($loop->last ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 font-bold' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300') }}">
                                        {{ $step }}
                                    </span>
                                    @if(!$loop->last)
                                        <span class="text-zinc-400 dark:text-zinc-500 font-bold">&rarr;</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700/70">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">Derajat Kekerabatan: {{ $result['degree'] }} langkah</span>
                        <a href="{{ $this->getWhatsAppShareUrl() }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#25D366] hover:bg-[#20ba59] text-white text-xs font-bold transition-all shadow-sm">
                            <span class="text-sm leading-none">💬</span>
                            <span>Bagikan ke WhatsApp</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
