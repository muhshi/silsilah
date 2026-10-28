<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="size-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <flux:icon name="calendar" class="size-5" />
                </div>
                <flux:heading size="lg">Agenda &amp; Pengingat Keluarga</flux:heading>
            </div>
            <flux:subheading>Pengingat Haul, Ulang Tahun, dan Ulang Tahun Pernikahan trah keluarga.</flux:subheading>
        </div>

        {{-- Switcher Mode (Kalender vs Daftar) + Link ke Halaman Penuh jika modal --}}
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <div class="flex items-center bg-zinc-100 dark:bg-zinc-800 p-1 rounded-xl">
                <button
                    type="button"
                    wire:click="setViewMode('calendar')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer {{ $viewMode === 'calendar' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    <span>📅</span>
                    <span>Kalender</span>
                </button>
                <button
                    type="button"
                    wire:click="setViewMode('list')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer {{ $viewMode === 'list' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                    <span>📋</span>
                    <span>Daftar Agenda</span>
                </button>
            </div>

            @if($asModal && $this->calendarPageUrl)
                <flux:button href="{{ $this->calendarPageUrl }}" target="_blank" icon="arrow-top-right-on-square" size="sm" variant="subtle" title="Buka Halaman Kalender Penuh">
                    Buka Halaman Penuh
                </flux:button>
            @endif
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

    {{-- Controls: Filter Tabs & Secondary Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
        {{-- Filter Buttons --}}
        @php
            $badgeCounts = ($viewMode === 'calendar') ? $this->monthCounts : $this->counts;
        @endphp
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
            <button
                type="button"
                wire:click="setFilter('all')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 {{ $filterType === 'all' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 shadow-sm' : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                <span>Semua</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterType === 'all' ? 'bg-zinc-700 text-zinc-200 dark:bg-zinc-200 dark:text-zinc-800' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">{{ $badgeCounts['all'] }}</span>
            </button>

            <button
                type="button"
                wire:click="setFilter('haul')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 {{ $filterType === 'haul' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                <span>🕌 Haul</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterType === 'haul' ? 'bg-emerald-700 text-emerald-100' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">{{ $badgeCounts['haul'] }}</span>
            </button>

            <button
                type="button"
                wire:click="setFilter('birthday')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 {{ $filterType === 'birthday' ? 'bg-amber-600 text-white shadow-sm' : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                <span>🎂 Ulang Tahun</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterType === 'birthday' ? 'bg-amber-700 text-amber-100' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">{{ $badgeCounts['birthday'] }}</span>
            </button>

            <button
                type="button"
                wire:click="setFilter('marriage')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 {{ $filterType === 'marriage' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                <span>💍 Pernikahan</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterType === 'marriage' ? 'bg-indigo-700 text-indigo-100' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">{{ $badgeCounts['marriage'] }}</span>
            </button>
        </div>

        {{-- Days Ahead Window Buttons (only for List Mode) --}}
        @if($viewMode === 'list')
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
        @endif
    </div>

    @if($viewMode === 'calendar')
        {{-- ==================== CALENDAR GRID VIEW ==================== --}}
        <div class="space-y-4">
            {{-- Month Navigation Toolbar --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-zinc-50 dark:bg-zinc-800/60 rounded-2xl border border-zinc-200/80 dark:border-zinc-700/60">
                <div class="flex items-center gap-2">
                    <flux:button type="button" wire:click="prevMonth" size="sm" icon="chevron-left" variant="subtle">
                        Bulan Lalu
                    </flux:button>
                    <flux:button type="button" wire:click="goToToday" size="sm" variant="subtle" class="font-bold">
                        Bulan Ini
                    </flux:button>
                    <flux:button type="button" wire:click="nextMonth" size="sm" icon-trailing="chevron-right" variant="subtle">
                        Bulan Depan
                    </flux:button>
                </div>

                <div class="text-base sm:text-lg font-black text-zinc-900 dark:text-white capitalize flex items-center gap-2">
                    <span>🗓️</span>
                    <span>{{ $this->monthTitle }}</span>
                    @if($this->monthCounts['all'] > 0)
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            {{ $this->monthCounts['all'] }} agenda
                        </span>
                    @endif
                </div>
            </div>

            {{-- 7-Day Header --}}
            <div class="grid grid-cols-7 gap-1 sm:gap-2 text-center text-xs font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <div class="py-1">Sen<span class="hidden sm:inline">in</span></div>
                <div class="py-1">Sel<span class="hidden sm:inline">asa</span></div>
                <div class="py-1">Rab<span class="hidden sm:inline">u</span></div>
                <div class="py-1">Kam<span class="hidden sm:inline">is</span></div>
                <div class="py-1">Jum<span class="hidden sm:inline">at</span></div>
                <div class="py-1 text-blue-600 dark:text-blue-400">Sab<span class="hidden sm:inline">tu</span></div>
                <div class="py-1 text-rose-600 dark:text-rose-400">Min<span class="hidden sm:inline">ggu</span></div>
            </div>

            {{-- Calendar Weeks Grid --}}
            <div class="space-y-1 sm:space-y-2">
                @foreach($this->calendarWeeks as $week)
                    <div class="grid grid-cols-7 gap-1 sm:gap-2">
                        @foreach($week as $day)
                            @php
                                $isSelected = $day['is_current_month'] && ($selectedDay === $day['day']);
                                $hasEvents = $day['events']->isNotEmpty();
                            @endphp
                            <div
                                @if($day['is_current_month'])
                                    wire:click="selectDay({{ $day['day'] }})"
                                    role="button"
                                    tabindex="0"
                                @endif
                                class="min-h-[75px] sm:min-h-[96px] p-1.5 sm:p-2 rounded-xl sm:rounded-2xl border transition-all flex flex-col justify-between select-none
                                    {{ ! $day['is_current_month'] ? 'opacity-30 bg-zinc-50/50 dark:bg-zinc-900/30 border-dashed border-zinc-200 dark:border-zinc-800 cursor-not-allowed' : 'cursor-pointer' }}
                                    {{ $day['is_current_month'] && ! $isSelected && ! $day['is_today'] ? 'bg-white dark:bg-zinc-800/80 border-zinc-200 dark:border-zinc-700/80 hover:border-zinc-400 dark:hover:border-zinc-500 hover:shadow-xs' : '' }}
                                    {{ $day['is_today'] && ! $isSelected ? 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-400 dark:border-emerald-600 ring-2 ring-emerald-400/50' : '' }}
                                    {{ $isSelected ? 'bg-indigo-50/90 dark:bg-indigo-950/50 border-indigo-500 dark:border-indigo-500 ring-2 ring-indigo-500 shadow-sm' : '' }}
                                ">
                                {{-- Day Number & Today indicator --}}
                                <div class="flex items-center justify-between">
                                    <span class="text-xs sm:text-sm font-bold {{ $isSelected ? 'text-indigo-600 dark:text-indigo-400 font-black' : ($day['is_today'] ? 'text-emerald-700 dark:text-emerald-300 font-black' : 'text-zinc-700 dark:text-zinc-300') }}">
                                        {{ $day['day'] }}
                                    </span>
                                    @if($day['is_today'])
                                        <span class="text-[9px] font-black uppercase px-1.5 py-0.2 rounded-md bg-emerald-600 text-white leading-tight">
                                            Hari Ini
                                        </span>
                                    @endif
                                </div>

                                {{-- Event Badges / Pills --}}
                                <div class="space-y-1 mt-1">
                                    @foreach($day['events']->take(2) as $ev)
                                        @if($ev['type'] === 'haul')
                                            <div class="text-[10px] sm:text-[11px] leading-tight font-bold px-1 sm:px-1.5 py-0.5 rounded-md truncate bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center gap-1"
                                                 title="{{ $ev['title'] }}">
                                                <span class="shrink-0 text-[10px]">🕌</span>
                                                <span class="truncate">{{ $ev['ordinal_text'] }} {{ $ev['member']?->first_name }}</span>
                                            </div>
                                        @elseif($ev['type'] === 'birthday')
                                            <div class="text-[10px] sm:text-[11px] leading-tight font-bold px-1 sm:px-1.5 py-0.5 rounded-md truncate bg-amber-100 text-amber-900 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-200 dark:border-amber-800 flex items-center gap-1"
                                                 title="{{ $ev['title'] }}">
                                                <span class="shrink-0 text-[10px]">🎂</span>
                                                <span class="truncate">{{ $ev['ordinal_text'] }} {{ $ev['member']?->first_name }}</span>
                                            </div>
                                        @else
                                            <div class="text-[10px] sm:text-[11px] leading-tight font-bold px-1 sm:px-1.5 py-0.5 rounded-md truncate bg-indigo-100 text-indigo-900 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 flex items-center gap-1"
                                                 title="{{ $ev['title'] }}">
                                                <span class="shrink-0 text-[10px]">💍</span>
                                                <span class="truncate">{{ $ev['ordinal_text'] }} {{ $ev['member']?->first_name }} &amp; {{ $ev['spouse']?->first_name }}</span>
                                            </div>
                                        @endif
                                    @endforeach

                                    @if($day['events']->count() > 2)
                                        <div class="text-[9px] font-semibold text-zinc-500 dark:text-zinc-400 pl-1">
                                            +{{ $day['events']->count() - 2 }} lainnya
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            {{-- Selected Day / Month Events Detail Panel --}}
            @php
                $eventsToShow = $this->selectedEvents;
                $isDaySelected = ($selectedDay !== null);
                if ($isDaySelected) {
                    $dateHeader = \Carbon\Carbon::create($currentYear, $currentMonth, $selectedDay)->locale('id')->translatedFormat('l, d F Y');
                    $panelTitle = "Agenda: {$dateHeader}";
                } else {
                    $panelTitle = "Seluruh Agenda {$this->monthTitle}";
                }
            @endphp
            <div class="p-4 sm:p-5 rounded-2xl bg-zinc-50 dark:bg-zinc-800/70 border border-zinc-200 dark:border-zinc-700 space-y-3 mt-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-zinc-200 dark:border-zinc-700/60">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ $isDaySelected ? '📌' : '📅' }}</span>
                        <h4 class="text-sm sm:text-base font-extrabold text-zinc-900 dark:text-white">
                            {{ $panelTitle }}
                        </h4>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                            {{ $eventsToShow->count() }} Agenda
                        </span>
                    </div>
                    @if($isDaySelected)
                        <flux:button type="button" wire:click="selectDay(null)" size="xs" variant="subtle">
                            Tampilkan Semua Bulan Ini
                        </flux:button>
                    @endif
                </div>

                @if($eventsToShow->isNotEmpty())
                    <div class="space-y-3 pt-1">
                        @foreach($eventsToShow as $event)
                            <div class="p-3.5 sm:p-4 rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="space-y-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if($event['type'] === 'haul')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center gap-1">
                                                🕌 {{ $event['ordinal_text'] }}
                                            </span>
                                        @elseif($event['type'] === 'birthday')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 flex items-center gap-1">
                                                🎂 {{ $event['ordinal_text'] }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 flex items-center gap-1">
                                                💍 {{ $event['ordinal_text'] }}
                                            </span>
                                        @endif

                                        @if($event['days_until'] === 0)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white animate-pulse">
                                                ⚡ Hari Ini
                                            </span>
                                        @elseif($event['days_until'] === 1)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white">
                                                Besok
                                            </span>
                                        @elseif($event['days_until'] === 2)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                                Lusa
                                            </span>
                                        @elseif($event['days_until'] > 2)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                                                {{ $event['days_until'] }} hari lagi
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                                                {{ abs($event['days_until']) }} hari lalu
                                            </span>
                                        @endif
                                    </div>

                                    <h4 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white">
                                        {{ $event['title'] }}
                                    </h4>
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

                                <div class="flex items-center gap-2 shrink-0 pt-2 sm:pt-0">
                                    <a
                                        href="{{ $event['wa_share_url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="Kirim ke WhatsApp (Grup Keluarga)"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#25D366] hover:bg-[#20ba59] active:bg-[#1caa4f] text-white text-xs font-bold transition-all shadow-sm">
                                        <span class="text-sm leading-none">💬</span>
                                        <span>Kirim ke WhatsApp</span>
                                    </a>

                                    <div x-data="{ copied: false }">
                                        <button
                                            type="button"
                                            x-on:click="navigator.clipboard.writeText(@js($event['whatsapp_message'])); copied = true; setTimeout(() => copied = false, 2500)"
                                            title="Salin Teks Doa / Pesan Siaran"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-semibold transition-all">
                                            <span x-show="!copied">📋 Salin Teks</span>
                                            <span x-show="copied" class="text-emerald-600 dark:text-emerald-400 font-bold">✓ Tersalin!</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-4 text-center text-xs text-zinc-500 dark:text-zinc-400">
                        Tidak ada haul, ulang tahun, atau hari pernikahan pada periode/tanggal ini.
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- ==================== LIST VIEW ==================== --}}
        <div class="space-y-3">
            @forelse($this->events as $event)
                <div class="p-4 rounded-2xl bg-white dark:bg-zinc-800/80 border border-zinc-200/90 dark:border-zinc-700/80 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-600 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    {{-- Left: Event Info --}}
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            {{-- Event Type Badge --}}
                            @if($event['type'] === 'haul')
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center gap-1">
                                    🕌 {{ $event['ordinal_text'] }}
                                </span>
                            @elseif($event['type'] === 'birthday')
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 flex items-center gap-1">
                                    🎂 {{ $event['ordinal_text'] }}
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 flex items-center gap-1">
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
    @endif
</div>
