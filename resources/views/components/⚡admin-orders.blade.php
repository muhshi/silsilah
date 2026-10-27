<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Http\Controllers\DuitkuCallbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    public function simulateWebhook($orderId)
    {
        $order = Order::with('user')->findOrFail($orderId);

        if ($order->isPaid()) {
            session()->flash('error', 'Pesanan ini sudah berstatus PAID sebelumnya.');
            return;
        }

        $merchantCode = (string) config('services.duitku.merchant_code');
        $apiKey = (string) config('services.duitku.api_key');
        $amount = (string) $order->amount;
        $signature = hash_hmac('sha256', $merchantCode.$amount.$order->order_id, $apiKey);

        $payload = [
            'merchantCode' => $merchantCode,
            'amount' => $amount,
            'merchantOrderId' => $order->order_id,
            'productDetail' => "Kuota {$order->quota_count} Pohon Silsilah",
            'additionalParam' => (string) $order->user_id,
            'paymentCode' => 'QR',
            'resultCode' => '00',
            'merchantUserId' => (string) $order->user_id,
            'reference' => $order->reference ?? 'SIM-'.Str::random(10),
            'signature' => $signature,
        ];

        $request = Request::create('/api/duitku/callback', 'POST', $payload);
        $response = app(DuitkuCallbackController::class)->handle($request);

        if ($response->getStatusCode() === 200) {
            session()->flash('success', "🧪 Simulasi Webhook Sukses! Signature Duitku terverifikasi sah, pesanan {$order->order_id} otomatis berubah menjadi PAID, dan kuota +{$order->quota_count} berhasil ditambahkan ke akun {$order->user->name}.");
        } else {
            session()->flash('error', 'Simulasi Webhook Gagal: '.$response->getContent());
        }
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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-200/80 dark:border-zinc-800">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300 border border-zinc-200/80 dark:border-zinc-700/80 mb-2">
                <span class="material-symbols-outlined text-[14px] text-emerald-600 dark:text-emerald-400">point_of_sale</span>
                <span>Gateway Transaksi</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                Manajemen Transaksi Duitku
            </h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Audit riwayat pesanan kuota, pantau status pembayaran QRIS, dan lakukan verifikasi manual jika terdapat kendala webhook.
            </p>
        </div>

        <!-- Search Bar -->
        <div class="w-full sm:w-80">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari Order ID, customer, email..." icon="magnifying-glass" clearable />
        </div>
    </div>

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2 shadow-xs">
            <span class="material-symbols-outlined text-emerald-600 text-lg">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-800 dark:bg-red-950/40 dark:border-red-800 dark:text-red-300 text-xs font-semibold flex items-center gap-2 shadow-xs">
            <span class="material-symbols-outlined text-red-600 text-lg">error</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
        <button wire:click="$set('statusFilter', 'all')"
                class="px-3 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Semua</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'all' ? 'bg-white/20 dark:bg-black/20' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['all'] }}</span>
        </button>

        <button wire:click="$set('statusFilter', 'paid')"
                class="px-3 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'paid' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Paid (Berhasil)</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'paid' ? 'bg-emerald-700 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['paid'] }}</span>
        </button>

        <button wire:click="$set('statusFilter', 'pending')"
                class="px-3 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Pending</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'pending' ? 'bg-amber-700 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['pending'] }}</span>
        </button>

        <button wire:click="$set('statusFilter', 'expired')"
                class="px-3 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $statusFilter === 'expired' ? 'bg-zinc-700 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Expired</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $statusFilter === 'expired' ? 'bg-zinc-800 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['expired'] }}</span>
        </button>
    </div>

    <!-- Orders Table -->
    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-5">ID Pesanan</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Nominal</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4">Waktu</th>
                        <th class="py-3.5 px-5 text-right">Aksi Bantuan (Support)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @forelse($orders as $order)
                        <tr class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="py-3.5 px-5">
                                <p class="font-bold text-zinc-900 dark:text-white font-mono text-xs">{{ $order->order_id }}</p>
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
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                        <span class="material-symbols-outlined text-[13px] leading-none">check</span>
                                        <span>PAID</span>
                                    </span>
                                @elseif($order->status === 'pending')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>PENDING</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                                        {{ strtoupper($order->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-zinc-500 dark:text-zinc-400 text-[11px]">
                                {{ $order->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                                <span class="block text-[10px] text-zinc-400">{{ $order->created_at?->diffForHumans() }}</span>
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                @if(! $order->isPaid())
                                    <div class="flex items-center justify-end gap-1.5 flex-nowrap">
                                        <!-- Button 1: Simulasi Webhook -->
                                        <button wire:click="simulateWebhook({{ $order->id }})"
                                                wire:confirm="Simulasikan kiriman Webhook Callback Duitku (QRIS Sukses) untuk pesanan ini?"
                                                title="Simulasikan Webhook Duitku (Uji Coba Integrasi)"
                                                class="inline-flex items-center justify-center gap-1 h-7 px-2.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-900 dark:bg-amber-950/70 dark:hover:bg-amber-900 dark:text-amber-300 border border-amber-300/80 dark:border-amber-700/80 text-[11px] font-bold transition-all shrink-0">
                                            <span class="material-symbols-outlined text-[14px]">science</span>
                                            <span>Simulasi Webhook</span>
                                        </button>

                                        <!-- Button 2: Verifikasi Manual -->
                                        <button wire:click="manualVerifyOrder({{ $order->id }})"
                                                wire:confirm="Konfirmasi manual pesanan ini dan otomatis tambahkan kuota pohon ke pengguna?"
                                                title="Konfirmasi status bayar & tambah kuota langsung"
                                                class="inline-flex items-center justify-center gap-1 h-7 px-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white border border-emerald-600 text-[11px] font-bold shadow-xs transition-all shrink-0">
                                            <span class="material-symbols-outlined text-[14px]">verified</span>
                                            <span>Verifikasi Manual</span>
                                        </button>

                                        <!-- Button 3: Expired -->
                                        @if($order->status === 'pending')
                                            <button wire:click="markAsExpired({{ $order->id }})"
                                                    wire:confirm="Tandai pesanan ini sebagai EXPIRED?"
                                                    title="Tandai pesanan kedaluwarsa"
                                                    class="inline-flex items-center justify-center gap-1 h-7 px-2 rounded-lg bg-zinc-100 hover:bg-red-50 text-zinc-600 hover:text-red-600 dark:bg-zinc-800 dark:hover:bg-red-950/40 dark:text-zinc-400 dark:hover:text-red-400 border border-zinc-200 dark:border-zinc-700 text-[11px] font-semibold transition-all shrink-0">
                                                <span class="material-symbols-outlined text-[13px]">close</span>
                                                <span>Expired</span>
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex items-center justify-end gap-1.5 h-7 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                        <span class="material-symbols-outlined text-base">check_circle</span>
                                        <span>Tuntas (PAID)</span>
                                    </div>
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
