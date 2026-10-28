<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalender &amp; Agenda — {{ $tree->name }}</title>
    <meta name="description" content="{{ 'Agenda keluarga bulanan, haul, ulang tahun, dan ulang tahun pernikahan untuk trah ' . $tree->name . '.' }}">

    <!-- OpenGraph & WhatsApp Social Cards -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Kalender &amp; Agenda — {{ $tree->name }}">
    <meta property="og:description" content="{{ 'Jadwal bulanan haul, ulang tahun, dan peringatan keluarga trah ' . $tree->name . '.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">
    <meta property="og:site_name" content="Silsilah Keluarga">

    <!-- Favicon & Icons -->
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- PWA & Mobile Meta -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#059669">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        if (!localStorage.getItem('flux.appearance') || localStorage.getItem('flux.appearance') === 'system') {
            localStorage.setItem('flux.appearance', 'light');
        }
        if (localStorage.getItem('flux.appearance') !== 'dark') {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @fluxAppearance
    @livewireStyles
</head>
<body class="bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100 min-h-screen font-body antialiased flex flex-col justify-between">

    {{-- Sticky Header --}}
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md border-b border-zinc-200 dark:border-zinc-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 py-2.5 sm:py-3.5 flex items-center justify-between gap-4">
            {{-- Brand & Tree Info --}}
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2 group shrink-0">
                    <div class="size-9 rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200/80 dark:border-zinc-700/80 flex items-center justify-center shadow-xs hover:scale-105 transition-all p-1">
                        <x-app-logo-icon class="size-full" />
                    </div>
                </a>
                <div class="h-6 w-px bg-zinc-200 dark:bg-zinc-700 hidden sm:block"></div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ $tree->name }}</h1>
                        <span class="text-[10px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 px-2 py-0.5 rounded-full shrink-0">Kalender Publik</span>
                    </div>
                </div>
            </div>

            {{-- Controls & Navigation --}}
            <div class="flex items-center gap-2 shrink-0">
                <flux:button size="sm" icon="arrow-left" href="{{ route('tree.public', $tree->slug) }}" variant="subtle" class="min-h-[40px] flex items-center">
                    Bagan Silsilah
                </flux:button>

                {{-- Theme Toggle --}}
                <button type="button"
                        x-data="{
                            dark: document.documentElement.classList.contains('dark'),
                            toggleTheme() {
                                this.dark = !this.dark;
                                if (this.dark) {
                                    document.documentElement.classList.add('dark');
                                    localStorage.setItem('flux.appearance', 'dark');
                                } else {
                                    document.documentElement.classList.remove('dark');
                                    localStorage.setItem('flux.appearance', 'light');
                                }
                            }
                        }"
                        @click="toggleTheme()"
                        class="p-1.5 rounded-lg text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 transition-colors text-xs flex items-center justify-center min-h-[40px] min-w-[40px]"
                        title="Ganti Tema (Terang / Gelap)">
                    <span x-show="!dark" title="Mode Gelap">🌙</span>
                    <span x-show="dark" title="Mode Terang">☀️</span>
                </button>

                @auth
                    <flux:button size="sm" icon="layout-grid" href="{{ route('dashboard') }}" class="!bg-emerald-600 !text-white hover:!bg-emerald-700 min-h-[40px] flex items-center">Dashboard</flux:button>
                @else
                    <flux:button size="sm" variant="ghost" icon="arrow-right-end-on-rectangle" href="{{ route('login') }}" class="min-h-[40px] flex items-center">Masuk</flux:button>
                @endauth
            </div>
        </div>
    </header>

    {{-- Main Calendar Container --}}
    <main class="flex-1 max-w-6xl w-full mx-auto px-3 sm:px-6 py-4 sm:py-8">
        <div class="bg-white dark:bg-zinc-900 rounded-3xl p-3 sm:p-7 border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
            <livewire:family-reminders :tree-id="$tree->id" :as-modal="false" :view-mode="'calendar'" />
        </div>
    </main>

    {{-- Footer --}}
    <footer class="py-4 border-t border-zinc-200 dark:border-zinc-800 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-zinc-500 dark:text-zinc-400 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                © {{ date('Y') }} <strong>{{ $tree->name }}</strong> — Silsilah Keluarga
            </div>
            <div>
                Dibuat dengan <a href="{{ route('home') }}" class="font-bold text-emerald-600 dark:text-emerald-400 hover:underline">Silsilah Keluarga</a>
            </div>
        </div>
    </footer>

    @fluxScripts
    @livewireScripts
</body>
</html>
