<x-layouts::app :title="'Kalender Agenda — ' . $tree->name">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">
        {{-- Breadcrumb & Back to Tree --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center gap-3">
                <flux:button href="{{ route('tree.show', $tree->slug ?: $tree->id) }}" icon="arrow-left" size="sm" variant="subtle">
                    Kembali ke Silsilah
                </flux:button>
                <div class="h-5 w-px bg-zinc-200 dark:bg-zinc-700 hidden sm:block"></div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white flex items-center gap-2">
                        <span>🗓️</span>
                        <span>Kalender Agenda — {{ $tree->name }}</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Jadwal bulanan haul, ulang tahun, dan hari ulang tahun pernikahan seluruh anggota keluarga.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <flux:button href="{{ route('tree.show', $tree->slug ?: $tree->id) }}" icon="chart-bar" size="sm" variant="outline">
                    Bagan Pohon
                </flux:button>
            </div>
        </div>

        {{-- Interactive Monthly Calendar Grid --}}
        <div class="bg-white dark:bg-zinc-900 rounded-3xl p-4 sm:p-7 border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
            <livewire:family-reminders :tree-id="$tree->id" :as-modal="false" :view-mode="'calendar'" />
        </div>
    </div>
</x-layouts::app>
