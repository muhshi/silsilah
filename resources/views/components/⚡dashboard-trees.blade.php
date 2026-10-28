<?php

use Livewire\Component;
use App\Models\FamilyTree;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

new class extends Component
{
    public $showModal = false;
    public $name = '';
    public $description = '';
    public $is_public = false;
    public $view_password = '';

    public $search = '';
    public $filter = 'all';

    public function openCreateTree()
    {
        if (auth()->user()->hasAvailableTreeQuota()) {
            Flux::modal('create-tree-modal')->show();
        } else {
            Flux::modal('buy-quota-modal')->show();
        }
    }

    public function createTree()
    {
        $user = auth()->user();
        if (! $user->hasAvailableTreeQuota()) {
            Flux::modal('create-tree-modal')->close();
            Flux::modal('buy-quota-modal')->show();
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $isFirstTree = $user->ownedTreesCount() === 0;
        $isPremium = ! $isFirstTree;

        $tree = FamilyTree::create([
            'name' => $this->name,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'is_premium' => $isPremium,
            'view_password' => $this->view_password ? Hash::make($this->view_password) : null,
        ]);

        $tree->users()->attach($user->id, ['role' => 'owner']);

        return redirect()->route('tree.show', $tree);
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    public function with()
    {
        $user = auth()->user();
        $allUserTrees = $user->familyTrees()->withCount('members')->latest()->get();
        $totalMembers = $allUserTrees->sum('members_count');
        $totalTrees = $allUserTrees->count();
        $totalPublic = $allUserTrees->where('is_public', true)->count();
        $totalPrivate = $allUserTrees->where('is_public', false)->count();
        $totalPremium = $allUserTrees->where('is_premium', true)->count();

        $ownedTreesCount = $user->ownedTreesCount();
        $treeQuota = $user->tree_quota ?? 1;
        $remainingQuota = $user->remainingTreeQuota();
        $hasAvailableQuota = $user->hasAvailableTreeQuota();

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

        $recentOrder = null;
        if (request()->filled('order_id')) {
            $recentOrder = \App\Models\Order::where('order_id', request('order_id'))
                ->where('user_id', $user->id)
                ->first();
        }

        $pricingPackages = \App\Models\PricingPackage::active()->where('tree_quota', '>', 0)->get();
        $targetUpgradeTree = null;
        if (request()->filled('upgrade_tree')) {
            $targetUpgradeTree = $user->familyTrees()->find(request('upgrade_tree'));
        }

        return [
            'trees' => $trees,
            'totalMembers' => $totalMembers,
            'totalTrees' => $totalTrees,
            'totalPublic' => $totalPublic,
            'totalPrivate' => $totalPrivate,
            'totalPremium' => $totalPremium,
            'ownedTreesCount' => $ownedTreesCount,
            'treeQuota' => $treeQuota,
            'remainingQuota' => $remainingQuota,
            'hasAvailableQuota' => $hasAvailableQuota,
            'recentOrder' => $recentOrder,
            'pricingPackages' => $pricingPackages,
            'targetUpgradeTree' => $targetUpgradeTree,
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
    <!-- Payment Feedback Messages -->
    @if($recentOrder)
        @if($recentOrder->isPaid())
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center gap-3 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 shadow-sm">
                <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">check_circle</span>
                <div>
                    <span class="font-bold text-sm block">Pembayaran Berhasil!</span>
                    @if($recentOrder->isTreeUpgrade())
                        <span class="text-xs">Alhamdulillah, pembayaran untuk upgrade pohon <strong>{{ $recentOrder->targetTree?->name ?? 'Pohon Silsilah' }}</strong> berhasil diverifikasi! Pohon ini sekarang adalah <strong>👑 Pohon Premium</strong> dengan kapasitas anggota unlimited dan kolaborasi keluarga aktif.</span>
                    @else
                        <span class="text-xs">Alhamdulillah, pembayaran untuk pesanan <strong>{{ $recentOrder->order_id }}</strong> berhasil diverifikasi dan kuota pohon silsilah Anda telah bertambah.</span>
                    @endif
                </div>
            </div>
        @elseif($recentOrder->status === 'pending')
            <div class="bg-amber-50 border border-amber-200 text-amber-900 px-5 py-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-200 shadow-sm">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="material-symbols-outlined text-amber-600 dark:text-amber-400 text-2xl shrink-0">hourglass_top</span>
                    <div>
                        <span class="font-bold text-sm block">Menunggu Pembayaran</span>
                        <span class="text-xs text-amber-800 dark:text-amber-300">
                            Pesanan <strong>{{ $recentOrder->order_id }}</strong> masih berstatus PENDING. Silakan selesaikan pembayaran QRIS Anda atau tunggu beberapa saat jika baru saja melakukan pembayaran.
                        </span>
                    </div>
                </div>
                @if($recentOrder->payment_url)
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ $recentOrder->payment_url }}" target="_blank"
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white text-xs font-bold shadow-md shadow-amber-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                            <span class="material-symbols-outlined text-base">qr_code_scanner</span>
                            Lanjut Bayar QRIS &rarr;
                        </a>
                    </div>
                @endif
            </div>
        @elseif($recentOrder->status === 'expired' || $recentOrder->status === 'failed')
            <div class="bg-zinc-100 border border-zinc-300 text-zinc-800 px-5 py-4 rounded-2xl flex items-center gap-3 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-300 shadow-sm">
                <span class="material-symbols-outlined text-zinc-500">cancel</span>
                <div>
                    <span class="font-bold text-sm block">Pembayaran Belum Berhasil</span>
                    <span class="text-xs">Pesanan <strong>{{ $recentOrder->order_id }}</strong> belum diselesaikan atau telah kedaluwarsa.</span>
                </div>
            </div>
        @endif
    @elseif(request('payment') === 'success')
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center gap-3 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 shadow-sm">
            <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">check_circle</span>
            <div>
                <span class="font-bold text-sm block">Pembayaran Berhasil!</span>
                <span class="text-xs">Alhamdulillah, kuota pohon silsilah Anda telah bertambah dan siap digunakan.</span>
            </div>
        </div>
    @endif
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
            <button wire:click="openCreateTree"
                    class="flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white px-7 py-3.5 rounded-2xl font-bold shadow-lg shadow-emerald-600/25 hover:shadow-emerald-600/35 hover:scale-[1.02] active:scale-[0.98] transition-all w-full sm:w-auto text-sm">
                <span class="material-symbols-outlined text-xl">add</span>
                Buat Silsilah Baru
            </button>

            <flux:modal.trigger name="buy-quota-modal">
                <button class="flex items-center justify-center gap-2 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 px-5 py-3.5 rounded-2xl font-bold hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition-all w-full sm:w-auto text-sm shadow-xs cursor-pointer">
                    <span class="material-symbols-outlined text-lg">shopping_bag</span>
                    Beli Kuota Pohon
                </button>
            </flux:modal.trigger>
        </div>
    </div>

    <!-- M3 Stat Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- Stat 1: Total Pohon & Kuota -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-50/80 to-white dark:from-emerald-950/20 dark:to-zinc-900 border border-emerald-100 dark:border-emerald-900/40 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-1">Total Silsilah</p>
                <p class="text-3xl sm:text-4xl font-extrabold text-zinc-900 dark:text-white font-headline">{{ $totalTrees }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    Kuota: <span class="font-bold text-zinc-700 dark:text-zinc-200">{{ $ownedTreesCount }} / {{ $treeQuota }}</span> 
                    @if($remainingQuota > 0)
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">(Sisa {{ $remainingQuota }})</span>
                    @else
                        <span class="text-amber-600 dark:text-amber-400 font-bold">(Penuh)</span>
                    @endif
                </p>
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

    <!-- Referral Banner (Ajak Teman & Dapatkan +1 Kuota Pohon) -->
    <div x-data="{ copied: false }" class="p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-emerald-50 via-teal-50/50 to-white dark:from-emerald-950/40 dark:via-zinc-900 dark:to-zinc-900 border border-emerald-200/80 dark:border-emerald-800/60 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl shrink-0 shadow-xs">
                🎁
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Program Referral Keluarga</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-200/70 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-200">Win-Win</span>
                </div>
                <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white mt-0.5">
                    Ajak Keluarga & Kerabat, Dapatkan +1 Kuota Pohon Gratis!
                </h3>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
                    Bagikan kode referral Anda. Teman Anda mendapat <strong>Diskon 15%</strong> saat beli kuota, dan Anda langsung dapat <strong>+1 Kuota Pohon Gratis</strong>.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
            <div class="px-3.5 py-2 rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 font-mono font-bold text-xs text-zinc-900 dark:text-white flex items-center gap-2 shadow-2xs">
                <span class="text-zinc-400 font-sans text-[11px]">Kode:</span>
                <span class="tracking-wider text-emerald-700 dark:text-emerald-400">{{ auth()->user()->referral_code ?? 'SIL-'.auth()->id() }}</span>
            </div>

            <button type="button"
                    @click="navigator.clipboard.writeText('{{ auth()->user()->referral_code }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="h-9 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer">
                <span class="material-symbols-outlined text-[16px]" x-text="copied ? 'check' : 'content_copy'"></span>
                <span x-text="copied ? 'Tersalin!' : 'Salin'"></span>
            </button>

            <a href="https://wa.me/?text={{ urlencode('Halo! Aku pakai aplikasi Silsilah Keluarga Digital. Daftar atau beli kuota pakai kode referralku ' . (auth()->user()->referral_code) . ' untuk dapat diskon 15%: ' . (auth()->user()->referralLink())) }}"
               target="_blank"
               class="h-9 px-3 rounded-xl bg-[#25D366] hover:bg-[#20ba59] text-white text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer">
                <span class="text-sm">💬</span>
                <span class="hidden sm:inline">WhatsApp</span>
            </a>
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
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-50 dark:from-emerald-950/60 dark:to-teal-900/30 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xl shadow-xs group-hover:scale-105 transition-transform">
                            🌳
                        </div>

                        <div class="flex items-center gap-1.5 flex-wrap justify-end">
                            @if($tree->is_public)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/80 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> PUBLIK
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-zinc-700/60 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span> PRIVAT
                                </span>
                            @endif

                            @if($tree->is_premium)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 flex items-center gap-1 shadow-2xs">
                                    👑 PREMIUM
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-zinc-700/60 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                    STANDAR
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Tree Name & Description -->
                    <h3 class="text-lg font-headline font-bold text-zinc-900 dark:text-white group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors mb-1.5 line-clamp-1">
                        {{ $tree->name }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2 min-h-[32px] leading-relaxed mb-5">
                        {{ $tree->description ?: 'Belum ada deskripsi untuk silsilah keluarga ini.' }}
                    </p>
                </div>

                <div>
                    <!-- Meta Row: Members & Date -->
                    <div class="flex items-center justify-between text-xs font-semibold text-zinc-500 dark:text-zinc-400 pb-4 border-b border-zinc-100 dark:border-zinc-700/60 mb-4">
                        <div class="flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-700/50 px-2.5 py-1 rounded-lg">
                            <span class="material-symbols-outlined text-sm text-zinc-400">group</span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-bold tabular-nums">{{ $tree->members_count }}</span>
                            <span class="text-zinc-400 text-[11px]">{{ $tree->is_premium ? 'Anggota' : '/ 100 Anggota' }}</span>
                        </div>
                        <div class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">
                            {{ $tree->created_at->locale('id')->diffForHumans() }}
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <!-- Primary CTA -->
                        <button onclick="event.stopPropagation(); window.location='{{ route('tree.show', $tree) }}'"
                                class="flex-1 h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white text-xs font-bold shadow-xs hover:shadow-md hover:shadow-emerald-600/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                            <span>Buka Silsilah</span>
                            <svg class="w-3.5 h-3.5 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                            </svg>
                        </button>

                        <!-- Contextual Secondary: Upgrade 5K (Soft amber accent, height 40px) -->
                        @if(!$tree->is_premium)
                            <button type="button"
                                    onclick="event.stopPropagation(); window.dispatchEvent(new CustomEvent('open-upgrade-modal', { detail: { id: {{ $tree->id }}, name: '{{ addslashes($tree->name) }}' } }))"
                                    class="h-10 px-3 rounded-xl bg-amber-50 hover:bg-amber-100 active:scale-[0.98] dark:bg-amber-950/40 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-200 border border-amber-200 dark:border-amber-800/70 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shrink-0 shadow-2xs"
                                    title="Tingkatkan ke Pohon Premium (Rp 5.000)">
                                <span class="material-symbols-outlined text-base text-amber-600 dark:text-amber-400">workspace_premium</span>
                                <span>Upgrade 5K</span>
                            </button>
                        @endif

                        <!-- Auxiliary: Mobile / Focus View (Height 40px) -->
                        <a href="{{ route('tree.vertical', $tree) }}"
                           onclick="event.stopPropagation();"
                           wire:navigate
                           class="h-10 w-10 rounded-xl bg-zinc-100 hover:bg-zinc-200 active:scale-[0.98] dark:bg-zinc-700/60 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 flex items-center justify-center transition-all shrink-0 cursor-pointer"
                           title="Mode Fokus Smartphone (Vertikal)">
                            <span class="material-symbols-outlined text-lg">smartphone</span>
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
                <button wire:click="openCreateTree" class="mt-4 px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition-colors">
                    + Buat Silsilah Baru
                </button>
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

    <!-- Modal Beli Kuota (Duitku QRIS Multi-Paket & Kustom Kuota) -->
    <flux:modal name="buy-quota-modal" class="md:w-[44rem]">
        @php
            $defaultPackage = $pricingPackages->first(fn($p) => $p->canUserPurchase(auth()->user())) ?? $pricingPackages->first();
            $defaultId = $defaultPackage?->id ?? 0;
            $packageMap = $pricingPackages->keyBy('id')->map(fn($p) => [
                'id' => $p->id,
                'price' => $p->price,
                'name' => $p->name,
                'quota' => $p->tree_quota,
                'is_one_time' => $p->is_one_time_per_user,
            ]);
        @endphp

        <div x-data="{
                selectedId: {{ $defaultId }},
                isCustom: false,
                customCount: 4,
                promoInput: '',
                appliedCode: '',
                discountAmount: 0,
                promoMessage: '',
                promoError: '',
                isCheckingPromo: false,
                packageMap: {{ Js::from($packageMap) }},

                get basePrice() {
                    if (this.isCustom) {
                        let n = parseInt(this.customCount) || 1;
                        if (n <= 2) return n * 10000;
                        if (n <= 4) return n * 8500;
                        return n * 8000;
                    }
                    return this.packageMap[this.selectedId] ? this.packageMap[this.selectedId].price : 10000;
                },

                get finalPrice() {
                    return Math.max(1000, this.basePrice - this.discountAmount);
                },

                get formattedBasePrice() {
                    return new Intl.NumberFormat('id-ID').format(this.basePrice);
                },

                get formattedFinalPrice() {
                    return new Intl.NumberFormat('id-ID').format(this.finalPrice);
                },

                get formattedDiscount() {
                    return new Intl.NumberFormat('id-ID').format(this.discountAmount);
                },

                selectPackage(id) {
                    this.isCustom = false;
                    this.selectedId = id;
                    if (this.appliedCode) {
                        this.validatePromo();
                    }
                },

                selectCustom() {
                    this.isCustom = true;
                    this.selectedId = null;
                    if (this.appliedCode) {
                        this.validatePromo();
                    }
                },

                changeCustomCount(delta) {
                    let next = Math.max(1, Math.min(100, (parseInt(this.customCount) || 1) + delta));
                    this.customCount = next;
                    this.isCustom = true;
                    this.selectedId = null;
                    if (this.appliedCode) {
                        this.validatePromo();
                    }
                },

                async validatePromo() {
                    let code = (this.promoInput || this.appliedCode).trim();
                    if (!code) {
                        this.promoError = 'Silakan masukkan kode promo atau referral.';
                        return;
                    }
                    this.isCheckingPromo = true;
                    this.promoError = '';
                    this.promoMessage = '';

                    try {
                        let res = await fetch('{{ route('orders.validate-code') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                code: code,
                                package_id: this.isCustom ? null : this.selectedId,
                                custom_quota: this.isCustom ? this.customCount : null
                            })
                        });

                        let data = await res.json();
                        if (res.ok && data.valid) {
                            this.appliedCode = data.code;
                            this.discountAmount = data.discount;
                            this.promoMessage = data.message;
                            this.promoError = '';
                        } else {
                            this.discountAmount = 0;
                            this.promoError = data.message || 'Kode promo atau referral tidak valid.';
                            this.promoMessage = '';
                            this.appliedCode = '';
                        }
                    } catch (e) {
                        this.promoError = 'Gagal memverifikasi kode promo. Silakan coba lagi.';
                    } finally {
                        this.isCheckingPromo = false;
                    }
                },

                removePromo() {
                    this.appliedCode = '';
                    this.promoInput = '';
                    this.discountAmount = 0;
                    this.promoMessage = '';
                    this.promoError = '';
                }
             }"
             class="space-y-5">

            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-3xl shadow-inner mb-2.5">
                    🌳
                </div>
                <flux:heading size="lg">Beli Kuota Pohon Silsilah</flux:heading>
                <flux:subheading class="mt-0.5">
                    Pilih paket hemat instan atau tentukan sendiri jumlah pohon sesuai kebutuhan keluarga Anda.
                </flux:subheading>
            </div>

            <!-- 1. Package Selection Grid -->
            <div>
                <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2">Pilihan Paket Hemat:</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($pricingPackages as $package)
                        @php
                            $isEligible = $package->canUserPurchase(auth()->user());
                            $priceFormatted = number_format($package->price, 0, ',', '.');
                            $origPriceFormatted = $package->original_price ? number_format($package->original_price, 0, ',', '.') : null;
                        @endphp

                        <div @if($isEligible) @click="selectPackage({{ $package->id }})" @endif
                             class="relative rounded-2xl p-3.5 border transition-all text-left select-none
                                    @if(!$isEligible)
                                        opacity-50 bg-zinc-50 dark:bg-zinc-900/40 border-zinc-200 dark:border-zinc-800 cursor-not-allowed
                                    @else
                                        cursor-pointer hover:shadow-sm
                                    @endif"
                             :class="{
                                'bg-emerald-50/70 border-emerald-500 ring-2 ring-emerald-500/30 dark:bg-emerald-950/40 dark:border-emerald-500': !isCustom && selectedId === {{ $package->id }} && {{ $isEligible ? 'true' : 'false' }},
                                'bg-white dark:bg-zinc-900 border-zinc-200/90 dark:border-zinc-800 hover:border-emerald-300 dark:hover:border-emerald-700': isCustom || selectedId !== {{ $package->id }} && {{ $isEligible ? 'true' : 'false' }}
                             }">

                            <!-- Badge if present -->
                            @if($package->badge)
                                <div class="absolute -top-2.5 right-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider shadow-xs
                                        {{ $package->slug === 'promo-idul-fitri'
                                            ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white'
                                            : 'bg-emerald-600 text-white' }}">
                                        {{ $package->badge }}
                                    </span>
                                </div>
                            @elseif(!$isEligible)
                                <div class="absolute -top-2.5 right-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                                        Sudah Digunakan
                                    </span>
                                </div>
                            @endif

                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-base">🌳</span>
                                        <span class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white">{{ $package->name }}</span>
                                    </div>
                                    <div class="mt-1 flex items-baseline gap-2">
                                        <span class="text-lg font-extrabold text-emerald-700 dark:text-emerald-400">
                                            Rp {{ $priceFormatted }}
                                        </span>
                                        @if($origPriceFormatted)
                                            <span class="text-[11px] text-zinc-400 line-through">
                                                Rp {{ $origPriceFormatted }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Radio indicator -->
                                <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 mt-0.5"
                                     :class="!isCustom && selectedId === {{ $package->id }} ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-zinc-300 dark:border-zinc-700'">
                                    <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="!isCustom && selectedId === {{ $package->id }}"></div>
                                </div>
                            </div>

                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1.5 line-clamp-2 leading-relaxed">
                                {{ $package->description }}
                            </p>

                            <div class="mt-2.5 pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[10px] font-semibold">
                                <span class="text-zinc-500">Kuota Diperoleh:</span>
                                <span class="text-emerald-700 dark:text-emerald-300 font-bold">+{{ $package->tree_quota }} Pohon Silsilah</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 2. Custom Quota Option (Beli Sesuai Kebutuhan) -->
            <div class="rounded-2xl p-4 border transition-all text-left select-none cursor-pointer"
                 @click="selectCustom"
                 :class="{
                    'bg-emerald-50/70 border-emerald-500 ring-2 ring-emerald-500/30 dark:bg-emerald-950/40 dark:border-emerald-500': isCustom,
                    'bg-white dark:bg-zinc-900 border-zinc-200/90 dark:border-zinc-800 hover:border-emerald-300': !isCustom
                 }">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-lg">✨</span>
                            <span class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white">Beli Kuota Kustom (Sesuai Kebutuhan)</span>
                            <span class="px-2 py-0.2 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/40">Grosir Cerdas</span>
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Harga otomatis menyesuaikan: 1-2 pohon @Rp 10k, 3-4 pohon @Rp 8.5k, 5+ pohon @Rp 8k.
                        </p>
                    </div>

                    <!-- Counter Controls -->
                    <div class="flex items-center gap-2 shrink-0" @click.stop>
                        <button type="button"
                                @click="changeCustomCount(-1)"
                                :disabled="customCount <= 1"
                                class="w-8 h-8 rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 font-extrabold flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer">
                            -
                        </button>
                        <div class="px-3 py-1 rounded-xl bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-center min-w-[70px]">
                            <span class="text-sm font-extrabold text-zinc-900 dark:text-white" x-text="customCount"></span>
                            <span class="text-[10px] text-zinc-400 block -mt-0.5">Pohon</span>
                        </div>
                        <button type="button"
                                @click="changeCustomCount(1)"
                                :disabled="customCount >= 100"
                                class="w-8 h-8 rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 font-extrabold flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer">
                            +
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. Voucher / Referral Code Input Section -->
            <div class="p-3.5 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-700/80 space-y-2">
                <label class="block text-xs font-bold text-zinc-800 dark:text-zinc-200">
                    🏷️ Punya Kode Promo atau Referral?
                </label>
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <input type="text"
                               x-model="promoInput"
                               placeholder="Contoh: SILSILAH15 atau Kode Referral Teman"
                               :disabled="!!appliedCode"
                               @keydown.enter.prevent="validatePromo"
                               class="w-full px-3.5 py-2 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-xs font-mono tracking-wider text-zinc-900 dark:text-white placeholder-zinc-400 focus:ring-2 focus:ring-emerald-500 uppercase disabled:opacity-60">
                    </div>
                    <template x-if="!appliedCode">
                        <button type="button"
                                @click="validatePromo"
                                :disabled="isCheckingPromo"
                                class="h-9 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-xs shrink-0 cursor-pointer disabled:opacity-60">
                            <span x-show="!isCheckingPromo">Terapkan</span>
                            <span x-show="isCheckingPromo">Cek...</span>
                        </button>
                    </template>
                    <template x-if="appliedCode">
                        <button type="button"
                                @click="removePromo"
                                class="h-9 px-3 rounded-xl bg-zinc-200 hover:bg-red-50 text-zinc-700 hover:text-red-600 dark:bg-zinc-700 dark:hover:bg-red-950 dark:text-zinc-300 dark:hover:text-red-300 text-xs font-bold transition-all shrink-0 cursor-pointer">
                            Hapus
                        </button>
                    </template>
                </div>

                <!-- Feedback Messages -->
                <template x-if="promoMessage">
                    <div class="p-2.5 rounded-xl bg-emerald-100/70 dark:bg-emerald-950/60 border border-emerald-300/80 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base">check_circle</span>
                            <span x-text="promoMessage"></span>
                        </div>
                        <span class="font-extrabold font-mono" x-text="'-Rp ' + formattedDiscount"></span>
                    </div>
                </template>
                <template x-if="promoError">
                    <p class="text-xs text-red-600 dark:text-red-400 font-medium flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">error</span>
                        <span x-text="promoError"></span>
                    </p>
                </template>
            </div>

            <!-- 4. Price Summary -->
            <div class="bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-3.5 space-y-1.5 text-xs">
                <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Subtotal Tagihan:</span>
                    <span class="font-bold text-zinc-900 dark:text-white" x-text="'Rp ' + formattedBasePrice"></span>
                </div>
                <template x-if="discountAmount > 0">
                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-bold">
                        <span>Diskon Promo / Referral:</span>
                        <span x-text="'-Rp ' + formattedDiscount"></span>
                    </div>
                </template>
                <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-sm">
                    <span class="font-extrabold text-zinc-900 dark:text-white">Total Pembayaran QRIS:</span>
                    <span class="text-lg font-black text-emerald-700 dark:text-emerald-400 font-mono" x-text="'Rp ' + formattedFinalPrice"></span>
                </div>
            </div>

            <!-- 5. Checkout Form -->
            <form action="{{ route('orders.checkout') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="package_id" :value="isCustom ? '' : selectedId">
                <input type="hidden" name="custom_quota" :value="isCustom ? customCount : ''">
                <input type="hidden" name="code" :value="appliedCode">

                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white py-3.5 px-6 rounded-2xl font-bold shadow-lg shadow-emerald-600/25 hover:shadow-emerald-600/35 transition-all text-sm cursor-pointer hover:scale-[1.01] active:scale-[0.99]">
                    <span class="material-symbols-outlined text-lg">qr_code_scanner</span>
                    <span>Lanjut Bayar Rp <span x-text="formattedFinalPrice"></span> via QRIS</span>
                </button>
            </form>

            <div class="flex justify-center">
                <flux:modal.close>
                    <flux:button variant="ghost" size="sm" class="cursor-pointer">Nanti Saja</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <!-- Modal: Upgrade Spesifik Pohon ke Premium (5K) -->
    <flux:modal name="upgrade-tree-modal" class="md:w-[32rem]">
        <div x-data="{
            treeId: null,
            treeName: '',
            promoInput: '',
            appliedCode: '',
            discountAmount: 0,
            isCheckingPromo: false,
            promoMessage: '',
            promoError: '',
            basePrice: 5000,
            init() {
                window.addEventListener('open-upgrade-modal', (e) => {
                    this.treeId = e.detail.id;
                    this.treeName = e.detail.name;
                    this.removePromo();
                    $flux.modal('upgrade-tree-modal').show();
                });
                @if(request()->filled('upgrade_tree') && $targetUpgradeTree)
                    this.treeId = {{ $targetUpgradeTree->id }};
                    this.treeName = '{{ addslashes($targetUpgradeTree->name) }}';
                    this.$nextTick(() => {
                        $flux.modal('upgrade-tree-modal').show();
                    });
                @endif
            },
            async validatePromo() {
                if (!this.promoInput.trim()) return;
                this.isCheckingPromo = true;
                this.promoError = '';
                this.promoMessage = '';
                try {
                    const res = await fetch('{{ route('orders.validate-code') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            code: this.promoInput.trim(),
                            target_tree_id: this.treeId
                        })
                    });
                    const data = await res.json();
                    if (data.valid) {
                        this.appliedCode = data.code;
                        this.discountAmount = data.discount;
                        this.promoMessage = data.message;
                    } else {
                        this.promoError = data.message || 'Kode promo tidak valid';
                    }
                } catch (err) {
                    this.promoError = 'Gagal memvalidasi kode promo.';
                } finally {
                    this.isCheckingPromo = false;
                }
            },
            removePromo() {
                this.appliedCode = '';
                this.promoInput = '';
                this.discountAmount = 0;
                this.promoMessage = '';
                this.promoError = '';
            },
            get finalPrice() {
                return Math.max(1000, this.basePrice - this.discountAmount);
            },
            get formattedFinalPrice() {
                return new Intl.NumberFormat('id-ID').format(this.finalPrice);
            },
            get formattedDiscount() {
                return new Intl.NumberFormat('id-ID').format(this.discountAmount);
            }
        }" class="space-y-6">
            <div>
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-2xl mb-3 shadow-xs">
                    👑
                </div>
                <flux:heading size="lg">Upgrade ke Pohon Premium</flux:heading>
                <flux:subheading>
                    Tingkatkan pohon <strong class="text-zinc-900 dark:text-white" x-text="treeName"></strong> menjadi Pohon Premium selamanya.
                </flux:subheading>
            </div>

            <!-- Manfaat Premium -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50/60 dark:from-amber-950/40 dark:to-orange-950/30 border border-amber-200/80 dark:border-amber-800/50 space-y-2.5 text-xs">
                <h4 class="font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                    <span>Keuntungan Pohon Premium:</span>
                </h4>
                <div class="space-y-2 text-zinc-700 dark:text-zinc-300">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-sm">check_circle</span>
                        <span><strong>Kapasitas Anggota Tanpa Batas</strong> (Bebas limit 100 orang)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-sm">check_circle</span>
                        <span><strong>Fitur Kolaborasi Editor</strong> (Undang kerabat ikut mengedit silsilah)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-sm">check_circle</span>
                        <span><strong>Riwayat Perubahan Lengkap</strong> & badge mahkota emas 👑</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-sm">check_circle</span>
                        <span><strong>Sekali bayar selamanya</strong> (Tidak ada biaya langganan bulanan)</span>
                    </div>
                </div>
            </div>

            <!-- Voucher Input -->
            <div class="p-3.5 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-700/80 space-y-2">
                <label class="block text-xs font-bold text-zinc-800 dark:text-zinc-200">
                    🏷️ Punya Kode Promo atau Referral?
                </label>
                <div class="flex items-center gap-2">
                    <input type="text"
                           x-model="promoInput"
                           placeholder="Contoh: SILSILAH15"
                           :disabled="!!appliedCode"
                           @keydown.enter.prevent="validatePromo"
                           class="w-full px-3.5 py-2 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-xs font-mono uppercase tracking-wider text-zinc-900 dark:text-white placeholder-zinc-400">
                    <template x-if="!appliedCode">
                        <button type="button"
                                @click="validatePromo"
                                :disabled="isCheckingPromo"
                                class="h-9 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shrink-0 cursor-pointer disabled:opacity-60">
                            <span x-show="!isCheckingPromo">Terapkan</span>
                            <span x-show="isCheckingPromo">Cek...</span>
                        </button>
                    </template>
                    <template x-if="appliedCode">
                        <button type="button"
                                @click="removePromo"
                                class="h-9 px-3 rounded-xl bg-zinc-200 hover:bg-red-50 text-zinc-700 hover:text-red-600 text-xs font-bold transition-all shrink-0 cursor-pointer">
                            Hapus
                        </button>
                    </template>
                </div>
                <template x-if="promoMessage">
                    <div class="p-2.5 rounded-xl bg-emerald-100/70 dark:bg-emerald-950/60 border border-emerald-300/80 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between">
                        <span x-text="promoMessage"></span>
                        <span class="font-bold font-mono" x-text="'-Rp ' + formattedDiscount"></span>
                    </div>
                </template>
                <template x-if="promoError">
                    <p class="text-xs text-red-500 font-medium" x-text="promoError"></p>
                </template>
            </div>

            <!-- Summary & Checkout Button -->
            <div class="bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-3.5 space-y-1.5 text-xs">
                <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                    <span>Biaya Upgrade Pohon:</span>
                    <span class="font-bold text-zinc-900 dark:text-white">Rp 5.000</span>
                </div>
                <template x-if="discountAmount > 0">
                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-bold">
                        <span>Diskon Promo / Referral:</span>
                        <span x-text="'-Rp ' + formattedDiscount"></span>
                    </div>
                </template>
                <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-sm">
                    <span class="font-extrabold text-zinc-900 dark:text-white">Total Pembayaran QRIS:</span>
                    <span class="text-lg font-black text-amber-600 dark:text-amber-400 font-mono" x-text="'Rp ' + formattedFinalPrice"></span>
                </div>
            </div>

            <form action="{{ route('orders.checkout') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="target_tree_id" :value="treeId">
                <input type="hidden" name="code" :value="appliedCode">

                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white py-3.5 px-6 rounded-2xl font-bold shadow-lg shadow-amber-500/25 hover:shadow-amber-500/35 transition-all text-sm cursor-pointer hover:scale-[1.01] active:scale-[0.99]">
                    <span class="material-symbols-outlined text-lg">qr_code_scanner</span>
                    <span>Bayar Rp <span x-text="formattedFinalPrice"></span> via QRIS</span>
                </button>
            </form>

            <div class="flex justify-center">
                <flux:modal.close>
                    <flux:button variant="ghost" size="sm" class="cursor-pointer">Batal</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>