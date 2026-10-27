<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function manualVerifyOrder($orderId)
    {
        $order = Order::with('user')->findOrFail($orderId);

        if ($order->status === 'paid') {
            session()->flash('error', 'Pesanan ini sudah berstatus PAID sebelumnya.');
            return;
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => 'paid',
            ]);

            $quotaToAdd = $order->quota_count ?? 1;
            $order->user->increment('tree_quota', $quotaToAdd);

            Log::info('Admin Manually Verified Order', [
                'admin_id' => auth()->id(),
                'order_id' => $order->order_id,
                'user_id' => $order->user_id,
                'quota_added' => $quotaToAdd,
            ]);
        });

        session()->flash('success', "Pesanan {$order->order_id} berhasil diverifikasi manual. +{$order->quota_count} kuota pohon telah ditambahkan ke akun {$order->user->name}.");
    }

    public function markAsExpired($orderId)
    {
        $order = Order::findOrFail($orderId);

        if ($order->status === 'paid') {
            session()->flash('error', 'Pesanan yang sudah PAID tidak dapat diubah ke EXPIRED.');
            return;
        }

        $order->update(['status' => 'expired']);
        session()->flash('success', "Pesanan {$order->order_id} ditandai sebagai EXPIRED.");
    }

    public function with()
    {
        $query = Order::with('user')->latest();

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('order_id', 'like', "%{$term}%")
                  ->orWhere('reference', 'like', "%{$term}%")
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('name', 'like', "%{$term}%")
                         ->orWhere('email', 'like', "%{$term}%");
                  });
            });
        }

        if ($this->statusFilter && $this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $orders = $query->paginate(15);

        $counts = [
            'all' => Order::count(),
            'paid' => Order::where('status', 'paid')->count(),
            'pending' => Order::where('status', 'pending')->count(),
            'expired' => Order::where('status', 'expired')->count(),
        ];

        return [
            'orders' => $orders,
            'counts' => $counts,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-zinc-200/80 dark:border-zinc-800">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/80 dark:text-amber-300 mb-2">
                <span>💳</span> Gateway Transaksi
            </div>
            <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                Manajemen Transaksi Duitku
            </h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                Audit riwayat pesanan kuota, pantau pembayaran QRIS, dan lakukan verifikasi manual jika terdapat kendala webhook.
            </p>
        </div>

        <!-- Search Bar -->
        <div class="w-full sm:w-80">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari Order ID, customer, email..." icon="magnifying-glass" clearable />
        </div>
    </div>

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2 shadow-xs">
            <span class="material-symbols-outlined text-emerald-600 text-lg">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 dark:bg-red-950/40 dark:border-red-800 dark:text-red-300 text-xs font-semibold flex items-center gap-2 shadow-xs">
            <span class="material-symbols-outlined text-red-600 text-lg">error</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
        <button wire:click="$set('statusFilter', 'all')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-amber-600 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200' }}">
            <span>Semua</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/15">{{ $counts['all'] }}</span>
        </button>

        <button wire:click="$set('statusFilter', 'paid')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'paid' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200' }}">
            <span>Paid (Berhasil)</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/15">{{ $counts['paid'] }}</span>
        </button>

        <button wire:click="$set('statusFilter', 'pending')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'pending' ? 'bg-amber-500 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200' }}">
            <span>Pending</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/15">{{ $counts['pending'] }}</span>
        </button>

        <button wire:click="$set('statusFilter', 'expired')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'expired' ? 'bg-zinc-700 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200' }}">
            <span>Expired</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/15">{{ $counts['expired'] }}</span>
        </button>
    </div>

    <!-- Orders Table -->
    <div class="bg-white dark:bg-zinc-800/80 rounded-3xl border border-zinc-200/80 dark:border-zinc-700/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200/80 dark:border-zinc-700/60 bg-zinc-50/50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-5">ID Pesanan</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Nominal</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4">Waktu</th>
                        <th class="py-3.5 px-5 text-right">Aksi Bantuan (Support)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    @forelse($orders as $order)
                        <tr class="hover:bg-zinc-50/80 dark:hover:bg-zinc-700/20 transition-colors">
                            <td class="py-3.5 px-5">
                                <p class="font-bold text-zinc-900 dark:text-white font-mono">{{ $order->order_id }}</p>
                                @if($order->reference)
                                    <p class="text-[10px] text-zinc-400 font-mono">Ref: {{ $order->reference }}</p>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <p class="font-bold text-zinc-900 dark:text-white">{{ $order->user?->name ?? 'User #'.$order->user_id }}</p>
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ $order->user?->email }}</p>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-zinc-900 dark:text-white">
                                Rp {{ number_format($order->amount, 0, ',', '.') }}
                                <span class="text-[10px] text-zinc-400 block font-normal">+{{ $order->quota_count }} Pohon</span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($order->status === 'paid')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        ✓ PAID
                                    </span>
                                @elseif($order->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                        ⏳ PENDING
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                        {{ strtoupper($order->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-zinc-500 dark:text-zinc-400 text-[11px]">
                                {{ $order->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                                <span class="block text-[10px] text-zinc-400">{{ $order->created_at?->diffForHumans() }}</span>
                            </td>
                            <td class="py-3.5 px-5 text-right space-x-1.5">
                                @if($order->status !== 'paid')
                                    <button wire:click="manualVerifyOrder({{ $order->id }})"
                                            wire:confirm="Konfirmasi manual pesanan ini dan otomatis tambahkan kuota pohon ke pengguna?"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-bold text-[11px] shadow-sm transition-all">
                                        <span class="material-symbols-outlined text-sm">verified</span>
                                        Verifikasi & Beri Kuota
                                    </button>

                                    @if($order->status === 'pending')
                                        <button wire:click="markAsExpired({{ $order->id }})"
                                                wire:confirm="Tandai pesanan ini sebagai EXPIRED?"
                                                class="inline-flex items-center px-2 py-1.5 rounded-xl text-[10px] font-semibold text-zinc-400 hover:text-red-600 transition-colors">
                                            Expired
                                        </button>
                                    @endif
                                @else
                                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center justify-end gap-1">
                                        <span class="material-symbols-outlined text-sm">check</span>
                                        Tuntas
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                Tidak ada transaksi yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-zinc-200/80 dark:border-zinc-700/60">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
