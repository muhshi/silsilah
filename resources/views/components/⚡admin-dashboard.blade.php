<?php

use Livewire\Component;
use App\Models\User;
use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\Order;

new class extends Component
{
    public $period = 'all';

    public function with()
    {
        $orderQuery = Order::query();
        $userQuery = User::query();
        $treeQuery = FamilyTree::query();
        $memberQuery = Member::query();

        if ($this->period === 'today') {
            $startDate = now()->startOfDay();
            $orderQuery->where('created_at', '>=', $startDate);
            $userQuery->where('created_at', '>=', $startDate);
            $treeQuery->where('created_at', '>=', $startDate);
            $memberQuery->where('created_at', '>=', $startDate);
        } elseif ($this->period === '7d') {
            $startDate = now()->subDays(7)->startOfDay();
            $orderQuery->where('created_at', '>=', $startDate);
            $userQuery->where('created_at', '>=', $startDate);
            $treeQuery->where('created_at', '>=', $startDate);
            $memberQuery->where('created_at', '>=', $startDate);
        } elseif ($this->period === '30d') {
            $startDate = now()->subDays(30)->startOfDay();
            $orderQuery->where('created_at', '>=', $startDate);
            $userQuery->where('created_at', '>=', $startDate);
            $treeQuery->where('created_at', '>=', $startDate);
            $memberQuery->where('created_at', '>=', $startDate);
        }

        $periodRevenue = (clone $orderQuery)->where('status', 'paid')->sum('amount');
        $periodPaidOrders = (clone $orderQuery)->where('status', 'paid')->count();
        $periodPendingOrders = (clone $orderQuery)->where('status', 'pending')->count();
        $periodUsers = (clone $userQuery)->count();
        $periodTrees = (clone $treeQuery)->count();
        $periodMembers = (clone $memberQuery)->count();

        $lifetimeRevenue = Order::where('status', 'paid')->sum('amount');
        $lifetimeUsers = User::count();
        $lifetimeTrees = FamilyTree::count();
        $lifetimeMembers = Member::count();

        $recentOrders = Order::with('user')->latest()->take(6)->get();
        $recentUsers = User::withCount('familyTrees')->latest()->take(6)->get();

        return [
            'periodRevenue' => $periodRevenue,
            'periodPaidOrders' => $periodPaidOrders,
            'periodPendingOrders' => $periodPendingOrders,
            'periodUsers' => $periodUsers,
            'periodTrees' => $periodTrees,
            'periodMembers' => $periodMembers,
            'lifetimeRevenue' => $lifetimeRevenue,
            'lifetimeUsers' => $lifetimeUsers,
            'lifetimeTrees' => $lifetimeTrees,
            'lifetimeMembers' => $lifetimeMembers,
            'recentOrders' => $recentOrders,
            'recentUsers' => $recentUsers,
        ];
    }
};
?>

