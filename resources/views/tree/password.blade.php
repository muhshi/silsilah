<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password — {{ $tree->name }}</title>
    <meta name="description" content="Masukkan password untuk melihat silsilah {{ $tree->name }}">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">
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
</head>
<body class="bg-gray-50 dark:bg-zinc-900 min-h-screen font-sans antialiased">
    <livewire:public-tree-password :slug="$slug" />
    @fluxScripts
</body>
</html>
