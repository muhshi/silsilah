<?php

use App\Models\ActivityLog;
use App\Models\AdminActivityLog;
use App\Models\FamilyTree;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $search = '';

    public $filter = 'all';

    public $selectedTreeId = null;

    public $selectedTree = null;

    public $overrideReason = 'cash_cod';

    public $overrideNotes = '';

    public $targetIsPremium = true;

    public $showOverrideModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function openOverrideModal(int $treeId): void
    {
        $this->selectedTreeId = $treeId;
        $this->selectedTree = FamilyTree::with(['users' => fn ($q) => $q->wherePivot('role', 'owner')])->findOrFail($treeId);
        $this->targetIsPremium = ! $this->selectedTree->is_premium;
        $this->overrideReason = 'cash_cod';
        $this->overrideNotes = '';
        $this->showOverrideModal = true;
    }

    public function closeOverrideModal(): void
    {
        $this->showOverrideModal = false;
        $this->selectedTree = null;
        $this->selectedTreeId = null;
        $this->overrideNotes = '';
    }

    public function saveOverride(bool $isPremium): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        $this->validate([
            'selectedTreeId' => 'required|exists:family_trees,id',
            'overrideReason' => 'required|string|in:cash_cod,bank_transfer,gateway_issue,special_promo,other',
            'overrideNotes' => 'nullable|string|max:200',
        ]);

        $tree = FamilyTree::findOrFail($this->selectedTreeId);

        $reasonMap = [
            'cash_cod' => 'Bayar Tunai / COD saat tatap muka',
            'bank_transfer' => 'Transfer Bank Manual',
            'gateway_issue' => 'Kendala Gateway QRIS',
            'special_promo' => 'Kompensasi / Promo Khusus',
            'other' => 'Lainnya',
        ];

        $reasonLabel = $reasonMap[$this->overrideReason] ?? $this->overrideReason;
        $notesText = trim($this->overrideNotes ?? '');
        $fullDescription = 'Super Admin '.auth()->user()->name.($isPremium ? ' mengaktifkan status Premium manual' : ' mengembalikan status Standar')." (Metode: {$reasonLabel}".($notesText ? " - {$notesText}" : '').')';

        \Illuminate\Support\Facades\DB::transaction(function () use ($tree, $isPremium, $fullDescription) {
            $tree->update(['is_premium' => $isPremium]);

            ActivityLog::log(
                $tree->id,
                'admin_override_premium',
                $fullDescription,
                [
                    'admin_id' => auth()->id(),
                    'admin_name' => auth()->user()->name,
                    'is_premium' => $isPremium,
                    'reason' => $this->overrideReason,
                    'notes' => $this->overrideNotes,
                ]
            );

            AdminActivityLog::log(
                'tree_premium_override',
                Str::limit($fullDescription, 245),
                'FamilyTree',
                $tree->id,
                [
                    'is_premium' => $isPremium,
                    'reason' => $this->overrideReason,
                    'notes' => $this->overrideNotes,
                ]
            );
        });

        $this->showOverrideModal = false;
        $this->selectedTree = null;
        $this->selectedTreeId = null;

        session()->flash('success', $isPremium ? "Pohon '{$tree->name}' berhasil diubah menjadi Premium!" : "Pohon '{$tree->name}' berhasil dikembalikan ke Standar.");
    }

    public function with(): array
    {
        $query = FamilyTree::with([
            'users' => fn ($q) => $q->wherePivot('role', 'owner'),
        ])->withCount('members')->latest();

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhereHas('users', function ($uq) use ($term) {
                        $uq->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
            });
        }

        if ($this->filter === 'free') {
            $query->where('is_premium', false);
        } elseif ($this->filter === 'premium') {
            $query->where('is_premium', true);
        } elseif ($this->filter === 'public') {
            $query->where('is_public', true);
        } elseif ($this->filter === 'private') {
            $query->where('is_public', false);
        }

        $trees = $query->paginate(15);

        $counts = [
            'all' => FamilyTree::count(),
            'free' => FamilyTree::where('is_premium', false)->count(),
            'premium' => FamilyTree::where('is_premium', true)->count(),
            'public' => FamilyTree::where('is_public', true)->count(),
            'private' => FamilyTree::where('is_public', false)->count(),
            'total_views' => (int) FamilyTree::sum('views_count'),
        ];

        return [
            'trees' => $trees,
            'counts' => $counts,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-200/80 dark:border-zinc-800/80">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Manajemen SaaS
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                Pohon Silsilah
            </h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola seluruh pohon silsilah keluarga, cek statistik kunjungan, dan override status premium manual.
            </p>
        </div>

        <!-- Search Bar -->
        <div class="w-full sm:w-80">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama pohon, slug, atau pemilik..." icon="magnifying-glass" clearable />
        </div>
    </div>

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2 shadow-xs">
            <span class="material-symbols-outlined text-emerald-600 text-lg">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- 4 High-Density Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Metric 1: Total Pohon -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-emerald-300 dark:hover:border-emerald-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Pohon</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center border border-emerald-200/50 dark:border-emerald-800/50 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">account_tree</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                {{ number_format($counts['all']) }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="text-zinc-500 dark:text-zinc-400">
                    Pohon silsilah terdaftar
                </span>
            </div>
        </div>

        <!-- Metric 2: Pohon Premium -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-amber-300 dark:hover:border-amber-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Pohon Premium</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 flex items-center justify-center border border-amber-200/50 dark:border-amber-800/50 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">workspace_premium</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                {{ number_format($counts['premium']) }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-1.5 py-0.5 rounded">
                    👑 Premium
                </span>
                <span class="text-zinc-400">&bull;</span>
                <span class="text-zinc-500 dark:text-zinc-400">
                    Anggota tanpa batas
                </span>
            </div>
        </div>

        <!-- Metric 3: Pohon Standar (Gratis) -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Pohon Standar (Gratis)</span>
                <div class="w-9 h-9 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 flex items-center justify-center border border-zinc-200 dark:border-zinc-700 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">park</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                {{ number_format($counts['free']) }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="text-zinc-500 dark:text-zinc-400">
                    Maksimum 100 anggota
                </span>
            </div>
        </div>

        <!-- Metric 4: Total Kunjungan Publik -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-sky-300 dark:hover:border-sky-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Kunjungan Publik</span>
                <div class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-400 flex items-center justify-center border border-sky-200/50 dark:border-sky-800/50 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">visibility</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                {{ number_format($counts['total_views']) }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="text-zinc-500 dark:text-zinc-400">
                    Akumulasi pembaca publik
                </span>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
        <button wire:click="setFilter('all')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $filter === 'all' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Semua Pohon</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'all' ? 'bg-white/20 dark:bg-black/20' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['all'] }}</span>
        </button>

        <button wire:click="setFilter('premium')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $filter === 'premium' ? 'bg-amber-600 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>👑 Premium</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'premium' ? 'bg-amber-700 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['premium'] }}</span>
        </button>

        <button wire:click="setFilter('free')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $filter === 'free' ? 'bg-zinc-700 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Standar (Gratis)</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'free' ? 'bg-zinc-800 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['free'] }}</span>
        </button>

        <button wire:click="setFilter('public')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $filter === 'public' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Publik</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'public' ? 'bg-emerald-700 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['public'] }}</span>
        </button>

        <button wire:click="setFilter('private')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $filter === 'private' ? 'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-900 shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Privat</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'private' ? 'bg-black/20' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['private'] }}</span>
        </button>
    </div>

    <!-- Table Card -->
    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-5">Pohon Silsilah</th>
                        <th class="py-3.5 px-4">Pemilik</th>
                        <th class="py-3.5 px-4 text-center">Anggota</th>
                        <th class="py-3.5 px-4 text-center">Kunjungan</th>
                        <th class="py-3.5 px-4">Akses</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Dibuat</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @forelse($trees as $tree)
                        @php
                            $owner = $tree->users->first();
                        @endphp
                        <tr class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="py-3.5 px-5">
                                <div class="min-w-0 max-w-xs">
                                    <p class="font-bold text-zinc-900 dark:text-white truncate text-sm">{{ $tree->name }}</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="font-mono text-[10px] text-zinc-400 truncate">/{{ $tree->slug }}</span>
                                    </div>
                                    @if($tree->description)
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate mt-0.5">{{ $tree->description }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($owner)
                                    <div class="flex items-center gap-2.5">
                                        @if($owner->avatar)
                                            <img src="{{ $owner->avatar }}" class="w-7 h-7 rounded-full object-cover border border-zinc-200 dark:border-zinc-700 shrink-0" alt="">
                                        @else
                                            <div class="w-7 h-7 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 flex items-center justify-center font-bold text-[10px] shrink-0 border border-emerald-200/60 dark:border-emerald-800/60">
                                                {{ $owner->initials() }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-bold text-zinc-900 dark:text-white truncate">{{ $owner->name }}</p>
                                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate">{{ $owner->email }}</p>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-zinc-400 italic text-[11px]">Tanpa Pemilik</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-zinc-700 dark:text-zinc-300">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 font-semibold text-[11px]">
                                    <span class="material-symbols-outlined text-xs text-zinc-400">group</span>
                                    <span>{{ $tree->members_count }}</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1 text-[11px] text-zinc-600 dark:text-zinc-400">
                                    <span class="material-symbols-outlined text-xs text-zinc-400">visibility</span>
                                    <span>{{ number_format($tree->views_count ?? 0) }}</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($tree->is_public)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span class="material-symbols-outlined text-xs">public</span>
                                        Publik
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                        <span class="material-symbols-outlined text-xs">lock</span>
                                        Privat
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($tree->is_premium)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-2xs">
                                        👑 Premium
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                        Standar
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-zinc-500 dark:text-zinc-400 text-[11px]">
                                {{ $tree->created_at?->translatedFormat('d M Y') ?? '-' }}
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5 flex-nowrap">
                                    <!-- View Tree Link -->
                                    <a href="{{ route('tree.show', $tree) }}"
                                       target="_blank"
                                       title="Lihat Pohon Silsilah"
                                       class="inline-flex items-center justify-center gap-1 h-7 px-2.5 rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 font-semibold text-xs border border-zinc-200/80 dark:border-zinc-700 transition-all shrink-0">
                                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                                        <span>Lihat</span>
                                    </a>

                                    <!-- Override Status Button -->
                                    <button wire:click="openOverrideModal({{ $tree->id }})"
                                            title="Ubah Status Premium Manual"
                                            class="inline-flex items-center justify-center gap-1 h-7 px-2.5 rounded-lg {{ $tree->is_premium ? 'bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border-zinc-200 dark:border-zinc-700' : 'bg-amber-50 hover:bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:hover:bg-amber-900 dark:text-amber-300 border-amber-200 dark:border-amber-800' }} font-bold text-xs border transition-all shrink-0 cursor-pointer">
                                        <span class="material-symbols-outlined text-sm">{{ $tree->is_premium ? 'tune' : 'workspace_premium' }}</span>
                                        <span>Override Status</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                Tidak ada data pohon silsilah yang sesuai dengan pencarian atau filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($trees->hasPages())
            <div class="p-4 border-t border-zinc-200/80 dark:border-zinc-700/60">
                {{ $trees->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Override Status -->
    <flux:modal wire:model="showOverrideModal" class="md:w-[32rem]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Override Status Premium Pohon</flux:heading>
                <flux:subheading class="mt-1">
                    Ubah status pohon <strong>{{ $selectedTree?->name }}</strong> secara manual. Digunakan apabila pengguna membayar tunai/COD, transfer manual, atau kompensasi khusus.
                </flux:subheading>
            </div>

            <!-- Current vs Target Summary Box -->
            <div class="p-3.5 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200/80 dark:border-zinc-700/80 text-xs space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Pemilik Pohon:</span>
                    <span class="font-bold text-zinc-900 dark:text-white">{{ $selectedTree?->users->first()?->name ?? 'Tanpa Pemilik' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Status Saat Ini:</span>
                    @if($selectedTree?->is_premium)
                        <span class="font-extrabold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-800">👑 Premium</span>
                    @else
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300 bg-zinc-200/60 dark:bg-zinc-700/60 px-2 py-0.5 rounded-full">Standar (Gratis)</span>
                    @endif
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-zinc-200/60 dark:border-zinc-700/60">
                    <span class="text-zinc-500 dark:text-zinc-400">Aksi yang Diambil:</span>
                    <span class="font-bold {{ $selectedTree?->is_premium ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ $selectedTree?->is_premium ? 'Kembalikan ke Status Standar' : 'Aktifkan Menjadi Premium' }}
                    </span>
                </div>
            </div>

            <!-- Form Inputs -->
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Alasan / Metode Override <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="overrideReason" class="w-full text-xs rounded-xl bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white px-3 py-2.5 focus:ring-2 focus:ring-emerald-500">
                        <option value="cash_cod">Bayar Tunai / COD saat tatap muka</option>
                        <option value="bank_transfer">Transfer Bank Manual</option>
                        <option value="gateway_issue">Kendala Gateway QRIS</option>
                        <option value="special_promo">Kompensasi / Promo Khusus</option>
                        <option value="other">Lainnya</option>
                    </select>
                    @error('overrideReason')
                        <span class="text-red-500 text-[11px] block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Catatan / Referensi (Opsional)
                    </label>
                    <flux:input wire:model="overrideNotes" placeholder="Misal: Bukti transfer BCA via WhatsApp / no. referensi" />
                    @error('overrideNotes')
                        <span class="text-red-500 text-[11px] block mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="closeOverrideModal">Batal</flux:button>
                </flux:modal.close>

                @if($selectedTree?->is_premium)
                    <flux:button type="button" wire:click="saveOverride(false)" variant="danger">
                        Kembalikan ke Standar
                    </flux:button>
                @else
                    <flux:button type="button" wire:click="saveOverride(true)" class="!bg-amber-500 hover:!bg-amber-600 !text-zinc-950 font-bold">
                        Jadikan Premium
                    </flux:button>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
