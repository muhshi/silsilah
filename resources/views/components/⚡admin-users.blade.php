<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $targetUserId = null;
    public $targetUserName = '';
    public $newQuota = 1;
    public $reason = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setTargetUser($userId)
    {
        $user = User::findOrFail($userId);
        $this->targetUserId = $user->id;
        $this->targetUserName = $user->name;
        $this->newQuota = $user->tree_quota ?? 1;
        $this->reason = '';

        Flux::modal('adjust-quota-modal')->show();
    }

    public function saveQuota()
    {
        $this->validate([
            'targetUserId' => 'required|exists:users,id',
            'newQuota' => 'required|integer|min:0|max:1000',
        ]);

        $user = User::findOrFail($this->targetUserId);
        $oldQuota = $user->tree_quota ?? 1;
        $user->update(['tree_quota' => $this->newQuota]);

        Log::info('Admin Adjusted Tree Quota', [
            'admin_id' => auth()->id(),
            'user_id' => $user->id,
            'old_quota' => $oldQuota,
            'new_quota' => $this->newQuota,
            'reason' => $this->reason,
        ]);

        session()->flash('success', "Kuota pohon untuk {$user->name} berhasil diubah menjadi {$this->newQuota}.");
        Flux::modal('adjust-quota-modal')->close();
    }

    public function quickAddQuota($userId, $amount = 1)
    {
        $user = User::findOrFail($userId);
        $oldQuota = $user->tree_quota ?? 1;
        $user->increment('tree_quota', $amount);

        Log::info('Admin Quick Added Tree Quota', [
            'admin_id' => auth()->id(),
            'user_id' => $user->id,
            'old_quota' => $oldQuota,
            'added' => $amount,
            'new_quota' => $user->tree_quota,
        ]);

        session()->flash('success', "Berhasil menambahkan +{$amount} kuota pohon untuk {$user->name}. Total kuota sekarang: {$user->tree_quota}.");
    }

    public function toggleAdminRole($userId)
    {
        if ($userId === auth()->id()) {
            session()->flash('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
            return;
        }

        $user = User::findOrFail($userId);
        $newRole = $user->role === 'admin' ? 'user' : 'admin';
        $user->update(['role' => $newRole]);

        session()->flash('success', "Role untuk {$user->name} diubah menjadi {$newRole}.");
    }

    public function with()
    {
        $query = User::withCount('familyTrees')->latest();

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $users = $query->paginate(15);

        return [
            'users' => $users,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-200/80 dark:border-zinc-800/80">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-sky-50 text-sky-800 dark:bg-sky-950/80 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/60 mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                Manajemen Pengguna
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                Direktori Pengguna & Hak Akses
            </h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola akun pengguna, pantau jumlah pohon, dan berikan kuota pohon secara manual.
            </p>
        </div>

        <!-- Search Bar -->
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email..." icon="magnifying-glass" clearable />
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

    <!-- Table Card -->
    <div class="bg-white dark:bg-zinc-800/80 rounded-3xl border border-zinc-200/80 dark:border-zinc-700/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200/80 dark:border-zinc-700/60 bg-zinc-50/50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-5">Pengguna</th>
                        <th class="py-3.5 px-4">Role</th>
                        <th class="py-3.5 px-4 text-center">Pohon Dibuat</th>
                        <th class="py-3.5 px-4 text-center">Kuota Pohon</th>
                        <th class="py-3.5 px-4">Terdaftar</th>
                        <th class="py-3.5 px-5 text-right">Aksi Kuota & Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    @forelse($users as $user)
                        <tr class="hover:bg-zinc-50/80 dark:hover:bg-zinc-700/20 transition-colors">
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    @if($user->avatar)
                                        <img src="{{ $user->avatar }}" class="w-8 h-8 rounded-full object-cover border border-zinc-200 dark:border-zinc-700 shrink-0" alt="">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ $user->initials() }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-bold text-zinc-900 dark:text-white truncate">{{ $user->name }}</p>
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($user->role === 'admin')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        ADMIN
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                        USER
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-zinc-700 dark:text-zinc-300">
                                {{ $user->family_trees_count }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-lg text-xs font-bold {{ ($user->tree_quota ?? 1) > 1 ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                    {{ $user->tree_quota ?? 1 }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-zinc-500 dark:text-zinc-400 text-[11px]">
                                {{ $user->created_at?->translatedFormat('d M Y') ?? '-' }}
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5 flex-nowrap">
                                    <!-- Quick Add +1 Quota -->
                                    <button wire:click="quickAddQuota({{ $user->id }}, 1)"
                                            title="Tambah +1 Kuota Langsung"
                                            class="inline-flex items-center justify-center gap-1 h-7 px-2.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:hover:bg-emerald-900 dark:text-emerald-300 font-bold text-xs border border-emerald-200 dark:border-emerald-800 transition-all shrink-0">
                                        <span class="material-symbols-outlined text-sm">add</span>
                                        <span>+1 Kuota</span>
                                    </button>

                                    <!-- Custom Adjust Quota Modal Button -->
                                    <button wire:click="setTargetUser({{ $user->id }})"
                                            title="Atur kuota khusus"
                                            class="inline-flex items-center justify-center gap-1 h-7 px-2.5 rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 font-semibold text-xs border border-zinc-200/80 dark:border-zinc-700 transition-all shrink-0">
                                        <span class="material-symbols-outlined text-sm">tune</span>
                                        <span>Atur</span>
                                    </button>

                                    <!-- Toggle Admin Role Button -->
                                    @if($user->id !== auth()->id())
                                        <button wire:click="toggleAdminRole({{ $user->id }})"
                                                wire:confirm="Yakin ingin mengubah peran pengguna ini?"
                                                class="inline-flex items-center justify-center h-7 px-2 rounded-lg text-xs font-semibold text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors shrink-0"
                                                title="Ubah Role Admin/User">
                                            {{ $user->role === 'admin' ? 'Jadikan User' : 'Jadikan Admin' }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                Tidak ada data pengguna yang sesuai dengan pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-zinc-200/80 dark:border-zinc-700/60">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form Adjust Quota -->
    <flux:modal name="adjust-quota-modal" class="md:w-[28rem]">
        <form wire:submit="saveQuota" class="space-y-5">
            <div>
                <flux:heading size="lg">Sesuaikan Kuota Pohon</flux:heading>
                <flux:subheading class="mt-1">
                    Ubah jumlah kuota pohon untuk <strong>{{ $targetUserName }}</strong>.
                </flux:subheading>
            </div>

            <!-- Quick Add Shortcut Buttons inside Modal -->
            <div>
                <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2">Pilihan Cepat Tambah Kuota:</label>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="$set('newQuota', {{ $newQuota + 1 }})" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition-all">
                        +1 Kuota
                    </button>
                    <button type="button" wire:click="$set('newQuota', {{ $newQuota + 2 }})" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition-all">
                        +2 Kuota
                    </button>
                    <button type="button" wire:click="$set('newQuota', {{ $newQuota + 5 }})" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition-all">
                        +5 Kuota
                    </button>
                </div>
            </div>

            <div class="space-y-4">
                <flux:input type="number" wire:model="newQuota" label="Jumlah Total Kuota Pohon" min="0" max="1000" required />
                <flux:textarea wire:model="reason" label="Alasan / Catatan (Opsional)" placeholder="Misal: Kompensasi pembayaran QRIS gagal webhook Duitku" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="!bg-amber-600 !text-white hover:!bg-amber-700">
                    Simpan Perubahan
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
