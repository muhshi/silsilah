<?php

use Livewire\Component;
use App\Models\User;
use App\Models\FamilyTree;
use App\Models\Member;
use App\Models\Order;

new class extends Component
{
    public function with()
    {
        $totalRevenue = Order::where('status', 'paid')->sum('amount');
        $totalPaidOrders = Order::where('status', 'paid')->count();
        $totalPendingOrders = Order::where('status', 'pending')->count();
        $totalUsers = User::count();
        $totalTrees = FamilyTree::count();
        $totalMembers = Member::count();

        $recentOrders = Order::with('user')->latest()->take(6)->get();
        $recentUsers = User::withCount('familyTrees')->latest()->take(6)->get();

        return [
            'totalRevenue' => $totalRevenue,
            'totalPaidOrders' => $totalPaidOrders,
            'totalPendingOrders' => $totalPendingOrders,
            'totalUsers' => $totalUsers,
            'totalTrees' => $totalTrees,
            'totalMembers' => $totalMembers,
            'recentOrders' => $recentOrders,
            'recentUsers' => $recentUsers,
        ];
    }
};
?>

<div class="space-y-8">
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

    <!-- 4 High-Density Executive Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Metric 1: Total Revenue -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-emerald-300 dark:hover:border-emerald-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Omzet QRIS</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center border border-emerald-200/50 dark:border-emerald-800/50 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">payments</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-1.5 py-0.5 rounded">
                    {{ $totalPaidOrders }} Paid
                </span>
                <span class="text-zinc-400">&bull;</span>
                <span class="text-zinc-500 dark:text-zinc-400">
                    {{ $totalPendingOrders }} Menunggu
                </span>
            </div>
        </div>

        <!-- Metric 2: Total Users -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-sky-300 dark:hover:border-sky-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Pengguna</span>
                <div class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-400 flex items-center justify-center border border-sky-200/50 dark:border-sky-800/50 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">group</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                {{ number_format($totalUsers, 0, ',', '.') }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="text-zinc-500 dark:text-zinc-400">Akun terdaftar di sistem</span>
            </div>
        </div>

        <!-- Metric 3: Total Trees -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-teal-300 dark:hover:border-teal-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Pohon Silsilah</span>
                <div class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-400 flex items-center justify-center border border-teal-200/50 dark:border-teal-800/50 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">account_tree</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                {{ number_format($totalTrees, 0, ',', '.') }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="text-zinc-500 dark:text-zinc-400">Pohon keluarga aktif</span>
            </div>
        </div>

        <!-- Metric 4: Total Members (Jiwa) -->
        <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:border-violet-300 dark:hover:border-violet-700 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Individu (Jiwa)</span>
                <div class="w-9 h-9 rounded-xl bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-400 flex items-center justify-center border border-violet-200/50 dark:border-violet-800/50 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-lg">diversity_3</span>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                {{ number_format($totalMembers, 0, ',', '.') }}
            </p>
            <div class="mt-3 flex items-center gap-1.5 text-[11px]">
                <span class="text-zinc-500 dark:text-zinc-400">Anggota kekerabatan</span>
            </div>
        </div>
    </div>

    <!-- 2 Column Layout: Recent Transactions & New Users -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Orders Card -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs overflow-hidden flex flex-col justify-between">
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

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                @forelse($recentOrders as $order)
                    <div class="p-4 flex items-center justify-between gap-3 text-xs hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                        <div class="min-w-0">
                            <p class="font-bold text-zinc-900 dark:text-white truncate">
                                {{ $order->user?->name ?? 'User #'.$order->user_id }}
                            </p>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 font-mono mt-0.5 truncate">
                                {{ $order->order_id }} &bull; {{ $order->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="text-right flex items-center gap-2.5 shrink-0">
                            <span class="font-bold text-zinc-900 dark:text-white text-xs">
                                Rp {{ number_format($order->amount, 0, ',', '.') }}
                            </span>
                            @if($order->isPaid())
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                    PAID
                                </span>
                            @elseif($order->status === 'pending')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">
                                    PENDING
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                    {{ strtoupper($order->status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-xs text-zinc-500 dark:text-zinc-400">
                        Belum ada transaksi kuota tercatat.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Registered Users Card -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs overflow-hidden flex flex-col justify-between">
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

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                @forelse($recentUsers as $u)
                    <div class="p-4 flex items-center justify-between gap-3 text-xs hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            @if($u->avatar)
                                <img src="{{ $u->avatar }}" class="w-8 h-8 rounded-full object-cover border border-zinc-200 dark:border-zinc-700 shrink-0" alt="">
                            @else
                                <div class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 flex items-center justify-center font-bold text-[11px] shrink-0">
                                    {{ $u->initials() }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-bold text-zinc-900 dark:text-white truncate">{{ $u->name }}</p>
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">{{ $u->email }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                Kuota: {{ $u->tree_quota ?? 1 }}
                            </span>
                            <p class="text-[10px] text-zinc-400 mt-0.5">{{ $u->family_trees_count }} pohon</p>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-xs text-zinc-500 dark:text-zinc-400">
                        Belum ada pengguna terdaftar.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
