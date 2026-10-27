<?php

use Livewire\Component;
use App\Models\PricingPackage;
use App\Models\AdminActivityLog;
use Flux\Flux;

new class extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public int $tree_quota = 1;
    public int $price = 10000;
    public ?int $original_price = null;
    public string $badge = '';
    public string $description = '';
    public bool $is_one_time_per_user = false;
    public bool $is_active = true;
    public ?string $starts_at = null;
    public ?string $ends_at = null;

    public function toggleActive(int $id): void
    {
        $package = PricingPackage::findOrFail($id);
        $package->is_active = ! $package->is_active;
        $package->save();

        AdminActivityLog::log(
            'toggle_package_status',
            "Admin " . auth()->user()->name . " mengubah status paket '{$package->name}' menjadi " . ($package->is_active ? 'Aktif' : 'Nonaktif'),
            PricingPackage::class,
            $package->id,
            ['is_active' => $package->is_active]
        );

        Flux::toast(
            text: "Status paket '{$package->name}' berhasil diperbarui.",
            variant: 'success'
        );
    }

    public function editPackage(int $id): void
    {
        $package = PricingPackage::findOrFail($id);
        $this->editingId = $package->id;
        $this->name = $package->name;
        $this->tree_quota = $package->tree_quota;
        $this->price = $package->price;
        $this->original_price = $package->original_price;
        $this->badge = $package->badge ?? '';
        $this->description = $package->description ?? '';
        $this->is_one_time_per_user = $package->is_one_time_per_user;
        $this->is_active = $package->is_active;
        $this->starts_at = $package->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $package->ends_at?->format('Y-m-d\TH:i');

        Flux::modal('edit-package-modal')->show();
    }

    public function setRamadanPreset(int $year = 2026): void
    {
        // Preset bulan puasa hingga lebaran (pertengahan Feb - pertengahan Apr)
        $this->starts_at = "{$year}-02-15T00:00";
        $this->ends_at = "{$year}-04-15T23:59";
    }

    public function clearSchedule(): void
    {
        $this->starts_at = null;
        $this->ends_at = null;
    }

    public function updatePackage(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'tree_quota' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'original_price' => 'nullable|integer|min:0',
            'badge' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_one_time_per_user' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $package = PricingPackage::findOrFail($this->editingId);
        $package->update([
            'name' => $this->name,
            'tree_quota' => $this->tree_quota,
            'price' => $this->price,
            'original_price' => $this->original_price ?: null,
            'badge' => $this->badge ? trim($this->badge) : null,
            'description' => $this->description ? trim($this->description) : null,
            'is_one_time_per_user' => $this->is_one_time_per_user,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,
        ]);

        AdminActivityLog::log(
            'update_pricing_package',
            "Admin " . auth()->user()->name . " memperbarui detail paket '{$package->name}'",
            PricingPackage::class,
            $package->id,
            [
                'price' => $package->price,
                'tree_quota' => $package->tree_quota,
                'starts_at' => $package->starts_at,
                'ends_at' => $package->ends_at,
            ]
        );

        Flux::modal('edit-package-modal')->close();
        Flux::toast(text: "Paket '{$package->name}' berhasil diperbarui.", variant: 'success');
    }

    public function with(): array
    {
        $packages = PricingPackage::orderBy('sort_order')->get();

        return [
            'packages' => $packages,
            'totalPackages' => $packages->count(),
            'activePackages' => $packages->where('is_active', true)->count(),
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/40">
                    Monetisasi
                </span>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Total: {{ $totalPackages }} Paket ({{ $activePackages }} Aktif)</span>
            </div>
            <h1 class="text-2xl font-headline font-extrabold text-zinc-900 dark:text-white tracking-tight mt-1">
                Paket & Harga Kuota
            </h1>
            <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 mt-0.5">
                Kelola paket pembelian kuota pohon silsilah, promo musiman, dan nominal pembayaran QRIS Duitku.
            </p>
        </div>
    </div>

    <!-- Pricing Cards / Table -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[840px] text-left text-xs">
                <thead class="bg-zinc-50/75 dark:bg-zinc-800/50 border-b border-zinc-200/70 dark:border-zinc-800 text-[11px] font-bold text-zinc-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Nama Paket & Info</th>
                        <th class="py-3 px-4">Kuota Tambahan</th>
                        <th class="py-3 px-4">Harga Normal / Promo</th>
                        <th class="py-3 px-4">Batasan Pembelian</th>
                        <th class="py-3 px-4">Periode Promo</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                    @forelse($packages as $pkg)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <!-- Paket Name & Badge -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🌳</span>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-zinc-900 dark:text-white">{{ $pkg->name }}</span>
                                            @if($pkg->badge)
                                                <span class="px-2 py-0.2 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/40">
                                                    {{ $pkg->badge }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 max-w-sm line-clamp-1">
                                            {{ $pkg->description }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Tree Quota -->
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1 font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-1 rounded-lg border border-emerald-200/60 dark:border-emerald-800/60 text-xs">
                                    +{{ $pkg->tree_quota }} Pohon
                                </span>
                            </td>

                            <!-- Price -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-baseline gap-1.5">
                                    <span class="font-extrabold text-zinc-900 dark:text-white text-sm">
                                        Rp {{ number_format($pkg->price, 0, ',', '.') }}
                                    </span>
                                    @if($pkg->original_price)
                                        <span class="text-[10px] text-zinc-400 line-through">
                                            Rp {{ number_format($pkg->original_price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Limitation -->
                            <td class="py-3.5 px-4">
                                @if($pkg->is_one_time_per_user)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800/60">
                                        <span class="material-symbols-outlined text-xs">lock</span>
                                        Maks 1x per User
                                    </span>
                                @else
                                    <span class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                        Tanpa Batas
                                    </span>
                                @endif
                            </td>

                            <!-- Periode Promo -->
                            <td class="py-3.5 px-4">
                                @if($pkg->starts_at || $pkg->ends_at)
                                    <div class="space-y-0.5">
                                        @if($pkg->isAvailableNow())
                                            <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                <span>Sedang Berjalan</span>
                                            </span>
                                        @elseif($pkg->ends_at && $pkg->ends_at->isPast())
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                                <span class="material-symbols-outlined text-[12px]">history</span>
                                                <span>Kedaluwarsa</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                <span class="material-symbols-outlined text-[12px]">schedule</span>
                                                <span>Akan Datang</span>
                                            </span>
                                        @endif
                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-mono">
                                            {{ $pkg->starts_at ? $pkg->starts_at->translatedFormat('d M Y') : 'Awal' }} - {{ $pkg->ends_at ? $pkg->ends_at->translatedFormat('d M Y') : 'Seterusnya' }}
                                        </p>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                                        <span class="material-symbols-outlined text-xs text-zinc-400">all_inclusive</span>
                                        Sepanjang Tahun
                                    </span>
                                @endif
                            </td>

                            <!-- Active Toggle -->
                            <td class="py-3.5 px-4 text-center">
                                <button wire:click="toggleActive({{ $pkg->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold cursor-pointer transition-all
                                        {{ $pkg->is_active
                                             ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/60 hover:bg-emerald-200'
                                             : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $pkg->is_active ? 'bg-emerald-600 animate-pulse' : 'bg-zinc-400' }}"></span>
                                    {{ $pkg->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right">
                                <button wire:click="editPackage({{ $pkg->id }})"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 cursor-pointer transition-all">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                    Ubah
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-zinc-500">
                                Belum ada data paket kuota.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Package Modal -->
    <flux:modal name="edit-package-modal" class="md:w-[32rem]">
        <form wire:submit="updatePackage" class="space-y-4">
            <div>
                <flux:heading size="lg">Ubah Paket Kuota</flux:heading>
                <flux:subheading>Sesuaikan harga, kuota pohon, jadwal, dan deskripsi paket.</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Nama Paket" placeholder="Misal: Promo Idul Fitri" required />

            <div class="grid grid-cols-2 gap-3">
                <flux:input type="number" wire:model="tree_quota" label="Kuota Pohon" min="1" required />
                <flux:input type="number" wire:model="price" label="Harga (Rp)" min="0" step="1000" required />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:input type="number" wire:model="original_price" label="Harga Coret (Rp - Opsional)" min="0" step="1000" />
                <flux:input wire:model="badge" label="Badge Teks (Opsional)" placeholder="Misal: POPULER" />
            </div>

            <!-- Jadwal Promo Musiman Section -->
            <div class="p-3.5 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-700/80 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-zinc-800 dark:text-zinc-200">
                        🗓️ Jadwal / Periode Musiman (Opsional)
                    </label>
                    <div class="flex items-center gap-1.5">
                        <button type="button" wire:click="setRamadanPreset(2026)" class="text-[10px] font-bold px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 transition-colors cursor-pointer">
                            🌙 Puasa & Lebaran 2026
                        </button>
                        <button type="button" wire:click="clearSchedule" class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-zinc-200 text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer">
                            Reset
                        </button>
                    </div>
                </div>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                    Kosongkan jika paket aktif sepanjang tahun. Jika diisi, paket hanya akan muncul bagi user di rentang tanggal ini.
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <flux:input type="datetime-local" wire:model="starts_at" label="Mulai Berlaku" />
                    <flux:input type="datetime-local" wire:model="ends_at" label="Berakhir Pada" />
                </div>
            </div>

            <flux:textarea wire:model="description" label="Deskripsi Singkat" rows="2" />

            <div class="space-y-2 pt-1">
                <flux:checkbox wire:model="is_one_time_per_user" label="Batasi Maksimal 1x Beli per Akun User (Cocok untuk Promo BOGO)" />
                <flux:checkbox wire:model="is_active" label="Aktifkan Paket (Muncul di Modal Pilihan Kuota User)" />
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 cursor-pointer">
                    Simpan Perubahan
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
