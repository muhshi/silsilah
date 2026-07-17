@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center gap-1.5">
    <h1 class="font-headline text-2xl font-bold text-on-surface tracking-tight">{{ $title }}</h1>
    <p class="text-sm text-on-surface-variant leading-relaxed">{{ $description }}</p>
</div>
