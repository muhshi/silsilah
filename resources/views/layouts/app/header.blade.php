<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-background text-on-surface dark:bg-zinc-900 font-body">
    <flux:header container
        class="border-b border-zinc-200/70 bg-white/80 backdrop-blur-xl sticky top-0 z-50 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900 dark:bg-zinc-900/80 dark:border-zinc-800 transition-all">
        <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

        <!-- Logo & Brand -->
        <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3 group">
            <div class="w-9 h-9 bg-gradient-to-br from-emerald-600 to-teal-800 rounded-xl flex items-center justify-center shadow-md shadow-emerald-700/20 group-hover:scale-105 group-hover:shadow-emerald-700/30 transition-all">
                <span class="text-white text-lg">🌳</span>
            </div>
            <div class="flex flex-col">
                <span class="font-headline font-extrabold text-zinc-900 dark:text-white text-base tracking-tight leading-none group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors">Silsilah</span>
                <span class="text-[10px] font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-widest leading-tight mt-0.5">Living Heritage</span>
            </div>
        </a>

        <!-- Desktop Nav -->
        <flux:navbar class="-mb-px max-lg:hidden ml-8">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold transition-all
                          {{ request()->routeIs('dashboard')
    ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 shadow-sm border border-emerald-200/60 dark:border-emerald-800/60'
    : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800/60' }}">
                <span class="material-symbols-outlined text-base">dashboard</span>
                Dashboard Silsilah
            </a>
        </flux:navbar>

        <flux:spacer />

        <!-- Right: Notification + User Profile -->
        <div class="flex items-center gap-2">
            <livewire:notification-dropdown />

            <!-- User Dropdown -->
            <flux:dropdown position="bottom" align="end">
                <button
                    class="flex items-center gap-2.5 pl-1.5 pr-3 py-1.5 rounded-full bg-zinc-100/80 hover:bg-zinc-200/80 dark:bg-zinc-800/80 dark:hover:bg-zinc-700/80 border border-zinc-200/60 dark:border-zinc-700/60 transition-all group"
                    data-test="sidebar-menu-button">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}"
                            class="w-7 h-7 rounded-full object-cover border border-emerald-500/40"
                            alt="{{ auth()->user()->name }}">
                    @else
                        <div
                            class="w-7 h-7 rounded-full bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center text-white text-[11px] font-extrabold shadow-sm">
                            {{ auth()->user()->initials() }}
                        </div>
                    @endif
                    <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 max-md:hidden">{{ auth()->user()->name }}</span>
                    <span
                        class="material-symbols-outlined text-sm text-zinc-600 dark:text-zinc-400 group-hover:text-zinc-900 max-md:hidden">expand_more</span>
                </button>

                <flux:menu class="w-60 !rounded-2xl p-1.5 shadow-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-3 px-3 py-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl mb-1">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}"
                                class="w-10 h-10 rounded-full border border-emerald-500/30 object-cover" alt="">
                        @else
                            <div
                                class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                {{ auth()->user()->initials() }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-zinc-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-zinc-600 dark:text-zinc-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <flux:menu.separator />
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="!rounded-lg text-xs font-medium">
                        {{ __('Pengaturan Akun') }}
                    </flux:menu.item>
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer !rounded-lg text-xs font-medium !text-red-600 dark:!text-red-400 hover:!bg-red-50 dark:hover:!bg-red-950/40" data-test="logout-button">
                            {{ __('Keluar (Log out)') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:header>

    <!-- Mobile Menu -->
    <flux:sidebar collapsible="mobile" sticky
        class="lg:hidden border-e border-primary/10 bg-gradient-to-b from-[#F9F6F1] to-white dark:from-zinc-900 dark:to-zinc-900 dark:border-zinc-700 text-on-surface">
        <flux:sidebar.header>
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5">
                <div
                    class="w-8 h-8 bg-gradient-to-br from-primary to-[#86A789] rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-base"
                        style="font-variation-settings: 'FILL' 1;">park</span>
                </div>
                <span class="font-headline font-bold text-on-surface">Pohon Silsilah</span>
            </a>
            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Menu')">
                <flux:sidebar.item icon="layout-grid" :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')" wire:navigate>
                    Dashboard
                </flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>
    </flux:sidebar>

    {{ $slot }}

    @fluxScripts
</body>

</html>