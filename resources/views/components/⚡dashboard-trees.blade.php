<?php

use Livewire\Component;
use App\Models\FamilyTree;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $showModal = false;
    public $name = '';
    public $description = '';
    public $is_public = false;
    public $view_password = '';

    public $search = '';
    public $filter = 'all';

    public function createTree()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $tree = FamilyTree::create([
            'name' => $this->name,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'view_password' => $this->view_password ? Hash::make($this->view_password) : null,
        ]);

        $tree->users()->attach(auth()->id(), ['role' => 'owner']);

        return redirect()->route('tree.show', $tree);
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    public function with()
    {
        $allUserTrees = auth()->user()->familyTrees()->withCount('members')->latest()->get();
        $totalMembers = $allUserTrees->sum('members_count');
        $totalTrees = $allUserTrees->count();
        $totalPublic = $allUserTrees->where('is_public', true)->count();
        $totalPrivate = $allUserTrees->where('is_public', false)->count();
        $totalPremium = $allUserTrees->where('is_premium', true)->count();

        $trees = $allUserTrees;

        if ($this->search) {
            $search = strtolower(trim($this->search));
            $trees = $trees->filter(function($t) use ($search) {
                return str_contains(strtolower($t->name), $search) || str_contains(strtolower($t->description ?? ''), $search);
            });
        }

        if ($this->filter === 'public') {
            $trees = $trees->where('is_public', true);
        } elseif ($this->filter === 'private') {
            $trees = $trees->where('is_public', false);
        } elseif ($this->filter === 'premium') {
            $trees = $trees->where('is_premium', true);
        }

        return [
            'trees' => $trees,
            'totalMembers' => $totalMembers,
            'totalTrees' => $totalTrees,
            'totalPublic' => $totalPublic,
            'totalPrivate' => $totalPrivate,
            'totalPremium' => $totalPremium,
        ];
    }

    public function upgradeToPremium($treeId)
    {
        $tree = auth()->user()->familyTrees()->findOrFail($treeId);
        $tree->update(['is_premium' => true]);
        
        session()->flash('success', 'Berhasil! Pohon ini sekarang adalah Pohon Premium.');
        return redirect()->route('dashboard');
    }
};
?>

