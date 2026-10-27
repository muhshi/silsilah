<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Voucher;
use App\Models\AdminActivityLog;
use Flux\Flux;
use Illuminate\Support\Str;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;

    // Form fields
    public string $code = '';
    public string $name = '';
    public string $type = 'percentage';
    public int $value = 15;
    public int $min_amount = 0;
    public ?int $max_discount = null;
    public ?int $usage_limit = 100;
    public int $user_limit = 1;
    public ?string $starts_at = null;
    public ?string $expires_at = null;
    public bool $is_active = true;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        Flux::modal('voucher-modal')->show();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->code = 'DISKON' . strtoupper(Str::random(4));
        $this->name = '';
        $this->type = 'percentage';
        $this->value = 15;
        $this->min_amount = 0;
        $this->max_discount = null;
        $this->usage_limit = 100;
        $this->user_limit = 1;
        $this->starts_at = null;
        $this->expires_at = null;
        $this->is_active = true;
    }

    public function presetRamadan15(): void
    {
        $this->code = 'LEBARAN15';
        $this->name = 'Promo Spesial Lebaran Diskon 15%';
        $this->type = 'percentage';
        $this->value = 15;
        $this->min_amount = 10000;
        $this->max_discount = 10000;
        $this->usage_limit = 200;
        $this->user_limit = 1;
        $this->starts_at = '2026-02-15T00:00';
        $this->expires_at = '2026-04-15T23:59';
        $this->is_active = true;
    }

    public function editVoucher(int $id): void
    {
        $voucher = Voucher::findOrFail($id);
        $this->editingId = $voucher->id;
        $this->code = $voucher->code;
        $this->name = $voucher->name ?? '';
        $this->type = $voucher->type;
        $this->value = $voucher->value;
        $this->min_amount = $voucher->min_amount;
        $this->max_discount = $voucher->max_discount;
        $this->usage_limit = $voucher->usage_limit;
        $this->user_limit = $voucher->user_limit;
        $this->starts_at = $voucher->starts_at?->format('Y-m-d\TH:i');
        $this->expires_at = $voucher->expires_at?->format('Y-m-d\TH:i');
        $this->is_active = $voucher->is_active;

        Flux::modal('voucher-modal')->show();
    }

    public function saveVoucher(): void
    {
        $this->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . ($this->editingId ?? 'NULL') . ',id',
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|integer|min:1',
            'min_amount' => 'required|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'user_limit' => 'required|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $codeFormatted = strtoupper(trim($this->code));

        if ($this->editingId) {
            $voucher = Voucher::findOrFail($this->editingId);
            $voucher->update([
                'code' => $codeFormatted,
                'name' => $this->name ? trim($this->name) : null,
                'type' => $this->type,
                'value' => $this->value,
                'min_amount' => $this->min_amount,
                'max_discount' => $this->max_discount ?: null,
                'usage_limit' => $this->usage_limit ?: null,
                'user_limit' => $this->user_limit,
                'starts_at' => $this->starts_at ?: null,
                'expires_at' => $this->expires_at ?: null,
                'is_active' => $this->is_active,
            ]);

            AdminActivityLog::log(
                'update_voucher',
                "Admin " . auth()->user()->name . " memperbarui voucher '{$voucher->code}'",
                Voucher::class,
                $voucher->id,
                ['value' => $voucher->value, 'type' => $voucher->type]
            );

            session()->flash('success', "Voucher '{$voucher->code}' berhasil diperbarui.");
        } else {
            $voucher = Voucher::create([
                'code' => $codeFormatted,
                'name' => $this->name ? trim($this->name) : null,
                'type' => $this->type,
                'value' => $this->value,
                'min_amount' => $this->min_amount,
                'max_discount' => $this->max_discount ?: null,
                'usage_limit' => $this->usage_limit ?: null,
                'user_limit' => $this->user_limit,
                'starts_at' => $this->starts_at ?: null,
                'expires_at' => $this->expires_at ?: null,
                'is_active' => $this->is_active,
            ]);

            AdminActivityLog::log(
                'create_voucher',
                "Admin " . auth()->user()->name . " membuat voucher baru '{$voucher->code}'",
                Voucher::class,
                $voucher->id,
                ['value' => $voucher->value, 'type' => $voucher->type]
            );

            session()->flash('success', "Voucher '{$voucher->code}' berhasil dibuat.");
        }

        Flux::modal('voucher-modal')->close();
    }

    public function toggleActive(int $id): void
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->is_active = ! $voucher->is_active;
        $voucher->save();

        AdminActivityLog::log(
            'toggle_voucher',
            "Admin " . auth()->user()->name . " mengubah status voucher '{$voucher->code}' menjadi " . ($voucher->is_active ? 'Aktif' : 'Nonaktif'),
            Voucher::class,
            $voucher->id,
            ['is_active' => $voucher->is_active]
        );

        Flux::toast(text: "Status voucher '{$voucher->code}' berhasil diubah.", variant: 'success');
    }

    public function deleteVoucher(int $id): void
    {
        $voucher = Voucher::findOrFail($id);
        $code = $voucher->code;
        $voucher->delete();

        AdminActivityLog::log(
            'delete_voucher',
            "Admin " . auth()->user()->name . " menghapus voucher '{$code}'",
            Voucher::class,
            $id
        );

        session()->flash('success', "Voucher '{$code}' berhasil dihapus.");
    }

    public function with(): array
    {
        $query = Voucher::latest();

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%");
            });
        }

        return [
            'vouchers' => $query->paginate(12),
            'totalVouchers' => Voucher::count(),
            'activeVouchers' => Voucher::where('is_active', true)->count(),
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-200/80 dark:border-zinc-800">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-50 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200/80 dark:border-purple-800/80 mb-2">
                <span class="material-symbols-outlined text-[14px]">confirmation_number</span>
                <span>Promosi & Diskon</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                Voucher & Kode Diskon
            </h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola kode voucher promo, persentase diskon pembelian kuota, kuota penggunaan, dan masa berlaku.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full sm:w-auto">
            <button wire:click="openCreateModal"
                    class="h-9 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5 shrink-0 cursor-pointer">
                <span class="material-symbols-outlined text-base">add</span>
                <span>Tambah Voucher</span>
            </button>
            <div class="w-full sm:w-64">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari kode atau nama voucher..." icon="magnifying-glass" clearable />
            </div>
        </div>
    </div>

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2 shadow-xs">
            <span class="material-symbols-outlined text-emerald-600 text-lg">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Container -->
    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs overflow-hidden">
        <!-- 1. Mobile Cards View -->
        <div class="block md:hidden divide-y divide-zinc-100 dark:divide-zinc-800/80">
            @forelse($vouchers as $v)
                <div class="p-4 space-y-3 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-bold text-zinc-900 dark:text-white font-mono text-sm tracking-wider">{{ $v->code }}</span>
                            @if($v->name)
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $v->name }}</p>
                            @endif
                        </div>
                        <div class="shrink-0">
                            <button wire:click="toggleActive({{ $v->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition-all
                                    {{ $v->is_active
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/60'
                                        : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-700' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $v->is_active ? 'bg-emerald-600 animate-pulse' : 'bg-zinc-400' }}"></span>
                                {{ $v->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-1 border-t border-zinc-100 dark:border-zinc-800/50">
                        <div>
                            <span class="text-[10px] text-zinc-400 block">Potongan:</span>
                            <span class="font-extrabold text-emerald-700 dark:text-emerald-400 text-sm">
                                {{ $v->type === 'percentage' ? "{$v->value}%" : 'Rp ' . number_format($v->value, 0, ',', '.') }}
                            </span>
                            @if($v->max_discount)
                                <span class="text-[10px] text-zinc-400 block">(Maks Rp {{ number_format($v->max_discount, 0, ',', '.') }})</span>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-zinc-400 block">Pemakaian:</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-200">
                                {{ $v->used_count }} / {{ $v->usage_limit ?? '∞' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-zinc-500 pt-1">
                        <span class="text-[10px]">
                            Periode: {{ $v->starts_at ? $v->starts_at->format('d/m/y') : 'Sekarang' }} - {{ $v->expires_at ? $v->expires_at->format('d/m/y') : 'Seterusnya' }}
                        </span>
                        <div class="flex items-center gap-1.5">
                            <button wire:click="editVoucher({{ $v->id }})" class="px-2.5 py-1 rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-xs font-semibold text-zinc-800 dark:text-zinc-200">Ubah</button>
                            <button wire:click="deleteVoucher({{ $v->id }})" wire:confirm="Hapus voucher ini?" class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-xs font-semibold text-red-600">Hapus</button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-xs text-zinc-500">Belum ada data voucher promo.</div>
            @endforelse
        </div>

        <!-- 2. Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full min-w-[850px] text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-5">Kode Voucher & Info</th>
                        <th class="py-3.5 px-4">Besaran Diskon</th>
                        <th class="py-3.5 px-4">Minimal Beli</th>
                        <th class="py-3.5 px-4 text-center">Terpakai / Batas</th>
                        <th class="py-3.5 px-4">Periode Berlaku</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @forelse($vouchers as $v)
                        <tr class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🎟️</span>
                                    <div>
                                        <p class="font-extrabold text-zinc-900 dark:text-white font-mono text-sm tracking-wider">{{ $v->code }}</p>
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $v->name ?: 'Voucher Diskon' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-extrabold text-emerald-700 dark:text-emerald-400 text-sm">
                                    {{ $v->type === 'percentage' ? "{$v->value}%" : 'Rp ' . number_format($v->value, 0, ',', '.') }}
                                </span>
                                @if($v->max_discount)
                                    <span class="text-[10px] text-zinc-400 block">(Maks Rp {{ number_format($v->max_discount, 0, ',', '.') }})</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $v->min_amount > 0 ? 'Rp ' . number_format($v->min_amount, 0, ',', '.') : 'Tanpa Minimal' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-lg text-xs font-bold {{ $v->usage_limit && $v->used_count >= $v->usage_limit ? 'bg-red-50 text-red-700' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                    {{ $v->used_count }} / {{ $v->usage_limit ?? '∞' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-[11px] text-zinc-500 dark:text-zinc-400 font-mono">
                                {{ $v->starts_at ? $v->starts_at->translatedFormat('d M Y') : 'Sekarang' }} - {{ $v->expires_at ? $v->expires_at->translatedFormat('d M Y') : 'Seterusnya' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <button wire:click="toggleActive({{ $v->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold cursor-pointer transition-all
                                        {{ $v->is_active
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/60'
                                            : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-700' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $v->is_active ? 'bg-emerald-600 animate-pulse' : 'bg-zinc-400' }}"></span>
                                    {{ $v->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button wire:click="editVoucher({{ $v->id }})" class="px-2.5 py-1 rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-xs font-semibold text-zinc-800 dark:text-zinc-200 transition-all cursor-pointer">
                                        Ubah
                                    </button>
                                    <button wire:click="deleteVoucher({{ $v->id }})" wire:confirm="Yakin ingin menghapus voucher ini?" class="px-2 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-xs font-semibold text-red-600 transition-all cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-xs text-zinc-500">Belum ada data voucher promo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vouchers->hasPages())
            <div class="p-4 border-t border-zinc-200/80 dark:border-zinc-700/60">
                {{ $vouchers->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Voucher Modal -->
    <flux:modal name="voucher-modal" class="md:w-[32rem]">
        <form wire:submit="saveVoucher" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Ubah Voucher Diskon' : 'Buat Voucher Diskon Baru' }}</flux:heading>
                <flux:subheading>Atur kode unik, besaran potongan, dan kuota pemakaian voucher.</flux:subheading>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-xs text-zinc-500">Pilihan Cepat:</span>
                <button type="button" wire:click="presetRamadan15" class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-purple-100 text-purple-800 hover:bg-purple-200 dark:bg-purple-950 dark:text-purple-300 transition-colors cursor-pointer">
                    🌙 Preset Promo Lebaran 15%
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="code" label="Kode Voucher" placeholder="Contoh: SILSILAH15" required class="uppercase font-mono" />
                <flux:input wire:model="name" label="Nama / Judul Promo" placeholder="Misal: Diskon Sambut Lebaran" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Tipe Diskon</label>
                    <select wire:model="type" class="w-full px-3 py-2 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-xs font-semibold">
                        <option value="percentage">Persentase (%)</option>
                        <option value="fixed">Nominal Tetap (Rp)</option>
                    </select>
                </div>
                <flux:input type="number" wire:model="value" label="Nilai Potongan" min="1" required />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:input type="number" wire:model="min_amount" label="Minimal Pembelian (Rp)" min="0" step="1000" />
                <flux:input type="number" wire:model="max_discount" label="Maksimal Potongan (Rp)" min="0" step="1000" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:input type="number" wire:model="usage_limit" label="Batas Kuota Total (Kosongkan jika tak terbatas)" min="1" />
                <flux:input type="number" wire:model="user_limit" label="Batas Pakai per User" min="1" required />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:input type="datetime-local" wire:model="starts_at" label="Mulai Berlaku" />
                <flux:input type="datetime-local" wire:model="expires_at" label="Kedaluwarsa Pada" />
            </div>

            <flux:checkbox wire:model="is_active" label="Aktifkan Voucher Ini Sekarang" />

            <div class="flex justify-end gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 cursor-pointer">
                    Simpan Voucher
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
