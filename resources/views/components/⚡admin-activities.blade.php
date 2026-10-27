<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AdminActivityLog;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $actionFilter = 'all';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingActionFilter()
    {
        $this->resetPage();
    }

    public function with()
    {
        $query = AdminActivityLog::with('admin')->latest();

        if ($this->search) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                  ->orWhere('ip_address', 'like', "%{$term}%")
                  ->orWhere('action', 'like', "%{$term}%")
                  ->orWhereHas('admin', function ($aq) use ($term) {
                      $aq->where('name', 'like', "%{$term}%")
                         ->orWhere('email', 'like', "%{$term}%");
                  });
            });
        }

        if ($this->actionFilter !== 'all') {
            if ($this->actionFilter === 'quota') {
                $query->whereIn('action', ['quota_adjusted', 'quota_quick_added']);
            } elseif ($this->actionFilter === 'orders') {
                $query->whereIn('action', ['order_verified', 'order_expired', 'webhook_simulated']);
            } elseif ($this->actionFilter === 'role') {
                $query->where('action', 'role_changed');
            } elseif ($this->actionFilter === 'system') {
                $query->whereIn('action', ['download_database_backup', 'impersonate_user', 'leave_impersonation', 'toggle_package_status', 'update_pricing_package']);
            } else {
                $query->where('action', $this->actionFilter);
            }
        }

        $logs = $query->paginate(20);

        $counts = [
            'all' => AdminActivityLog::count(),
            'quota' => AdminActivityLog::whereIn('action', ['quota_adjusted', 'quota_quick_added'])->count(),
            'orders' => AdminActivityLog::whereIn('action', ['order_verified', 'order_expired', 'webhook_simulated'])->count(),
            'role' => AdminActivityLog::where('action', 'role_changed')->count(),
            'system' => AdminActivityLog::whereIn('action', ['download_database_backup', 'impersonate_user', 'leave_impersonation', 'toggle_package_status', 'update_pricing_package'])->count(),
        ];

        return [
            'logs' => $logs,
            'counts' => $counts,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-200/80 dark:border-zinc-800/80">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-violet-50 text-violet-800 dark:bg-violet-950/80 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800/60 mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
                Audit Trail Platform
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-white font-headline tracking-tight">
                Log Aktivitas Administrator
            </h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Rekam jejak seluruh tindakan sensitif administrator: perubahan kuota, penyesuaian hak akses, dan intervensi transaksi.
            </p>
        </div>

        <!-- Search Bar & Backup Button -->
        <div class="flex items-center gap-2.5 w-full sm:w-auto">
            <div class="w-full sm:w-64">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari aktivitas, admin, IP..." icon="magnifying-glass" clearable />
            </div>

            <a href="{{ route('admin.backup.download') }}"
               title="Unduh snapshot seluruh database dalam format SQL"
               class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white text-xs font-bold shadow-xs hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer shrink-0">
                <span class="material-symbols-outlined text-sm">download</span>
                <span class="hidden md:inline">Unduh Backup (.sql)</span>
            </a>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
        <button wire:click="$set('actionFilter', 'all')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $actionFilter === 'all' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Semua Aktivitas</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $actionFilter === 'all' ? 'bg-white/20 dark:bg-black/20' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['all'] }}</span>
        </button>

        <button wire:click="$set('actionFilter', 'quota')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $actionFilter === 'quota' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Perubahan Kuota</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $actionFilter === 'quota' ? 'bg-emerald-700 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['quota'] }}</span>
        </button>

        <button wire:click="$set('actionFilter', 'orders')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $actionFilter === 'orders' ? 'bg-amber-600 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Transaksi & Webhook</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $actionFilter === 'orders' ? 'bg-amber-700 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['orders'] }}</span>
        </button>

        <button wire:click="$set('actionFilter', 'role')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $actionFilter === 'role' ? 'bg-violet-600 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Perubahan Role</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $actionFilter === 'role' ? 'bg-violet-700 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['role'] }}</span>
        </button>

        <button wire:click="$set('actionFilter', 'system')"
                class="px-3.5 py-1.5 rounded-xl font-bold transition-all flex items-center gap-1.5 {{ $actionFilter === 'system' ? 'bg-sky-600 text-white shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}">
            <span>Sistem & Backup</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $actionFilter === 'system' ? 'bg-sky-700 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}">{{ $counts['system'] }}</span>
        </button>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-5">Waktu</th>
                        <th class="py-3.5 px-4">Administrator</th>
                        <th class="py-3.5 px-4">Tindakan</th>
                        <th class="py-3.5 px-4">Deskripsi Aktivitas</th>
                        <th class="py-3.5 px-5 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @forelse($logs as $log)
                        <tr class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <p class="font-bold text-zinc-900 dark:text-white">{{ $log->created_at?->translatedFormat('d M Y, H:i:s') ?? '-' }}</p>
                                <p class="text-[10px] text-zinc-400">{{ $log->created_at?->diffForHumans() }}</p>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if($log->admin?->avatar)
                                        <img src="{{ $log->admin->avatar }}" class="w-6 h-6 rounded-full object-cover border border-zinc-200 dark:border-zinc-700 shrink-0" alt="">
                                    @else
                                        <div class="w-6 h-6 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 flex items-center justify-center font-bold text-[10px] shrink-0">
                                            {{ $log->admin?->initials() ?? 'A' }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-bold text-zinc-900 dark:text-white text-xs truncate">{{ $log->admin?->name ?? 'System' }}</p>
                                        <p class="text-[10px] text-zinc-400 truncate">{{ $log->admin?->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if(in_array($log->action, ['quota_adjusted', 'quota_quick_added']))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                        <span class="material-symbols-outlined text-[12px]">add_circle</span>
                                        <span>KUOTA</span>
                                    </span>
                                @elseif($log->action === 'order_verified')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                        <span class="material-symbols-outlined text-[12px]">verified</span>
                                        <span>VERIFIKASI ORDER</span>
                                    </span>
                                @elseif($log->action === 'webhook_simulated')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                        <span class="material-symbols-outlined text-[12px]">science</span>
                                        <span>SIMULASI</span>
                                    </span>
                                @elseif($log->action === 'order_expired')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-red-50 text-red-800 dark:bg-red-950/60 dark:text-red-300 border border-red-200 dark:border-red-800/60">
                                        <span class="material-symbols-outlined text-[12px]">cancel</span>
                                        <span>EXPIRED</span>
                                    </span>
                                @elseif($log->action === 'role_changed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-violet-50 text-violet-800 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800/60">
                                        <span class="material-symbols-outlined text-[12px]">shield_person</span>
                                        <span>ROLE</span>
                                    </span>
                                @elseif($log->action === 'download_database_backup')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-sky-50 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60">
                                        <span class="material-symbols-outlined text-[12px]">database</span>
                                        <span>BACKUP DB</span>
                                    </span>
                                @elseif(in_array($log->action, ['impersonate_user', 'leave_impersonation']))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                        <span class="material-symbols-outlined text-[12px]">supervised_user_circle</span>
                                        <span>IMPERSONASI</span>
                                    </span>
                                @elseif(in_array($log->action, ['toggle_package_status', 'update_pricing_package']))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-teal-50 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300 border border-teal-200 dark:border-teal-800/60">
                                        <span class="material-symbols-outlined text-[12px]">tag</span>
                                        <span>HARGA PAKET</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                                        {{ strtoupper($log->action) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <p class="text-zinc-800 dark:text-zinc-200 font-medium">{{ $log->description }}</p>
                                @if($log->properties && is_array($log->properties))
                                    <div class="mt-1 flex items-center gap-2 flex-wrap">
                                        @foreach($log->properties as $key => $val)
                                            <span class="px-1.5 py-0.2 rounded bg-zinc-100 dark:bg-zinc-800 text-[10px] font-mono text-zinc-500 dark:text-zinc-400">
                                                {{ $key }}: {{ is_array($val) ? json_encode($val) : $val }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono text-[11px] text-zinc-400 whitespace-nowrap">
                                {{ $log->ip_address ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                Belum ada catatan aktivitas administrator.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