<div class="space-y-8 max-w-7xl mx-auto">
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center gap-3 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 shadow-sm">
            <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">check_circle</span>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-2xl flex items-center gap-3 dark:bg-red-950/40 dark:border-red-800 dark:text-red-300 shadow-sm">
            <span class="material-symbols-outlined text-red-600 dark:text-red-400">error</span>
            <span class="font-bold text-sm">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Hero Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-2">
        <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold uppercase tracking-widest mb-3">
                <span>🌱</span> The Living Heritage
            </div>
            <h1 class="text-4xl sm:text-5xl font-headline font-extrabold text-zinc-900 dark:text-white tracking-tight leading-tight">
                Silsilah Keluarga
            </h1>
            <p class="text-zinc-600 dark:text-zinc-400 text-base sm:text-lg mt-2 leading-relaxed">
                Kelola, telusuri, dan wariskan akar silsilah keluarga Anda secara elegan dan terstruktur.
            </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <flux:modal.trigger name="create-tree-modal">
                <button class="flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white px-7 py-3.5 rounded-2xl font-bold shadow-lg shadow-emerald-600/25 hover:shadow-emerald-600/35 hover:scale-[1.02] active:scale-[0.98] transition-all w-full sm:w-auto text-sm">
                    <span class="material-symbols-outlined text-xl">add</span>
                    Buat Silsilah Baru
                </button>
            </flux:modal.trigger>
        </div>
    </div>

    <!-- M3 Stat Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- Stat 1: Total Pohon -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-50/80 to-white dark:from-emerald-950/20 dark:to-zinc-900 border border-emerald-100 dark:border-emerald-900/40 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-1">Total Silsilah</p>
                <p class="text-3xl sm:text-4xl font-extrabold text-zinc-900 dark:text-white font-headline">{{ $totalTrees }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Pohon keluarga aktif</p>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-emerald-100/80 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-2xl shadow-inner">
                🌳
            </div>
        </div>

        <!-- Stat 2: Total Anggota -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-amber-50/80 to-white dark:from-amber-950/20 dark:to-zinc-900 border border-amber-100 dark:border-amber-900/40 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 mb-1">Total Anggota</p>
                <p class="text-3xl sm:text-4xl font-extrabold text-zinc-900 dark:text-white font-headline">{{ $totalMembers }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Jiwa terhubung</p>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-amber-100/80 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 flex items-center justify-center text-2xl shadow-inner">
                👥
            </div>
        </div>

        <!-- Stat 3: Status Premium -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-purple-50/80 to-white dark:from-purple-950/20 dark:to-zinc-900 border border-purple-100 dark:border-purple-900/40 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400 mb-1">Pohon Premium</p>
                <p class="text-3xl sm:text-4xl font-extrabold text-zinc-900 dark:text-white font-headline">{{ $totalPremium }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Fitur tak terbatas</p>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-purple-100/80 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 flex items-center justify-center text-2xl shadow-inner">
                👑
            </div>
        </div>
    </div>

    <!-- Live Search & M3 Filter Chips Bar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-2">
        <!-- Search Input -->
        <div class="relative flex-1 max-w-md">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400 text-lg">search</span>
            <input type="text"
                   wire:model.live.debounce.250ms="search"
                   placeholder="Cari silsilah keluarga..."
                   class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-white dark:bg-zinc-800/90 border border-zinc-200 dark:border-zinc-700 text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm transition-all text-zinc-900 dark:text-white placeholder-zinc-400">
            @if($search)
                <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    ✕
                </button>
            @endif
        </div>

        <!-- Filter Chips -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
            <button wire:click="setFilter('all')"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all {{ $filter === 'all' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-sm' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
                Semua ({{ $totalTrees }})
            </button>
            <button wire:click="setFilter('public')"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 {{ $filter === 'public' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
                <span>🌍</span> Publik ({{ $totalPublic }})
            </button>
            <button wire:click="setFilter('private')"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 {{ $filter === 'private' ? 'bg-zinc-700 text-white shadow-sm' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
                <span>🔒</span> Privat ({{ $totalPrivate }})
            </button>
            <button wire:click="setFilter('premium')"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 {{ $filter === 'premium' ? 'bg-purple-600 text-white shadow-sm' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
                <span>👑</span> Premium ({{ $totalPremium }})
            </button>
        </div>
    </div>

    <!-- Tree Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($trees as $tree)
            <!-- M3 Elevated Family Tree Card -->
            <div wire:key="tree-{{ $tree->id }}"
                 onclick="window.location='{{ route('tree.show', $tree) }}'"
                 class="group bg-white dark:bg-zinc-800/90 border border-zinc-200/80 dark:border-zinc-700/80 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:shadow-emerald-600/10 hover:border-emerald-400/50 dark:hover:border-emerald-500/50 cursor-pointer relative overflow-hidden transition-all duration-300 flex flex-col justify-between">
                
                <div>
                    <!-- Top Status Row -->
                    <div class="flex items-start justify-between mb-5">
                        <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-950/60 dark:to-teal-900/30 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-2xl shadow-sm group-hover:scale-105 transition-transform">
                            🌳
                        </div>

                        <div class="flex items-center gap-1.5 flex-wrap justify-end">
                            @if($tree->is_public)
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> PUBLIK
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-zinc-100 dark:bg-zinc-700/60 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span> PRIVAT
                                </span>
                            @endif

                            @if($tree->is_premium)
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 flex items-center gap-1">
                                    👑 PREMIUM
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Tree Name & Description -->
                    <h3 class="text-xl font-headline font-bold text-zinc-900 dark:text-white group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors mb-2 line-clamp-1">
                        {{ $tree->name }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2 min-h-[32px] leading-relaxed mb-6">
                        {{ $tree->description ?: 'Belum ada deskripsi untuk silsilah keluarga ini.' }}
                    </p>
                </div>

                <div>
                    <!-- Meta Row: Members & Date -->
                    <div class="flex items-center justify-between text-xs font-semibold text-zinc-500 dark:text-zinc-400 pb-5 border-b border-zinc-100 dark:border-zinc-700/60 mb-5">
                        <div class="flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-700/50 px-3 py-1.5 rounded-full">
                            <span>👥</span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-bold">{{ $tree->members_count }}</span> Anggota
                        </div>
                        <div class="text-[11px]">
                            {{ $tree->created_at->diffForHumans() }}
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <button onclick="event.stopPropagation(); window.location='{{ route('tree.show', $tree) }}'"
                                class="flex-1 py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 hover:shadow-emerald-600/30 transition-all flex items-center justify-center gap-1.5">
                            <span>Buka Silsilah</span>
                            <span>➔</span>
                        </button>
                        <a href="{{ route('tree.vertical', $tree) }}"
                           onclick="event.stopPropagation();"
                           wire:navigate
                           class="py-2.5 px-3 rounded-xl bg-zinc-100 hover:bg-amber-50 dark:bg-zinc-700/60 dark:hover:bg-amber-950/40 text-zinc-700 hover:text-amber-700 dark:text-zinc-300 dark:hover:text-amber-300 text-xs font-bold transition-all"
                           title="Buka Mode Fokus HP">
                            🧭
                        </a>
                    </div>
                </div>

                <!-- Subtle Decorative Accent -->
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/10 transition-colors pointer-events-none"></div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-3xl bg-zinc-50/50 dark:bg-zinc-800/20">
                <span class="text-4xl block mb-3">🔍</span>
                <h3 class="text-base font-bold text-zinc-800 dark:text-zinc-200">Tidak ada silsilah ditemukan</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-sm mx-auto">Coba ganti kata kunci pencarian atau buat pohon silsilah keluarga baru.</p>
                <flux:modal.trigger name="create-tree-modal">
                    <button class="mt-4 px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition-colors">
                        + Buat Silsilah Baru
                    </button>
                </flux:modal.trigger>
            </div>
        @endforelse
    </div>

    <!-- Modal Form for Create Tree -->
    <flux:modal name="create-tree-modal" class="md:w-[32rem]">
        <form wire:submit="createTree" class="space-y-6">
            <div>
                <flux:heading size="lg">Buat Silsilah Keluarga</flux:heading>
                <flux:subheading>Masukkan detail awal untuk silsilah keluarga Anda.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="name" label="Nama Keluarga" placeholder="Misal: Keluarga Ahmad Jamil" required />
                
                <flux:textarea wire:model="description" label="Deskripsi (Opsional)" placeholder="Keterangan singkat tentang silsilah keluarga ini" />
                
                <flux:switch wire:model="is_public" label="Publik" description="Silsilah dapat diakses dan dilihat oleh siapa saja yang memiliki link" />

                <flux:input type="password" wire:model="view_password" label="Password Silsilah (Opsional)" placeholder="Password untuk memproteksi tampilan publik" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="!bg-emerald-600 !text-white hover:!bg-emerald-700">Simpan & Mulai</flux:button>
            </div>
        </form>
    </flux:modal>
</div>