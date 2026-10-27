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
    <!-- Header Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-zinc-200/80 dark:border-zinc-800">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/80 dark:text-amber-300 mb-2">
                <span>🛡️</span> Superadmin Overview
            </div>
            <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                Ringkasan Platform & KPI
            </h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                Pantau metrik kesehatan bisnis SaaS, omzet pembayaran QRIS, dan pertumbuhan pengguna.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users') }}" wire:navigate class="px-4 py-2 text-xs font-bold rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow-sm transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">group</span>
                Kelola User & Kuota
            </a>
            <a href="{{ route('admin.orders') }}" wire:navigate class="px-4 py-2 text-xs font-bold rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">receipt</span>
                Daftar Transaksi
            </a>
        </div>
    </div>

    <!-- 4 Primary Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1: Total Revenue -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-50/80 to-white dark:from-emerald-950/30 dark:to-zinc-900 border border-emerald-200/70 dark:border-emerald-800/40 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400">Total Omzet QRIS</span>
                <span class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-lg">💰</span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 flex items-center gap-1.5">
                <span class="font-bold text-emerald-700 dark:text-emerald-400">{{ $totalPaidOrders }} transaksi</span> berhasil
                @if($totalPendingOrders > 0)
                    <span class="text-amber-600 font-semibold">({{ $totalPendingOrders }} pending)</span>
                @endif
            </p>
        </div>

        <!-- Card 2: Total Users -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-sky-50/80 to-white dark:from-sky-950/30 dark:to-zinc-900 border border-sky-200/70 dark:border-sky-800/40 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-sky-800 dark:text-sky-400">Total Pengguna</span>
                <span class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300 flex items-center justify-center text-lg">👥</span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline">
                {{ number_format($totalUsers, 0, ',', '.') }}
            </p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                Akun terdaftar di sistem
            </p>
        </div>

        <!-- Card 3: Total Trees -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-amber-50/80 to-white dark:from-amber-950/30 dark:to-zinc-900 border border-amber-200/70 dark:border-amber-800/40 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-400">Pohon Silsilah</span>
                <span class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 flex items-center justify-center text-lg">🌳</span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline">
                {{ number_format($totalTrees, 0, ',', '.') }}
            </p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                Pohon keluarga aktif dibuat
            </p>
        </div>

        <!-- Card 4: Total Members (Jiwa) -->
        <div class="p-6 rounded-3xl bg-gradient-to-br from-purple-50/80 to-white dark:from-purple-950/30 dark:to-zinc-900 border border-purple-200/70 dark:border-purple-800/40 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-purple-800 dark:text-purple-400">Total Jiwa</span>
                <span class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 flex items-center justify-center text-lg">🌿</span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline">
                {{ number_format($totalMembers, 0, ',', '.') }}
            </p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                Anggota keluarga terdokumentasi
            </p>
        </div>
    </div>

    <!-- 2 Column Grids: Recent Orders & Recent Users -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Orders -->
        <div class="bg-white dark:bg-zinc-800/80 rounded-3xl p-6 border border-zinc-200/80 dark:border-zinc-700/60 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-white">Transaksi Duitku Terbaru</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">6 pesanan kuota terakhir</p>
                </div>
                <a href="{{ route('admin.orders') }}" wire:navigate class="text-xs font-bold text-amber-700 dark:text-amber-400 hover:underline">
                    Lihat Semua &rarr;
                </a>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                @forelse($recentOrders as $order)
                    <div class="py-3 flex items-center justify-between gap-3 text-xs">
                        <div class="min-w-0">
                            <p class="font-bold text-zinc-900 dark:text-white truncate">
                                {{ $order->user?->name ?? 'User #'.$order->user_id }}
                            </p>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">
                                {{ $order->order_id }} &bull; {{ $order->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="text-right flex items-center gap-2">
                            <span class="font-bold text-zinc-900 dark:text-white">
                                Rp {{ number_format($order->amount, 0, ',', '.') }}
                            </span>
                            @if($order->status === 'paid')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    PAID
                                </span>
                            @elseif($order->status === 'pending')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                    PENDING
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                    {{ strtoupper($order->status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                        Belum ada transaksi kuota tercatat.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Users -->
        <div class="bg-white dark:bg-zinc-800/80 rounded-3xl p-6 border border-zinc-200/80 dark:border-zinc-700/60 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-base text-zinc-900 dark:text-white">Pengguna Baru Mendaftar</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">6 pendaftaran terakhir</p>
                </div>
                <a href="{{ route('admin.users') }}" wire:navigate class="text-xs font-bold text-amber-700 dark:text-amber-400 hover:underline">
                    Kelola Pengguna &rarr;
                </a>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                @forelse($recentUsers as $u)
                    <div class="py-3 flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            @if($u->avatar)
                                <img src="{{ $u->avatar }}" class="w-8 h-8 rounded-full object-cover border border-zinc-200 dark:border-zinc-700" alt="">
                            @else
                                <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 flex items-center justify-center font-bold text-xs">
                                    {{ $u->initials() }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-bold text-zinc-900 dark:text-white truncate">{{ $u->name }}</p>
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">{{ $u->email }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-zinc-100 dark:bg-zinc-700/60 text-zinc-700 dark:text-zinc-300">
                                Kuota: {{ $u->tree_quota ?? 1 }}
                            </span>
                            <p class="text-[10px] text-zinc-400 mt-0.5">{{ $u->family_trees_count }} pohon</p>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                        Belum ada pengguna terdaftar.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
