<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <title>{{ $tree->name }} - Silsilah Digital (Dock View)</title>
    @fluxAppearance
    @livewireStyles
</head>
<body class="bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100 min-h-screen font-body antialiased overflow-hidden">
    <livewire:tree-view :id="$id" :is-public="true" :public-slug="$tree->slug" :dock="true" />
    @fluxScripts
    @livewireScripts
</body>
</html>
