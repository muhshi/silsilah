@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Pohon Silsilah" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-xs p-1">
            <x-app-logo-icon class="size-full" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Pohon Silsilah" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-xs p-1">
            <x-app-logo-icon class="size-full" />
        </x-slot>
    </flux:brand>
@endif
