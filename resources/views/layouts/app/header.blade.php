<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-background text-on-surface dark:bg-zinc-900 font-body">
    <flux:header container
        class="border-b border-primary/10 bg-gradient-to-r from-[#F9F6F1] via-[#F4F7F4] to-[#F9F6F1] backdrop-blur-md sticky top-0 z-50 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

        <!-- Logo -->
        <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5 group">
            <div
                class="w-9 h-9 bg-gradient-to-br from-primary to-[#86A789] rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                <span class="material-symbols-outlined text-white text-lg"
                    style="font-variation-settings: 'FILL' 1;">park</span>
            </div>
            <span class="font-headline font-bold text-on-surface text-lg tracking-tight max-sm:hidden">Pohon
                Silsilah</span>
        </a>

        <!-- Desktop Nav -->
        <flux:navbar class="-mb-px max-lg:hidden ml-6">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-medium transition-all
                          {{ request()->routeIs('dashboard')
    ? 'bg-primary/10 text-primary'
    : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-highest/50' }}">
                <span class="material-symbols-outlined text-base">dashboard</span>
                Dashboard
            </a>
        </flux:navbar>

        <flux:spacer />

        <!-- Right: Notification + User -->
        <div class="flex items-center gap-1.5">
            <livewire:notification-dropdown />

            <!-- User Dropdown -->
            <flux:dropdown position="bottom" align="end">
                <button
                    class="flex items-center gap-2.5 px-2 py-1.5 rounded-full hover:bg-surface-container-highest/50 transition-all group"
                    data-test="sidebar-menu-button">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}"
                            class="w-8 h-8 rounded-full border-2 border-primary/20 group-hover:border-primary/40 transition-colors"
                            alt="{{ auth()->user()->name }}">
                    @else
                        <div
                            class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-[#86A789] flex items-center justify-center text-white text-xs font-bold">
                            {{ auth()->user()->initials() }}
                        </div>
                    @endif
                    <span class="text-sm font-medium text-on-surface max-md:hidden">{{ auth()->user()->name }}</span>
                    <span
                        class="material-symbols-outlined text-base text-on-surface-variant max-md:hidden">expand_more</span>
                </button>

                <flux:menu class="w-56">
                    <div class="flex items-center gap-3 px-3 py-2.5">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}"
                                class="w-10 h-10 rounded-full border border-outline-variant/30" alt="">
                        @else
                            <div
                                class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-[#86A789] flex items-center justify-center text-white text-sm font-bold">
                                {{ auth()->user()->initials() }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-on-surface truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-on-surface-variant truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <flux:menu.separator />
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer" data-test="logout-button">
                            {{ __('Log out') }}
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