<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tree->name }} — Pohon Silsilah Keluarga</title>
    <meta name="description" content="Silsilah keluarga {{ $tree->name }}">
    <link rel="icon" href="/favicon.ico" sizes="any">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100 min-h-screen font-body antialiased flex flex-col justify-between">

    {{-- Sticky Header --}}
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md border-b border-zinc-200 dark:border-zinc-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-2.5 flex items-center justify-between gap-4">
            {{-- Brand & Tree Info --}}
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2 group shrink-0">
                    <div class="size-8 rounded-lg bg-emerald-600 flex items-center justify-center shadow-sm text-white font-bold">
                        <x-app-logo-icon class="size-4 fill-current text-white" />
                    </div>
                </a>
                <div class="h-6 w-px bg-zinc-200 dark:bg-zinc-700 hidden sm:block"></div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ $tree->name }}</h1>
                        <span class="text-[10px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 px-2 py-0.5 rounded-full shrink-0">Publik</span>
                    </div>
                </div>
            </div>

            {{-- Controls & View Switcher --}}
            <div class="flex items-center gap-2 shrink-0">
                {{-- View Type Switcher --}}
                <div class="hidden md:flex items-center bg-zinc-100 dark:bg-zinc-800 p-1 rounded-lg text-xs">
                    <a href="{{ route('tree.public', $tree->slug) }}"
                       class="px-2.5 py-1 rounded-md font-medium transition-colors {{ ($viewType ?? 'horizontal') === 'horizontal' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}">
                        Horizontal
                    </a>
                    <a href="{{ route('tree.public.vertical', $tree->slug) }}"
                       class="px-2.5 py-1 rounded-md font-medium transition-colors {{ ($viewType ?? 'horizontal') === 'vertical' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}">
                        Vertikal
                    </a>
                    <a href="{{ route('tree.public.simple', $tree->slug) }}"
                       class="px-2.5 py-1 rounded-md font-medium transition-colors {{ ($viewType ?? 'horizontal') === 'simple' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}">
                        Simple View
                    </a>
                </div>

                @auth
                    <flux:button size="sm" icon="layout-grid" href="{{ route('dashboard') }}" class="!bg-emerald-600 !text-white hover:!bg-emerald-700">Dashboard</flux:button>
                @else
                    <flux:button size="sm" variant="ghost" icon="arrow-right-end-on-rectangle" href="{{ route('login') }}">Masuk</flux:button>
                @endauth
            </div>
        </div>
    </header>

    {{-- Main Tree Canvas --}}
    <main class="flex-1 w-full">
        @if(($viewType ?? 'horizontal') === 'horizontal')
            <livewire:tree-view :id="$tree->id" :is-public="true" :public-slug="$tree->slug" />
        @elseif(($viewType ?? 'horizontal') === 'simple')
            <livewire:tree-simple :id="$tree->id" :is-public="true" :public-slug="$tree->slug" />
        @elseif(($viewType ?? 'horizontal') === 'vertical')
            <livewire:tree-vertical :id="$tree->id" :is-public="true" :public-slug="$tree->slug" />
        @endif
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
