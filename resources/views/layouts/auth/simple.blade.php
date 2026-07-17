<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen font-body antialiased bg-background text-on-background selection:bg-primary selection:text-on-primary">
        <div class="flex min-h-svh">

            {{-- Left panel — decorative (hidden on mobile) --}}
            <div class="relative hidden w-1/2 lg:flex flex-col justify-between bg-on-surface overflow-hidden p-10">
                {{-- Subtle pattern overlay --}}
                <div class="absolute inset-0 opacity-[0.04]" style="background-image: radial-gradient(circle at 1px 1px, currentColor 1px, transparent 0); background-size: 24px 24px;"></div>

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="relative z-10 flex items-center gap-2.5" wire:navigate>
                    <div class="size-9 rounded-lg bg-primary flex items-center justify-center shadow-sm">
                        <x-app-logo-icon class="size-4 fill-current text-on-primary" />
                    </div>
                    <span class="text-lg font-headline font-bold text-surface tracking-tight">
                        Pohon Silsilah
                    </span>
                </a>

                {{-- Decorative tree illustration --}}
                <div class="relative z-10 flex-1 flex items-center justify-center py-12">
                    <div class="flex flex-col items-center gap-6">
                        {{-- Generation 1 --}}
                        <div class="flex items-center gap-4">
                            <div class="size-16 rounded-full bg-primary/20 border-[3px] border-surface/10 shadow-sm flex items-center justify-center">
                                <span class="material-symbols-outlined text-primary-container text-2xl">person</span>
                            </div>
                            <div class="w-6 h-0.5 bg-surface/20 rounded-full"></div>
                            <div class="size-16 rounded-full bg-secondary/20 border-[3px] border-surface/10 shadow-sm flex items-center justify-center">
                                <span class="material-symbols-outlined text-secondary-container text-2xl">person</span>
                            </div>
                        </div>

                        {{-- Connector --}}
                        <div class="w-0.5 h-6 bg-surface/20 rounded-full"></div>

                        {{-- Horizontal branch --}}
                        <div class="relative">
                            <div class="absolute top-0 left-1/4 right-1/4 h-0.5 bg-surface/15 rounded-full"></div>
                            <div class="flex items-start gap-16">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-0.5 h-4 bg-surface/15 rounded-full"></div>
                                    <div class="size-12 rounded-full bg-tertiary/20 border-[3px] border-surface/10 shadow-sm flex items-center justify-center">
                                        <span class="material-symbols-outlined text-tertiary-container text-xl">person</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-0.5 h-4 bg-surface/15 rounded-full"></div>
                                    <div class="size-12 rounded-full bg-primary/20 border-[3px] border-surface/10 shadow-sm flex items-center justify-center">
                                        <span class="material-symbols-outlined text-primary-container text-xl">person</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-0.5 h-4 bg-surface/15 rounded-full"></div>
                                    <div class="size-12 rounded-full bg-secondary/20 border-[3px] border-surface/10 shadow-sm flex items-center justify-center">
                                        <span class="material-symbols-outlined text-secondary-container text-xl">person</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quote --}}
                <div class="relative z-10">
                    <blockquote class="space-y-2">
                        <p class="text-surface/80 text-lg leading-relaxed font-headline italic">
                            &ldquo;Mengenal silsilah keluarga adalah ibadah. Ia menghubungkan kita dengan akar dan memuliakan ikatan darah.&rdquo;
                        </p>
                        <footer class="text-surface/50 text-sm font-medium">— Pepatah Nusantara</footer>
                    </blockquote>
                </div>
            </div>

            {{-- Right panel — form --}}
            <div class="flex flex-1 flex-col items-center justify-center p-6 sm:p-10 bg-surface-container-lowest">
                <div class="w-full max-w-sm flex flex-col gap-6">
                    {{-- Mobile logo --}}
                    <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 lg:hidden" wire:navigate>
                        <div class="size-10 rounded-lg bg-primary flex items-center justify-center shadow-sm">
                            <x-app-logo-icon class="size-5 fill-current text-on-primary" />
                        </div>
                        <span class="font-headline font-bold text-on-surface text-lg">Pohon Silsilah</span>
                    </a>

                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
