<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-3 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="size-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <flux:icon name="calendar" class="size-5" />
                </div>
                <flux:heading size="lg">Agenda &amp; Pengingat {{ $this->tree ? 'Trah ' . $this->tree->name : 'Keluarga' }}</flux:heading>
            </div>
            <flux:subheading>Pengingat Haul, Ulang Tahun, Hari Pernikahan, dan Acara Kumpul Trah Keluarga.</flux:subheading>
        </div>

        {{-- Switcher Mode (Kalender vs Daftar) + Tambah Acara + Link ke Halaman Penuh jika modal --}}
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            @if($this->canManageEvents)
                <flux:button
                    type="button"
                    wire:click="openCreateEventModal"
                    icon="plus"
                    size="sm"
                    class="!bg-emerald-600 hover:!bg-emerald-700 !text-white !font-bold cursor-pointer shadow-sm">
                    Tambah Acara
                </flux:button>
            @endif

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
    <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-emerald-50/90 via-teal-50/70 to-emerald-100/50 dark:from-emerald-950/40 dark:via-zinc-900 dark:to-teal-950/30 border border-emerald-200/90 dark:border-emerald-800/60 shadow-xs space-y-3">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3.5">
            <div class="flex items-start gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <span class="material-symbols-outlined text-xl">sync</span>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Sinkronisasi Kalender</h4>
                        <span class="px-2 py-0.2 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">Gratis &amp; Otomatis</span>
                    </div>
                    <p class="text-xs text-zinc-600 dark:text-zinc-300 leading-relaxed mt-0.5">
                        Hubungkan pengingat keluarga ke Google Calendar di Android/PC atau Apple Calendar di iPhone/Mac. Jadwal ter-update otomatis setiap tahun.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-1 md:pt-0 shrink-0">
                {{-- Dropdown Sinkronisasi Lengkap --}}
                <flux:dropdown>
                    <flux:button icon="arrow-path" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 !font-bold text-xs shadow-sm cursor-pointer min-h-[38px]">
                        Sinkron ke Kalender
                    </flux:button>
                    <flux:menu class="!rounded-2xl p-1.5 shadow-xl border border-zinc-200 dark:border-zinc-800 min-w-[270px]">
                        <div class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-zinc-400">Pilih Aplikasi Kalender:</div>

                        {{-- Google Calendar (Best for Android & PC) --}}
                        <flux:menu.item icon="calendar" href="{{ $this->googleCalendarUrl }}" target="_blank" rel="noopener noreferrer">
                            <div>
                                <div class="font-bold text-xs flex items-center gap-1.5">
                                    <span>Google Calendar</span>
                                    <span class="text-[9px] px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-700 font-bold">Android &amp; PC</span>
                                </div>
                                <div class="text-[11px] text-zinc-500">1-Klik buka &amp; simpan di akun Google</div>
                            </div>
                        </flux:menu.item>

                        {{-- Apple Calendar (iPhone/Mac) --}}
                        <flux:menu.item icon="device-phone-mobile" href="{{ $this->webcalFeedUrl }}">
                            <div>
                                <div class="font-bold text-xs flex items-center gap-1.5">
                                    <span>Apple Calendar</span>
                                    <span class="text-[9px] px-1.5 py-0.2 rounded bg-zinc-200 text-zinc-700 font-bold">iOS &amp; Mac</span>
                                </div>
                                <div class="text-[11px] text-zinc-500">Otomatis buka di iPhone, iPad, &amp; Mac</div>
                            </div>
                        </flux:menu.item>

                        {{-- Direct .ICS File Download --}}
                        <flux:menu.item icon="arrow-down-tray" href="{{ $this->downloadIcsUrl }}" download>
                            <div>
                                <div class="font-bold text-xs">Unduh File .ICS</div>
                                <div class="text-[11px] text-zinc-500">Impor ke kalender bawaan HP / offline</div>
                            </div>
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                {{-- Quick Shortcut: Google Calendar (especially handy on Android HP) --}}
                <a href="{{ $this->googleCalendarUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-bold transition-all shadow-2xs min-h-[38px] cursor-pointer">
                    <span class="text-sm">🗓️</span>
                    <span>Google Cal</span>
                </a>

                {{-- Copy ICS Link --}}
                <div x-data="{ copied: false }">
                    <button
                        type="button"
                        x-on:click="navigator.clipboard.writeText({{ json_encode($this->calendarFeedUrl) }}); copied = true; setTimeout(() => copied = false, 2500)"
                        class="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-bold transition-colors min-h-[38px] cursor-pointer"
                        title="Salin Link Feed Kalender (.ICS)">
                        <span class="material-symbols-outlined text-sm" x-show="!copied">link</span>
                        <span x-show="!copied">Salin Link</span>
                        <span x-show="copied" class="text-emerald-600 dark:text-emerald-400 font-black">✓ Tersalin!</span>
                    </button>
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
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 shrink-0 {{ $filterType === 'marriage' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                <span>💍 Pernikahan</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterType === 'marriage' ? 'bg-indigo-700 text-indigo-100' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">{{ $badgeCounts['marriage'] }}</span>
            </button>

            <button
                type="button"
                wire:click="setFilter('event')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center gap-1.5 shrink-0 {{ $filterType === 'event' ? 'bg-teal-600 text-white shadow-sm' : 'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">
                <span>🤝 Acara Trah</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filterType === 'event' ? 'bg-teal-700 text-teal-100' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' }}">{{ $badgeCounts['event'] ?? 0 }}</span>
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
                    <flux:button type="button" wire:click="prevMonth" size="sm" icon="chevron-left" variant="subtle" class="min-h-[40px]">
                        Bulan Lalu
                    </flux:button>
                    <flux:button type="button" wire:click="goToToday" size="sm" variant="subtle" class="font-bold min-h-[40px]">
                        Bulan Ini
                    </flux:button>
                    <flux:button type="button" wire:click="nextMonth" size="sm" icon-trailing="chevron-right" variant="subtle" class="min-h-[40px]">
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
            <div class="grid grid-cols-7 gap-1 sm:gap-2 text-center text-xs font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-400 py-1"
                 style="display: grid !important; grid-template-columns: repeat(7, minmax(0, 1fr)) !important;">
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
                    <div class="grid grid-cols-7 gap-1 sm:gap-2"
                         style="display: grid !important; grid-template-columns: repeat(7, minmax(0, 1fr)) !important;">
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
                                class="min-h-[58px] sm:min-h-[96px] p-1 sm:p-2 rounded-xl sm:rounded-2xl border transition-all flex flex-col justify-between select-none
                                    {{ ! $day['is_current_month'] ? 'opacity-25 bg-zinc-50/50 dark:bg-zinc-900/30 border-dashed border-zinc-200 dark:border-zinc-800 cursor-not-allowed' : 'cursor-pointer' }}
                                    {{ $day['is_current_month'] && ! $isSelected && ! $day['is_today'] ? 'bg-white dark:bg-zinc-800/90 border-zinc-200 dark:border-zinc-700/80 hover:border-zinc-400 dark:hover:border-zinc-500 hover:shadow-xs' : '' }}
                                    {{ $day['is_today'] && ! $isSelected ? 'bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-400 dark:border-emerald-600 ring-2 ring-emerald-500/40' : '' }}
                                    {{ $isSelected ? 'bg-indigo-50/90 dark:bg-indigo-950/60 border-indigo-500 dark:border-indigo-500 ring-2 ring-indigo-500 shadow-sm' : '' }}
                                ">
                                {{-- Day Number & Today indicator --}}
                                <div class="flex items-center justify-between">
                                    @if($day['is_today'])
                                        <span class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[11px] sm:text-xs font-black shadow-xs">
                                            {{ $day['day'] }}
                                        </span>
                                        <span class="hidden sm:inline text-[9px] font-black uppercase px-1.5 py-0.2 rounded-md bg-emerald-600 text-white leading-tight">
                                            Hari Ini
                                        </span>
                                    @elseif($isSelected)
                                        <span class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[11px] sm:text-xs font-black shadow-xs">
                                            {{ $day['day'] }}
                                        </span>
                                    @else
                                        <span class="text-xs sm:text-sm font-bold {{ ! $day['is_current_month'] ? 'text-zinc-400 dark:text-zinc-600' : 'text-zinc-700 dark:text-zinc-200' }}">
                                            {{ $day['day'] }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Mobile Micro Indicators (< sm) --}}
                                <div class="flex sm:hidden items-center justify-center gap-1 mt-1 flex-wrap">
                                    @foreach($day['events']->take(3) as $ev)
                                        @if($ev['type'] === 'haul')
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-2xs shrink-0" title="{{ $ev['title'] }}"></span>
                                        @elseif($ev['type'] === 'birthday')
                                            <span class="w-2 h-2 rounded-full bg-amber-500 shadow-2xs shrink-0" title="{{ $ev['title'] }}"></span>
                                        @elseif($ev['type'] === 'marriage')
                                            <span class="w-2 h-2 rounded-full bg-indigo-500 shadow-2xs shrink-0" title="{{ $ev['title'] }}"></span>
                                        @else
                                            <span class="w-2 h-2 rounded-full bg-teal-500 shadow-2xs shrink-0" title="{{ $ev['title'] }}"></span>
                                        @endif
                                    @endforeach
                                    @if($day['events']->count() > 3)
                                        <span class="text-[8px] font-black text-zinc-500 dark:text-zinc-400">+{{ $day['events']->count() - 3 }}</span>
                                    @endif
                                </div>

                                {{-- Tablet/Desktop Full Badges (sm:) --}}
                                <div class="hidden sm:block space-y-1 mt-1">
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
                                        @elseif($ev['type'] === 'marriage')
                                            <div class="text-[10px] sm:text-[11px] leading-tight font-bold px-1 sm:px-1.5 py-0.5 rounded-md truncate bg-indigo-100 text-indigo-900 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 flex items-center gap-1"
                                                 title="{{ $ev['title'] }}">
                                                <span class="shrink-0 text-[10px]">💍</span>
                                                <span class="truncate">{{ $ev['ordinal_text'] }}</span>
                                            </div>
                                        @else
                                            <div class="text-[10px] sm:text-[11px] leading-tight font-bold px-1 sm:px-1.5 py-0.5 rounded-md truncate bg-teal-100 text-teal-900 dark:bg-teal-950/80 dark:text-teal-300 border border-teal-200 dark:border-teal-800 flex items-center gap-1"
                                                 title="{{ $ev['title'] }}">
                                                <span class="shrink-0 text-[10px]">{{ $ev['custom_emoji'] ?? '🤝' }}</span>
                                                <span class="truncate">{{ $ev['title'] }}</span>
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

            {{-- Legend bar --}}
            <div class="flex items-center justify-center gap-3 sm:gap-6 py-2 px-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 text-[11px] font-medium text-zinc-500 dark:text-zinc-400 flex-wrap">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span>Haul</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <span>Ulang Tahun</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    <span>Pernikahan</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                    <span>Acara Trah</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 ring-2 ring-emerald-300 dark:ring-emerald-800"></span>
                    <span>Hari Ini</span>
                </div>
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
                                        @elseif($event['type'] === 'marriage')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 flex items-center gap-1">
                                                💍 {{ $event['ordinal_text'] }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800 flex items-center gap-1">
                                                {{ $event['custom_emoji'] ?? '🤝' }} {{ $event['ordinal_text'] }}
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

                                    @if(!empty($event['start_time']) || !empty($event['location_name']) || !empty($event['contact_person']))
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-600 dark:text-zinc-300 pt-1">
                                            @if(!empty($event['start_time']))
                                                <span class="flex items-center gap-1 font-semibold text-emerald-700 dark:text-emerald-400">
                                                    <span>⏰</span>
                                                    <span>{{ \Carbon\Carbon::parse($event['start_time'])->format('H:i') }}{{ !empty($event['end_time']) ? ' - ' . \Carbon\Carbon::parse($event['end_time'])->format('H:i') : '' }} WIB</span>
                                                </span>
                                            @endif
                                            @if(!empty($event['location_name']))
                                                <span class="flex items-center gap-1">
                                                    <span>🏠</span>
                                                    <span>{{ $event['location_name'] }}</span>
                                                </span>
                                            @endif
                                            @if(!empty($event['location_maps_url']))
                                                <a href="{{ $event['location_maps_url'] }}" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-bold underline inline-flex items-center gap-0.5">
                                                    <span>📍 Maps</span>
                                                    <span class="material-symbols-outlined text-[13px]">open_in_new</span>
                                                </a>
                                            @endif
                                            @if(!empty($event['contact_person']))
                                                <span class="text-zinc-500 dark:text-zinc-400">
                                                    (PIC: {{ $event['contact_person'] }})
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0 pt-2 sm:pt-0">
                                    @if($this->canManageEvents && ($event['type'] === 'event') && !empty($event['event_model']))
                                        <flux:button type="button" wire:click="editEvent({{ $event['event_model']->id }})" size="xs" variant="subtle" icon="pencil" title="Edit Acara" class="cursor-pointer" />
                                        <flux:button type="button" wire:click="deleteEvent({{ $event['event_model']->id }})" wire:confirm="Yakin ingin menghapus agenda acara ini?" size="xs" variant="subtle" icon="trash" class="!text-rose-600 hover:!bg-rose-50 dark:hover:!bg-rose-950/40 cursor-pointer" title="Hapus Acara" />
                                    @endif

                                    <a
                                        href="{{ $event['wa_share_url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="{{ $event['type'] === 'event' ? 'Kirim Undangan WhatsApp' : 'Kirim Pengingat ke WhatsApp' }}"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#25D366] hover:bg-[#20ba59] active:bg-[#1caa4f] text-white text-xs font-bold transition-all shadow-sm">
                                        <span class="text-sm leading-none">💬</span>
                                        <span>{{ $event['type'] === 'event' ? 'Undangan WA' : 'Kirim ke WhatsApp' }}</span>
                                    </a>

                                    <div x-data="{ copied: false }">
                                        <button
                                            type="button"
                                            x-on:click="navigator.clipboard.writeText({{ json_encode($event['whatsapp_message']) }}); copied = true; setTimeout(() => copied = false, 2500)"
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
                    <div class="py-6 text-center text-xs text-zinc-500 dark:text-zinc-400">
                        Tidak ada haul, ulang tahun, pernikahan, atau acara trah pada periode/tanggal ini.
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
                            @elseif($event['type'] === 'marriage')
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 flex items-center gap-1">
                                    💍 {{ $event['ordinal_text'] }}
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800 flex items-center gap-1">
                                    {{ $event['custom_emoji'] ?? '🤝' }} {{ $event['ordinal_text'] }}
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

                        @if(!empty($event['start_time']) || !empty($event['location_name']) || !empty($event['contact_person']))
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-600 dark:text-zinc-300 pt-1">
                                @if(!empty($event['start_time']))
                                    <span class="flex items-center gap-1 font-semibold text-emerald-700 dark:text-emerald-400">
                                        <span>⏰</span>
                                        <span>{{ \Carbon\Carbon::parse($event['start_time'])->format('H:i') }}{{ !empty($event['end_time']) ? ' - ' . \Carbon\Carbon::parse($event['end_time'])->format('H:i') : '' }} WIB</span>
                                    </span>
                                @endif
                                @if(!empty($event['location_name']))
                                    <span class="flex items-center gap-1">
                                        <span>🏠</span>
                                        <span>{{ $event['location_name'] }}</span>
                                    </span>
                                @endif
                                @if(!empty($event['location_maps_url']))
                                    <a href="{{ $event['location_maps_url'] }}" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-bold underline inline-flex items-center gap-0.5">
                                        <span>📍 Maps</span>
                                        <span class="material-symbols-outlined text-[13px]">open_in_new</span>
                                    </a>
                                @endif
                                @if(!empty($event['contact_person']))
                                    <span class="text-zinc-500 dark:text-zinc-400">
                                        (PIC: {{ $event['contact_person'] }})
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Right: Actions (WhatsApp Sharer, Edit/Delete, & Copy Text) --}}
                    <div class="flex items-center gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-zinc-100 dark:border-zinc-800">
                        @if($this->canManageEvents && ($event['type'] === 'event') && !empty($event['event_model']))
                            <flux:button type="button" wire:click="editEvent({{ $event['event_model']->id }})" size="xs" variant="subtle" icon="pencil" title="Edit Acara" class="cursor-pointer" />
                            <flux:button type="button" wire:click="deleteEvent({{ $event['event_model']->id }})" wire:confirm="Yakin ingin menghapus agenda acara ini?" size="xs" variant="subtle" icon="trash" class="!text-rose-600 hover:!bg-rose-50 dark:hover:!bg-rose-950/40 cursor-pointer" title="Hapus Acara" />
                        @endif

                        {{-- 1-Click WhatsApp Button --}}
                        <a
                            href="{{ $event['wa_share_url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            title="{{ $event['type'] === 'event' ? 'Kirim Undangan WhatsApp' : 'Kirim Pengingat ke WhatsApp' }}"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#25D366] hover:bg-[#20ba59] active:bg-[#1caa4f] text-white text-xs font-bold transition-all shadow-sm">
                            <span class="text-sm leading-none">💬</span>
                            <span>{{ $event['type'] === 'event' ? 'Undangan WA' : 'Kirim ke WhatsApp' }}</span>
                        </a>

                        {{-- Copy Message Button --}}
                        <div x-data="{ copied: false }">
                            <button
                                type="button"
                                x-on:click="navigator.clipboard.writeText({{ json_encode($event['whatsapp_message']) }}); copied = true; setTimeout(() => copied = false, 2500)"
                                title="Salin Teks Doa / Pesan Undangan"
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
