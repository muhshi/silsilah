<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <flux:heading size="lg">Agenda & Pengingat Keluarga</flux:heading>
            <flux:subheading>Pengingat Haul, Ulang Tahun, dan Ulang Tahun Pernikahan trah keluarga.</flux:subheading>
        </div>
    </div>

    {{-- Calendar Sync Banner / Card --}}
    <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-emerald-50 via-teal-50 to-emerald-100/60 dark:from-emerald-950/40 dark:via-zinc-900 dark:to-teal-950/30 border border-emerald-200 dark:border-emerald-800/60 shadow-sm space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <flux:icon name="calendar" class="w-5 h-5" />
                </div>
                <div>
                    <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Langganan Kalender Otomatis</h4>
                    <p class="text-xs text-zinc-600 dark:text-zinc-300 leading-relaxed mt-0.5">
                        Sinkronkan ke Google Calendar, Apple Calendar, atau kalender ponsel Anda. Jadwal akan otomatis berulang setiap tahun!
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-1 sm:pt-0 shrink-0">
                <flux:button href="{{ $this->webcalFeedUrl }}" icon="calendar" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 !font-bold text-xs shadow-sm">
                    Langganan Kalender
                </flux:button>

                <div x-data="{ copied: false }">
                    <flux:button
                        type="button"
                        x-on:click="navigator.clipboard.writeText(@js($this->calendarFeedUrl)); copied = true; setTimeout(() => copied = false, 2500)"
                        icon="link"
                        size="sm"
                        variant="subtle"
                        class="text-xs font-semibold">
                        <span x-show="!copied">Salin Link .ICS</span>
                        <span x-show="copied" class="text-emerald-600 dark:text-emerald-400 font-bold">✓ Tersalin!</span>
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    {{-- Controls: Filter Tabs & Days Window --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
        {{-- Filter Buttons --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
            <button
                type="button"
                wire:click="setFilter('all')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 {{ $filterType === 'all' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-sm' : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                <span>Semua</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterType === 'all' ? 'bg-zinc-700 text-zinc-200 dark:bg-zinc-200 dark:text-zinc-800' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">{{ $this->counts['all'] }}</span>
            </button>

            <button
                type="button"
                wire:click="setFilter('haul')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 {{ $filterType === 'haul' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                <span>Haul (Wafat)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterType === 'haul' ? 'bg-indigo-700 text-indigo-100' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">{{ $this->counts['haul'] }}</span>
            </button>

            <button
                type="button"
                wire:click="setFilter('birthday')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 {{ $filterType === 'birthday' ? 'bg-amber-600 text-white shadow-sm' : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                <span>Ulang Tahun</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterType === 'birthday' ? 'bg-amber-700 text-amber-100' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">{{ $this->counts['birthday'] }}</span>
            </button>

            <button
                type="button"
                wire:click="setFilter('marriage')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 {{ $filterType === 'marriage' ? 'bg-rose-600 text-white shadow-sm' : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                <span>Pernikahan</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterType === 'marriage' ? 'bg-rose-700 text-rose-100' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">{{ $this->counts['marriage'] }}</span>
            </button>
        </div>

        {{-- Days Ahead Window Buttons --}}
        <div class="flex items-center gap-1 self-end sm:self-auto bg-zinc-100 dark:bg-zinc-800 p-1 rounded-xl">
            <span class="text-[11px] font-bold text-zinc-400 dark:text-zinc-500 px-2">Rentang:</span>
            @foreach([30, 60, 90] as $days)
                <button
                    type="button"
                    wire:click="setDaysAhead({{ $days }})"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer {{ $daysAhead === $days ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    {{ $days }} Hari
                </button>
            @endforeach
        </div>
    </div>

    {{-- Events List --}}
    <div class="space-y-3">
        @forelse($this->events as $event)
            <div class="p-4 rounded-2xl bg-white dark:bg-zinc-800/80 border border-zinc-200/90 dark:border-zinc-700/80 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-600 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                {{-- Left: Event Info --}}
                <div class="space-y-1.5 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        {{-- Event Type Badge --}}
                        @if($event['type'] === 'haul')
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 flex items-center gap-1">
                                🕌 {{ $event['ordinal_text'] }}
                            </span>
                        @elseif($event['type'] === 'birthday')
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 flex items-center gap-1">
                                🎂 {{ $event['ordinal_text'] }}
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 flex items-center gap-1">
                                💍 {{ $event['ordinal_text'] }}
                            </span>
                        @endif

                        {{-- Days Until Badge --}}
                        @if($event['days_until'] === 0)
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-rose-500 text-white animate-pulse flex items-center gap-1">
                                ⚡ Hari Ini
                            </span>
                        @elseif($event['days_until'] === 1)
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-500 text-white">
                                Besok
                            </span>
                        @elseif($event['days_until'] === 2)
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                Lusa
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-zinc-100 dark:bg-zinc-700/60 text-zinc-600 dark:text-zinc-300">
                                {{ $event['days_until'] }} hari lagi
                            </span>
                        @endif
                    </div>

                    {{-- Title --}}
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white leading-tight">
                        {{ $event['title'] }}
                    </h3>

                    {{-- Formatted Date & Subtext --}}
                    <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">
                            📅 {{ \Carbon\Carbon::parse($event['date'])->locale('id')->translatedFormat('l, d F Y') }}
                        </span>
                        <span>•</span>
                        <span class="truncate max-w-xs sm:max-w-md">
                            {{ $event['description'] }}
                        </span>
                    </div>
                </div>

                {{-- Right: Actions (WhatsApp Sharer & Copy Text) --}}
                <div class="flex items-center gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-zinc-100 dark:border-zinc-800">
                    {{-- 1-Click WhatsApp Button --}}
                    <a
                        href="{{ $event['wa_share_url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        title="Kirim ke WhatsApp (Grup Keluarga)"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#25D366] hover:bg-[#20ba59] active:bg-[#1caa4f] text-white text-xs font-bold transition-all shadow-sm">
                        <span class="text-sm leading-none">💬</span>
                        <span>Kirim ke WhatsApp</span>
                    </a>

                    {{-- Copy Message Button --}}
                    <div x-data="{ copied: false }">
                        <button
                            type="button"
                            x-on:click="navigator.clipboard.writeText(@js($event['whatsapp_message'])); copied = true; setTimeout(() => copied = false, 2500)"
                            title="Salin Teks Doa / Pesan Siaran"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-semibold transition-all">
                            <span x-show="!copied">📋 Salin Teks</span>
                            <span x-show="copied" class="text-emerald-600 dark:text-emerald-400 font-bold">✓ Tersalin!</span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-8 rounded-2xl bg-zinc-50 dark:bg-zinc-800/40 border border-dashed border-zinc-300 dark:border-zinc-700 text-center space-y-3">
                <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-400 flex items-center justify-center">
                    <flux:icon name="calendar" class="w-6 h-6" />
                </div>
                <div>
                    <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Tidak Ada Agenda Terdekat</h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-md mx-auto">
                        Tidak ada haul, ulang tahun, atau hari pernikahan dalam {{ $daysAhead }} hari ke depan untuk kategori terpilih.
                    </p>
                </div>
                @if($daysAhead < 90)
                    <div class="pt-1">
                        <flux:button type="button" wire:click="setDaysAhead(90)" size="sm" variant="subtle" class="text-xs font-semibold">
                            Cari hingga 90 hari ke depan &rarr;
                        </flux:button>
                    </div>
                @endif
            </div>
        @endforelse
    </div>
</div>
