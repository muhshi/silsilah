<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tree->name }} — Silsilah Keluarga</title>
    <meta name="description" content="Silsilah keluarga {{ $tree->name }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-background text-on-surface dark:bg-zinc-900 min-h-screen font-body antialiased">
    <main class="min-h-screen">
        @if(($viewType ?? 'horizontal') === 'horizontal')
            <livewire:tree-view :id="$tree->id" :is-public="true" :public-slug="$tree->slug" />
        @elseif(($viewType ?? 'horizontal') === 'simple')
            <livewire:tree-simple :id="$tree->id" :is-public="true" :public-slug="$tree->slug" />
        @elseif(($viewType ?? 'horizontal') === 'vertical')
            <livewire:tree-vertical :id="$tree->id" :is-public="true" :public-slug="$tree->slug" />
        @endif
    </main>

    <footer class="text-center py-6 text-xs text-gray-400">
        Dibuat dengan <a href="{{ route('home') }}" class="text-indigo-500 hover:underline">Silsilah Keluarga</a>
    </footer>

    @livewireScripts
</body>
</html>
