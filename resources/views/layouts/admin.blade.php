<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-zinc-50/60 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100 font-body antialiased flex flex-col lg:flex-row">
    @php
        $pendingOrdersCount = \App\Models\Order::where('status', 'pending')->count();
    @endphp

    <!-- Left Sticky Sidebar (Desktop Collapsible & Mobile Drawer) -->
    <flux:sidebar sticky collapsible class="border-e border-zinc-200/80 bg-white dark:border-zinc-800/80 dark:bg-zinc-900/95 flex flex-col justify-between shrink-0 shadow-xs transition-all duration-200">
        <div>
            <!-- Sidebar Brand Header -->
            <flux:sidebar.header class="pb-4 pt-2 flex items-center justify-between">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-3 group overflow-hidden">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-800 flex items-center justify-center text-white shadow-md shadow-emerald-700/20 group-hover:scale-105 transition-all p-1.5 shrink-0">
                        <img src="{{ asset('images/logo-transparent.png') }}" class="w-full h-full object-contain filter brightness-0 invert" alt="Silsilah">
                    </div>
                    <div class="flex flex-col in-data-flux-sidebar-collapsed-desktop:hidden truncate">
                        <div class="flex items-center gap-1.5">
                            <span class="font-headline font-extrabold text-zinc-900 dark:text-white text-base tracking-tight leading-none">Silsilah</span>
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/40">
                                ADMIN
                            </span>
                        </div>
                        <span class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mt-0.5">Control Panel</span>
                    </div>
                </a>

                <flux:sidebar.collapse />
            </flux:sidebar.header>

            <!-- Navigation Groups -->
            <flux:sidebar.nav class="space-y-6 pt-2">
                <!-- Group 1: Ikhtisar -->
                <div>
                    <div class="px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 in-data-flux-sidebar-collapsed-desktop:hidden">
                        {{ __('Ikhtisar') }}
                    </div>
                    <div class="grid gap-1">
                        <flux:sidebar.item icon="layout-grid" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                            {{ __('Ringkasan KPI') }}
                        </flux:sidebar.item>
                    </div>
                </div>

                <!-- Group 2: Manajemen SaaS -->
                <div>
                    <div class="px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 in-data-flux-sidebar-collapsed-desktop:hidden">
                        {{ __('Manajemen SaaS') }}
                    </div>
                    <div class="grid gap-1">
                        <flux:sidebar.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
                            {{ __('Pengguna & Kuota') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="credit-card" :href="route('admin.orders')" :current="request()->routeIs('admin.orders')" wire:navigate :badge="$pendingOrdersCount > 0 ? $pendingOrdersCount : null" badge-color="amber">
                            {{ __('Transaksi Duitku') }}
                        </flux:sidebar.item>
                    </div>
                </div>

                <!-- Group 3: Navigasi App -->
                <div>
                    <div class="px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 in-data-flux-sidebar-collapsed-desktop:hidden">
                        {{ __('Aplikasi') }}
                    </div>
                    <div class="grid gap-1">
                        <flux:sidebar.item icon="arrow-left" :href="route('dashboard')" wire:navigate>
                            {{ __('Kembali ke Silsilah') }}
                        </flux:sidebar.item>
                    </div>
                </div>
            </flux:sidebar.nav>
        </div>

        <!-- Sidebar Footer: Admin Profile & System Status -->
        <div class="pt-4 pb-2 border-t border-zinc-100 dark:border-zinc-800/80 space-y-3">
            <!-- System Pulse Indicator (Expanded) -->
            <div class="px-3 py-2 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200/60 dark:border-zinc-700/60 flex items-center justify-between text-xs in-data-flux-sidebar-collapsed-desktop:hidden">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">Gateway Status</span>
                </div>
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ config('services.duitku.is_production') ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                    {{ config('services.duitku.is_production') ? 'PRODUCTION' : 'SANDBOX' }}
                </span>
            </div>

            <!-- Collapsed Gateway Status Dot -->
            <div class="hidden in-data-flux-sidebar-collapsed-desktop:flex justify-center py-1" title="Gateway: {{ config('services.duitku.is_production') ? 'PRODUCTION' : 'SANDBOX' }}">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ config('services.duitku.is_production') ? 'bg-emerald-400' : 'bg-amber-400' }} opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 {{ config('services.duitku.is_production') ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                </span>
            </div>

            <!-- Admin Profile Menu -->
            <flux:dropdown position="top" align="start">
                <button class="w-full flex items-center justify-center in-data-flux-sidebar-collapsed-desktop:justify-center gap-3 p-1.5 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition-colors text-left group">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" class="w-8 h-8 rounded-full object-cover border border-emerald-500/40 shrink-0" alt="">
                    @else
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-600 to-teal-800 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                            {{ auth()->user()->initials() }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0 in-data-flux-sidebar-collapsed-desktop:hidden">
                        <p class="text-xs font-bold text-zinc-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <span class="material-symbols-outlined text-sm text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-200 in-data-flux-sidebar-collapsed-desktop:hidden">unfold_more</span>
                </button>

                <flux:menu class="w-60 !rounded-2xl p-1.5 shadow-xl border border-zinc-200 dark:border-zinc-800">
                    <flux:menu.item :href="route('dashboard')" icon="home" wire:navigate class="!rounded-lg text-xs font-medium">
                        {{ __('Buka Dashboard User') }}
                    </flux:menu.item>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="!rounded-lg text-xs font-medium">
                        {{ __('Pengaturan Akun') }}
                    </flux:menu.item>
                    <flux:menu.separator />
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
    </flux:sidebar>

    <!-- Main Content Wrapper with Topbar -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navigation Bar inside Workspace -->
        <header class="h-16 px-4 lg:px-8 border-b border-zinc-200/80 dark:border-zinc-800/80 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md sticky top-0 z-40 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <flux:sidebar.toggle class="cursor-pointer" icon="bars-2" inset="left" />

                <!-- Dynamic Breadcrumbs -->
                <div class="flex items-center gap-2 text-xs">
                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 font-medium">
                        Superadmin
                    </a>
                    <span class="text-zinc-400">/</span>
                    <span class="font-bold text-zinc-900 dark:text-white">
                        @if(request()->routeIs('admin.dashboard'))
                            Ringkasan KPI
                        @elseif(request()->routeIs('admin.users'))
                            Pengguna & Kuota
                        @elseif(request()->routeIs('admin.orders'))
                            Transaksi Duitku
                        @else
                            Dashboard
                        @endif
                    </span>
                </div>
            </div>

            <!-- Topbar Quick Info & Theme Switcher -->
            <div class="flex items-center gap-2.5">
                <!-- Theme Toggle Button -->
                <button x-data x-on:click="$flux.dark = ! $flux.dark"
                        class="p-2 rounded-xl text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                        title="Ganti Mode Gelap / Terang">
                    <span class="material-symbols-outlined text-lg block dark:hidden">dark_mode</span>
                    <span class="material-symbols-outlined text-lg hidden dark:block">light_mode</span>
                </button>

                <a href="{{ route('dashboard') }}" wire:navigate class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition-all">
                    <span class="material-symbols-outlined text-sm">home</span>
                    Lihat Aplikasi Utama
                </a>
            </div>
        </header>

        <!-- Main Workspace Area -->
        <main class="flex-1 p-4 lg:p-8 max-w-7xl w-full mx-auto">
            {{ $slot }}
        </main>
    </div>

    @fluxScripts
</body>

</html>
