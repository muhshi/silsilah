<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password — {{ $tree->name }}</title>
    <meta name="description" content="Masukkan password untuk melihat silsilah {{ $tree->name }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="bg-gray-50 dark:bg-zinc-900 min-h-screen font-sans antialiased">
    <livewire:public-tree-password :slug="$slug" />
    @fluxScripts
</body>
</html>
