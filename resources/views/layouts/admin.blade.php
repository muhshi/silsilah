<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-background text-on-surface dark:bg-zinc-900 font-body">
    <!-- Admin Top Header Navigation -->
    <flux:header container
        class="border-b border-amber-200/60 bg-white/90 backdrop-blur-xl sticky top-0 z-50 dark:bg-zinc-900/90 dark:border-amber-950/60 transition-all">
        <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

        <!-- Logo & Admin Badge -->
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-3 group">
            <div class="w-9 h-9 bg-amber-500/10 dark:bg-amber-500/20 rounded-xl flex items-center justify-center shadow-xs border border-amber-300/40 dark:border-amber-700/40 group-hover:scale-105 transition-all p-1">
                <span class="text-xl">🛡️</span>
            </div>
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="font-headline font-extrabold text-zinc-900 dark:text-white text-base tracking-tight leading-none">Superadmin</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300/50 dark:border-amber-700/50">PRO</span>
                </div>
                <span class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest leading-tight mt-0.5">Silsilah Platform Management</span>
            </div>
        </a>

        <!-- Desktop Admin Nav -->
        <flux:navbar class="-mb-px max-lg:hidden ml-8 gap-1">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all
                {{ request()->routeIs('admin.dashboard')
                    ? 'bg-amber-100/70 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300/60 dark:border-amber-800/60'
                    : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800/60' }}">
                <span class="material-symbols-outlined text-base">dashboard</span>
                Ringkasan KPI
            </a>

            <a href="{{ route('admin.users') }}" wire:navigate class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all
                {{ request()->routeIs('admin.users')
                    ? 'bg-amber-100/70 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300/60 dark:border-amber-800/60'
                    : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800/60' }}">
                <span class="material-symbols-outlined text-base">group</span>
                Pengguna & Kuota
            </a>

            <a href="{{ route('admin.orders') }}" wire:navigate class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all
                {{ request()->routeIs('admin.orders')
                    ? 'bg-amber-100/70 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300/60 dark:border-amber-800/60'
                    : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800/60' }}">
                <span class="material-symbols-outlined text-base">receipt_long</span>
                Transaksi Duitku
            </a>
        </flux:navbar>

        <flux:spacer />

        <!-- Switch back to user app & Profile -->
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" wire:navigate class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition-all">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Kembali ke App
            </a>

            <!-- User Dropdown -->
            <flux:dropdown position="bottom" align="end">
                <button
                    class="flex items-center gap-2.5 pl-1.5 pr-3 py-1.5 rounded-full bg-zinc-100/80 hover:bg-zinc-200/80 dark:bg-zinc-800/80 dark:hover:bg-zinc-700/80 border border-zinc-200/60 dark:border-zinc-700/60 transition-all group">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}"
                            class="w-7 h-7 rounded-full object-cover border border-amber-500/40"
                            alt="{{ auth()->user()->name }}">
                    @else
                        <div
                            class="w-7 h-7 rounded-full bg-gradient-to-br from-amber-600 to-orange-700 flex items-center justify-center text-white text-[11px] font-extrabold shadow-sm">
                            {{ auth()->user()->initials() }}
                        </div>
                    @endif
                    <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 max-md:hidden">{{ auth()->user()->name }}</span>
                    <span
                        class="material-symbols-outlined text-sm text-zinc-600 dark:text-zinc-400 group-hover:text-zinc-900 max-md:hidden">expand_more</span>
                </button>

                <flux:menu class="w-60 !rounded-2xl p-1.5 shadow-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-3 px-3 py-3 bg-amber-50/60 dark:bg-amber-950/30 rounded-xl mb-1">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-zinc-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold uppercase tracking-wider">Superadmin</p>
                        </div>
                    </div>
                    <flux:menu.separator />
                    <flux:menu.item :href="route('dashboard')" icon="home" wire:navigate class="!rounded-lg text-xs font-medium">
                        {{ __('Aplikasi Silsilah') }}
                    </flux:menu.item>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="!rounded-lg text-xs font-medium">
                        {{ __('Pengaturan Akun') }}
                    </flux:menu.item>
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer !rounded-lg text-xs font-medium !text-red-600 dark:!text-red-400 hover:!bg-red-50 dark:hover:!bg-red-950/40">
                            {{ __('Keluar (Log out)') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:header>

    <!-- Admin Mobile Menu -->
    <flux:sidebar collapsible="mobile" sticky
        class="lg:hidden border-e border-amber-200/50 bg-white dark:bg-zinc-900 text-on-surface">
        <flux:sidebar.header>
            <div class="flex items-center gap-2">
                <span class="text-2xl">🛡️</span>
                <span class="font-bold text-sm">Superadmin Panel</span>
            </div>
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="layout-grid" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                {{ __('Ringkasan KPI') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
                {{ __('Pengguna & Kuota') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="credit-card" :href="route('admin.orders')" :current="request()->routeIs('admin.orders')" wire:navigate>
                {{ __('Transaksi Duitku') }}
            </flux:sidebar.item>
            <flux:separator />
            <flux:sidebar.item icon="arrow-left" :href="route('dashboard')" wire:navigate>
                {{ __('Kembali ke Aplikasi') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </flux:sidebar>

    <flux:main container class="py-6 sm:py-8 space-y-6">
        {{ $slot }}
    </flux:main>

    @fluxScripts
</body>

</html>