<div class="space-y-6 sm:space-y-8">
    <!-- Header Title & Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-200/80 dark:border-zinc-800/80">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Executive Overview
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                Metrik Utama Platform Silsilah
            </h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Ikhtisar pertumbuhan pengguna, pergerakan omzet QRIS Duitku, dan statistik pohon silsilah keluarga.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users') }}" wire:navigate class="h-9 px-3.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100 text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">group</span>
                <span>Kelola Pengguna</span>
            </a>
            <a href="{{ route('admin.orders') }}" wire:navigate class="h-9 px-3.5 rounded-xl bg-white hover:bg-zinc-50 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 text-xs font-bold transition-all shadow-2xs flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">receipt_long</span>
                <span>Transaksi Duitku</span>
            </a>
        </div>
    </div>

    <!-- Date Range Filter Tabs -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-1.5 p-1 bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-2xl text-xs">
            <button wire:click="$set('period', 'all')"
                    class="px-3 py-1.5 rounded-xl font-bold transition-all {{ $period === 'all' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">
                Semua Waktu
            </button>
            <button wire:click="$set('period', '30d')"
                    class="px-3 py-1.5 rounded-xl font-bold transition-all {{ $period === '30d' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">
                30 Hari Terakhir
            </button>
            <button wire:click="$set('period', '7d')"
                    class="px-3 py-1.5 rounded-xl font-bold transition-all {{ $period === '7d' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">
                7 Hari Terakhir
            </button>
            <button wire:click="$set('period', 'today')"
                    class="px-3 py-1.5 rounded-xl font-bold transition-all {{ $period === 'today' ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">
                Hari Ini
            </button>
        </div>

        <div class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-zinc-400">calendar_today</span>
            <span>
                @if($period === 'today')
                    Periode: Hari Ini ({{ now()->translatedFormat('d M Y') }})
                @elseif($period === '7d')
                    Periode: 7 Hari Terakhir
                @elseif($period === '30d')
                    Periode: 30 Hari Terakhir
                @else
                    Periode: Sepanjang Waktu
                @endif
            </span>
        </div>
    </div>

    <!-- 4 High-Density Executive Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Metric 1: Total Revenue -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-emerald-300 dark:hover:border-emerald-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Omzet QRIS</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center border border-emerald-200/50 dark:border-emerald-800/50 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">payments</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                Rp {{ number_format($periodRevenue, 0, ',', '.') }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-1.5 py-0.5 rounded">
                    {{ $periodPaidOrders }} Paid
                </span>
                <span class="text-zinc-400">&bull;</span>
                <span class="text-zinc-500 dark:text-zinc-400">
                    {{ $periodPendingOrders }} Menunggu
                </span>
            </div>
            @if($period !== 'all')
                <div class="mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800 text-[10px] text-zinc-400">
                    Sepanjang waktu: Rp {{ number_format($lifetimeRevenue, 0, ',', '.') }}
                </div>
            @endif
        </div>

        <!-- Metric 2: Total Users -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-sky-300 dark:hover:border-sky-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    {{ $period === 'all' ? 'Total Pengguna' : 'Pengguna Baru' }}
                </span>
                <div class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-400 flex items-center justify-center border border-sky-200/50 dark:border-sky-800/50 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">group</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                {{ number_format($periodUsers, 0, ',', '.') }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="text-zinc-500 dark:text-zinc-400">
                    {{ $period === 'all' ? 'Akun terdaftar di sistem' : 'Akun mendaftar pada periode ini' }}
                </span>
            </div>
            @if($period !== 'all')
                <div class="mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800 text-[10px] text-zinc-400">
                    Total akun sistem: {{ number_format($lifetimeUsers, 0, ',', '.') }}
                </div>
            @endif
        </div>

        <!-- Metric 3: Total Trees -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-teal-300 dark:hover:border-teal-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    {{ $period === 'all' ? 'Pohon Silsilah' : 'Pohon Baru Dibuat' }}
                </span>
                <div class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-400 flex items-center justify-center border border-teal-200/50 dark:border-teal-800/50 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">account_tree</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                {{ number_format($periodTrees, 0, ',', '.') }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="text-zinc-500 dark:text-zinc-400">
                    {{ $period === 'all' ? 'Pohon keluarga aktif' : 'Pohon dibuat periode ini' }}
                </span>
            </div>
            @if($period !== 'all')
                <div class="mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800 text-[10px] text-zinc-400">
                    Total pohon sistem: {{ number_format($lifetimeTrees, 0, ',', '.') }}
                </div>
            @endif
        </div>

        <!-- Metric 4: Total Members (Jiwa) -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-violet-300 dark:hover:border-violet-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    {{ $period === 'all' ? 'Total Individu (Jiwa)' : 'Individu Ditambahkan' }}
                </span>
                <div class="w-9 h-9 rounded-xl bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-400 flex items-center justify-center border border-violet-200/50 dark:border-violet-800/50 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">diversity_3</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                {{ number_format($periodMembers, 0, ',', '.') }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="text-zinc-500 dark:text-zinc-400">
                    {{ $period === 'all' ? 'Anggota kekerabatan' : 'Data anggota diinput' }}
                </span>
            </div>
            @if($period !== 'all')
                <div class="mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800 text-[10px] text-zinc-400">
                    Total individu sistem: {{ number_format($lifetimeMembers, 0, ',', '.') }}
                </div>
            @endif
        </div>
    </div>

    <!-- 2 Column Layout: Recent Transactions & New Users (Structured Mini-Tables) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Orders Card -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-zinc-900 dark:text-white">Transaksi Duitku Terkini</h3>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">Daftar pesanan kuota pohon terbaru</p>
                </div>
                <a href="{{ route('admin.orders') }}" wire:navigate class="text-xs font-bold text-emerald-700 dark:text-emerald-400 hover:underline flex items-center gap-1">
                    <span>Lihat Semua</span>
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/40 text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider text-[10px]">
                            <th class="py-2.5 px-4">Pelanggan & ID</th>
                            <th class="py-2.5 px-3">Waktu</th>
                            <th class="py-2.5 px-3 text-right">Nominal</th>
                            <th class="py-2.5 px-4 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        @forelse($recentOrders as $order)
                            <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-3 px-4">
                                    <p class="font-bold text-zinc-900 dark:text-white truncate max-w-[170px] sm:max-w-none">
                                        {{ $order->user?->name ?? 'User #'.$order->user_id }}
                                    </p>
                                    <p class="text-[10px] text-zinc-400 font-mono truncate">
                                        {{ $order->order_id }} &bull; +{{ $order->quota_count }} Pohon
                                    </p>
                                </td>
                                <td class="py-3 px-3 text-zinc-500 dark:text-zinc-400 text-[11px] whitespace-nowrap">
                                    {{ $order->created_at->diffForHumans() }}
                                </td>
                                <td class="py-3 px-3 text-right font-bold text-zinc-900 dark:text-white whitespace-nowrap">
                                    Rp {{ number_format($order->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    @if($order->isPaid())
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60">
                                            <span class="material-symbols-outlined text-[12px] leading-none">check</span>
                                            <span>PAID</span>
                                        </span>
                                    @elseif($order->status === 'pending')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            <span>PENDING</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                            {{ strtoupper($order->status) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                    Belum ada transaksi kuota tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Registered Users Card -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-zinc-900 dark:text-white">Pengguna Baru Bergabung</h3>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">Pendaftaran akun pengguna terkini</p>
                </div>
                <a href="{{ route('admin.users') }}" wire:navigate class="text-xs font-bold text-emerald-700 dark:text-emerald-400 hover:underline flex items-center gap-1">
                    <span>Lihat Semua</span>
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/40 text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider text-[10px]">
                            <th class="py-2.5 px-4">Pengguna</th>
                            <th class="py-2.5 px-3">Bergabung</th>
                            <th class="py-2.5 px-3 text-center">Pohon</th>
                            <th class="py-2.5 px-4 text-right">Kuota Tersedia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        @forelse($recentUsers as $u)
                            <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        @if($u->avatar)
                                            <img src="{{ $u->avatar }}" class="w-7 h-7 rounded-full object-cover border border-zinc-200 dark:border-zinc-700 shrink-0" alt="">
                                        @else
                                            <div class="w-7 h-7 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 flex items-center justify-center font-bold text-[10px] shrink-0">
                                                {{ $u->initials() }}
                                            </div>
                                        @endif
                                        <div class="min-w-0 max-w-[150px] sm:max-w-none">
                                            <p class="font-bold text-zinc-900 dark:text-white truncate">{{ $u->name }}</p>
                                            <p class="text-[10px] text-zinc-400 truncate">{{ $u->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-zinc-500 dark:text-zinc-400 text-[11px] whitespace-nowrap">
                                    {{ $u->created_at->diffForHumans() }}
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap font-medium text-zinc-700 dark:text-zinc-300">
                                    <span class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-[11px] font-bold">
                                        {{ $u->family_trees_count }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                        {{ $u->tree_quota ?? 1 }} Kuota
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                    Belum ada pengguna terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
