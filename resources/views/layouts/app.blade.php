<x-layouts::app.header :title="$title ?? null">
    <flux:main {{ $attributes }}>
        {{ $slot }}
    </flux:main>
</x-layouts::app.header>
