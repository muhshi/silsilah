@props(['title' => null])

<x-layouts::app.header :title="$title">
    <flux:main {{ $attributes }}>
        {{ $slot }}
    </flux:main>
</x-layouts::app.header>
