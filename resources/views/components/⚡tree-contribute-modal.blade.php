<?php

use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\TreeSuggestion;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int|string|null $treeId = null;

    public bool $showModal = false;

    public ?int $targetMemberId = null;

    public string $suggestionType = 'add_child'; // 'add_child', 'add_spouse', 'edit_member'

    public string $firstName = '';

    public string $lastName = '';

    public string $gender = 'male';

    public ?string $birthDate = null;

    public bool $isLiving = true;

    public ?string $deathDate = null;

    public ?string $birthPlace = null;

    public ?string $deathPlace = null;

    public string $contributorName = '';

    public ?string $contributorContact = null;

    public ?string $notes = null;

    public string $searchMember = '';

    public function mount($treeId = null)
    {
        $this->treeId = $treeId;
    }

    #[On('open-contribute-modal')]
    public function openModal(?int $targetMemberId = null, string $type = 'add_child')
    {
        if ($targetMemberId) {
            $this->targetMemberId = $targetMemberId;
        }
        $this->suggestionType = $type;

        if ($type === 'edit_member' && $this->targetMemberId) {
            $this->loadTargetMemberData();
        }

        $this->showModal = true;
    }

    public function updatedTargetMemberId($value)
    {
        if ($this->suggestionType === 'edit_member' && $value) {
            $this->loadTargetMemberData();
        }
    }

    public function updatedSuggestionType($type)
    {
        if ($type === 'edit_member' && $this->targetMemberId && empty($this->firstName)) {
            $this->loadTargetMemberData();
        }
    }

    protected function loadTargetMemberData(): void
    {
        if (! $this->treeId || ! $this->targetMemberId) {
            return;
        }

        $target = Member::where('family_tree_id', $this->treeId)->find($this->targetMemberId);
        if ($target) {
            $this->firstName = $target->first_name;
            $this->lastName = $target->last_name ?? '';
            $this->gender = $target->gender;
            $this->birthDate = $target->birth_date?->format('Y-m-d');
            $this->isLiving = (bool) $target->is_living;
            $this->deathDate = $target->death_date?->format('Y-m-d');
            $this->birthPlace = $target->birth_place ?? '';
            $this->deathPlace = $target->death_place ?? '';
        }
    }

    protected function rules(): array
    {
        return [
            'targetMemberId' => [
                'required',
                \Illuminate\Validation\Rule::exists('members', 'id')->where('family_tree_id', $this->treeId),
            ],
            'suggestionType' => ['required', 'in:add_child,add_spouse,edit_member'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'birthDate' => ['nullable', 'date'],
            'isLiving' => ['boolean'],
            'deathDate' => ['nullable', 'date'],
            'birthPlace' => ['nullable', 'string', 'max:255'],
            'deathPlace' => ['nullable', 'string', 'max:255'],
            'contributorName' => ['required', 'string', 'max:255'],
            'contributorContact' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'targetMemberId.required' => 'Pilih anggota keluarga rujukan terlebih dahulu.',
            'targetMemberId.exists' => 'Anggota keluarga rujukan tidak ditemukan.',
            'suggestionType.required' => 'Pilih jenis usulan perubahan data.',
            'firstName.required' => 'Nama depan wajib diisi.',
            'gender.required' => 'Pilih jenis kelamin.',
            'contributorName.required' => 'Nama pengusul (nama Anda) wajib diisi.',
        ];
    }

    public function submitSuggestion()
    {
        $this->validate();

        TreeSuggestion::create([
            'family_tree_id' => $this->treeId,
            'target_member_id' => $this->targetMemberId,
            'type' => $this->suggestionType,
            'data' => [
                'first_name' => trim($this->firstName),
                'last_name' => trim($this->lastName),
                'gender' => $this->gender,
                'birth_date' => $this->birthDate ?: null,
                'is_living' => (bool) $this->isLiving,
                'death_date' => $this->deathDate ?: null,
                'birth_place' => $this->birthPlace ? trim($this->birthPlace) : null,
                'death_place' => $this->deathPlace ? trim($this->deathPlace) : null,
            ],
            'contributor_name' => trim($this->contributorName),
            'contributor_contact' => $this->contributorContact ? trim($this->contributorContact) : null,
            'notes' => $this->notes ? trim($this->notes) : null,
            'status' => 'pending',
        ]);

        $this->reset([
            'firstName',
            'lastName',
            'birthDate',
            'deathDate',
            'birthPlace',
            'deathPlace',
            'notes',
            'contributorContact',
        ]);
        $this->isLiving = true;
        $this->showModal = false;

        session()->flash('success_suggestion', 'Terima kasih! Usulan data telah dikirimkan ke pengelola silsilah untuk ditinjau.');

        if (class_exists(Flux::class)) {
            Flux::toast(text: 'Terima kasih! Usulan data telah dikirimkan ke pengelola silsilah untuk ditinjau.', variant: 'success');
        }

        $this->dispatch('suggestion-submitted');
    }

    #[Computed]
    public function members()
    {
        if (! $this->treeId) {
            return collect();
        }

        return Member::where('family_tree_id', $this->treeId)
            ->select(['id', 'first_name', 'last_name', 'gender', 'family_tree_id'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function filteredMembers()
    {
        if (empty($this->searchMember)) {
            return $this->members;
        }

        $term = strtolower(trim($this->searchMember));

        return $this->members->filter(fn ($m) =>
            $m->id == $this->targetMemberId ||
            str_contains(strtolower("{$m->first_name} {$m->last_name}"), $term) ||
            str_contains((string) $m->id, $term)
        );
    }
};
?>

<div>
    <flux:modal wire:model="showModal" class="md:w-[42rem] max-w-2xl">
        <form wire:submit="submitSuggestion" class="space-y-6">
            {{-- Header --}}
            <div class="pb-2 border-b border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400">
                        <flux:icon name="pencil-square" class="size-5" />
                    </span>
                    <div>
                        <flux:heading size="lg">Usulkan Perubahan / Tambah Data</flux:heading>
                        <flux:subheading>Bantu lengkapi silsilah keluarga. Usulan Anda akan diverifikasi oleh pengelola silsilah.</flux:subheading>
                    </div>
                </div>
            </div>

            @if(session()->has('success_suggestion'))
                <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl text-xs flex items-center gap-2">
                    <flux:icon name="check-circle" class="size-4 shrink-0" />
                    <span>{{ session('success_suggestion') }}</span>
                </div>
            @endif

            {{-- 1. Pilihan Jenis Usulan --}}
            <div class="space-y-2">
                <flux:label>Jenis Usulan Data</flux:label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button"
                            wire:click="$set('suggestionType', 'add_child')"
                            class="p-2.5 rounded-xl border text-xs font-semibold text-center transition-all cursor-pointer flex flex-col items-center gap-1 {{ $suggestionType === 'add_child' ? 'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-600 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-600/20' : 'border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}">
                        <span class="text-base">👶</span>
                        <span>Tambah Anak</span>
                    </button>
                    <button type="button"
                            wire:click="$set('suggestionType', 'add_spouse')"
                            class="p-2.5 rounded-xl border text-xs font-semibold text-center transition-all cursor-pointer flex flex-col items-center gap-1 {{ $suggestionType === 'add_spouse' ? 'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-600 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-600/20' : 'border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}">
                        <span class="text-base">💍</span>
                        <span>Tambah Pasangan</span>
                    </button>
                    <button type="button"
                            wire:click="$set('suggestionType', 'edit_member')"
                            class="p-2.5 rounded-xl border text-xs font-semibold text-center transition-all cursor-pointer flex flex-col items-center gap-1 {{ $suggestionType === 'edit_member' ? 'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-600 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-600/20' : 'border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}">
                        <span class="text-base">✏️</span>
                        <span>Koreksi Anggota</span>
                    </button>
                </div>
            </div>

            {{-- 2. Anggota Keluarga Rujukan (Target) --}}
            <div class="space-y-1.5 p-3 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-800">
                <flux:label>
                    @if($suggestionType === 'add_child')
                        Orang Tua Rujukan (Ayah / Ibu) <span class="text-rose-500">*</span>
                    @elseif($suggestionType === 'add_spouse')
                        Pasangan Dari (Suami / Istri) <span class="text-rose-500">*</span>
                    @else
                        Anggota yang Hendak Dikoreksi / Dilengkapi <span class="text-rose-500">*</span>
                    @endif
                </flux:label>

                <flux:input wire:model.live.debounce.250ms="searchMember" placeholder="Cari nama anggota rujukan..." size="sm" icon="magnifying-glass" clearable />

                <flux:select wire:model.live="targetMemberId" placeholder="-- Pilih Anggota Keluarga --">
                    <flux:select.option value="">-- Pilih Anggota Keluarga --</flux:select.option>
                    @foreach($this->filteredMembers as $m)
                        <flux:select.option value="{{ $m->id }}">
                            #M{{ $m->id }} - {{ trim($m->first_name . ' ' . $m->last_name) }} ({{ $m->gender === 'male' ? 'Laki-laki' : 'Perempuan' }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('targetMemberId') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            {{-- 3. Detail Data yang Diusulkan --}}
            <div class="space-y-3">
                <div class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    {{ $suggestionType === 'edit_member' ? 'Koreksi Data Anggota' : 'Identitas Anggota Baru' }}
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <flux:input wire:model="firstName" label="Nama Depan / Lengkap" placeholder="Contoh: Ahmad" required />
                    </div>
                    <div>
                        <flux:input wire:model="lastName" label="Nama Belakang / Panggilan" placeholder="Contoh: Dahlan" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                    <div>
                        <flux:label>Jenis Kelamin</flux:label>
                        <div class="flex items-center gap-4 mt-1.5">
                            <label class="flex items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                <input type="radio" wire:model="gender" value="male" class="text-emerald-600 focus:ring-emerald-500">
                                <span>Laki-laki</span>
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                <input type="radio" wire:model="gender" value="female" class="text-emerald-600 focus:ring-emerald-500">
                                <span>Perempuan</span>
                            </label>
                        </div>
                        @error('gender') <flux:error>{{ $message }}</flux:error> @enderror
                    </div>

                    <div>
                        <flux:checkbox wire:model.live="isLiving" label="Masih Hidup" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <flux:input type="date" wire:model="birthDate" label="Tanggal Lahir" />
                    </div>
                    <div>
                        <flux:input wire:model="birthPlace" label="Tempat Lahir" placeholder="Contoh: Semarang" />
                    </div>
                </div>

                @if(!$isLiving)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-800">
                        <div>
                            <flux:input type="date" wire:model="deathDate" label="Tanggal Wafat" />
                        </div>
                        <div>
                            <flux:input wire:model="deathPlace" label="Tempat Pemakaman / Wafat" placeholder="Contoh: Makam Giri" />
                        </div>
                    </div>
                @endif
            </div>

            {{-- 4. Informasi Pengusul (Kerabat) --}}
            <div class="space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <div class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Informasi Pengusul (Kerabat)
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <flux:input wire:model="contributorName" label="Nama Anda & Hubungan" placeholder="Contoh: Budi (Cucu Alm. Simbah)" required />
                    </div>
                    <div>
                        <flux:input wire:model="contributorContact" label="No. WhatsApp / Kontak" placeholder="Contoh: 08123456789" />
                    </div>
                </div>

                <div>
                    <flux:textarea wire:model="notes" label="Catatan Tambahan (Opsional)" placeholder="Tambahkan informasi penting, sumber data, atau catatan silsilah lainnya..." rows="2" />
                </div>
            </div>

            {{-- Footer Buttons --}}
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" wire:click="$set('showModal', false)">
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary" icon="paper-airplane" class="!bg-emerald-600 hover:!bg-emerald-700 !text-white">
                    Kirim Usulan
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
