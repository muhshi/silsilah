<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pohon Keluarga - Lestarikan Sejarah Keluargamu</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Pohon Silsilah - Lestarikan Sejarah Keluargamu">
    <meta property="og:description" content="Aplikasi silsilah keluarga modern, interaktif, dan mudah digunakan. Bagan silsilah, buku PDF, kalkulator nasab, dan pengingat haul keluarga.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">

    <!-- PWA & Mobile Meta -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#059669">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Silsilah">

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="font-body antialiased bg-background text-on-background selection:bg-primary selection:text-on-primary">

    <!-- Navigation -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-surface/80 backdrop-blur-md border-b border-surface-variant/40">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="/" class="flex items-center gap-2.5 group">
                    <div class="size-9 rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center shadow-xs group-hover:scale-105 transition-all p-1">
                        <x-app-logo-icon class="size-full" />
                    </div>
                    <span class="text-lg font-headline font-bold text-on-surface tracking-tight">
                        Pohon Silsilah
                    </span>
                </a>

                <nav class="hidden md:flex items-center gap-8">
                    <a href="#fitur" class="text-sm text-on-surface-variant hover:text-primary font-medium transition-colors">Fitur</a>
                    <a href="#harga" class="text-sm text-on-surface-variant hover:text-primary font-medium transition-colors">Harga</a>
                </nav>

                <div class="flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <flux:button href="{{ route('dashboard') }}" variant="primary">Dashboard</flux:button>
                        @else
                            <flux:button href="{{ route('login') }}" variant="primary">Daftar / Login</flux:button>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section class="relative pt-28 pb-16 lg:pt-40 lg:pb-24">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold mb-5 shadow-xs">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Baru: Cetak Buku Silsilah Keluarga (Multi-Page PDF A4)</span>
                </div>

                <h1 class="font-headline text-4xl sm:text-5xl lg:text-[3.5rem] font-extrabold text-on-surface tracking-tight leading-[1.1] mb-6">
                    Satu tempat untuk merangkai cerita keluargamu.
                </h1>

                <p class="text-lg text-on-surface-variant leading-relaxed mb-10 max-w-xl">
                    Bangun pohon silsilah, simpan kisah tiap generasi, dan cetak buku silsilah keluarga eksklusif — semudah mengisi profil.
                </p>

                <div class="flex flex-wrap gap-3">
                    <flux:button href="{{ route('login') }}" variant="primary" class="px-6">
                        Mulai gratis
                    </flux:button>
                    <flux:button href="#fitur" variant="outline" class="px-6">
                        Lihat fitur
                    </flux:button>
                </div>
            </div>

            <!-- Tree illustration -->
            <div class="mt-16 lg:mt-20">
                <div class="rounded-2xl border border-surface-variant/60 bg-surface-container-low p-1.5 sm:p-3 shadow-lg">
                    <div class="rounded-xl overflow-hidden bg-[#F9F6F1] flex items-center justify-center relative">
                        <img src="{{ asset('images/hero-tree.png') }}" alt="Pohon Keluarga" class="w-full h-auto object-cover">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="fitur" class="py-20 lg:py-28 bg-surface-container-lowest">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="max-w-lg mb-14">
                <h2 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface tracking-tight mb-3">Dibuat untuk kemudahan</h2>
                <p class="text-on-surface-variant leading-relaxed">Fitur lengkap yang kamu butuhkan untuk menyusun dan mengabadikan silsilah keluarga — tanpa kerumitan.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Fitur 1 --}}
                <div class="rounded-2xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/40 transition-all shadow-xs group">
                    <div class="size-11 rounded-xl bg-primary-container flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-primary text-2xl">account_tree</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">Pohon Interaktif & Fokus Mobile</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Visualisasi silsilah responsif dengan navigasi kanvas interaktif serta mode fokus vertikal yang nyaman dijelajahi di smartphone.</p>
                </div>

                {{-- Fitur 2 (Highlighted: Buku Silsilah) --}}
                <div class="rounded-2xl bg-surface p-6 border-2 border-emerald-500/60 dark:border-emerald-600/60 hover:border-emerald-600 transition-all shadow-sm group relative overflow-hidden">
                    <div class="absolute top-3.5 right-3.5">
                        <span class="inline-block bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">Eksklusif</span>
                    </div>
                    <div class="size-11 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-2xl">menu_book</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">📖 Cetak Buku Silsilah (PDF)</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Ekspor buku silsilah A4 siap cetak dengan sampul royal heritage, hadits silaturahmi, statistik nasab, bab generasi, dan indeks alfabetis A–Z.</p>
                </div>

                {{-- Fitur 3 --}}
                <div class="rounded-2xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/40 transition-all shadow-xs group">
                    <div class="size-11 rounded-xl bg-secondary-container flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-secondary text-2xl">badge</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">Profil & Rekam Nasab</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Simpan foto, avatar, tanggal & tempat lahir/wafat, profesi, kota domisili, pasangan, hingga catatan biografi tiap anggota keluarga.</p>
                </div>

                {{-- Fitur 4 --}}
                <div class="rounded-2xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/40 transition-all shadow-xs group">
                    <div class="size-11 rounded-xl bg-tertiary-container flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-tertiary text-2xl">group_add</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">Kolaborasi Keluarga Besar</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Undang kerabat melalui email atau tautan undangan untuk bersama-sama melengkapi silsilah keluarga secara aman dan real-time.</p>
                </div>

                {{-- Fitur 5 --}}
                <div class="rounded-2xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/40 transition-all shadow-xs group">
                    <div class="size-11 rounded-xl bg-primary-container flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-primary text-2xl">download</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">Ekspor Cepat & Transparan</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Unduh bagan kanvas (PNG/PDF) dengan indikator progres real-time yang dapat diminimalkan ke pojok layar selagi kamu menjelajahi silsilah.</p>
                </div>

                {{-- Fitur 6 --}}
                <div class="rounded-2xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/40 transition-all shadow-xs group">
                    <div class="size-11 rounded-xl bg-surface-variant flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-on-surface-variant text-2xl">security</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">Privasi & Keamanan Data</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Atur pohon sebagai publik untuk dibagikan ke kerabat, atau privat dengan perlindungan kata sandi dan riwayat log aktivitas yang lengkap.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="harga" class="py-20 lg:py-28">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="max-w-lg mb-14">
                <h2 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface tracking-tight mb-3">Harga sederhana & terjangkau</h2>
                <p class="text-on-surface-variant leading-relaxed">Mulai gratis untuk keluarga utamamu. Upgrade atau tambah kuota pohon sekali bayar tanpa langganan bulanan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto items-stretch">
                <!-- 1. Free Tier -->
                <div class="rounded-2xl bg-surface-container-lowest p-7 border border-surface-variant/40 flex flex-col justify-between">
                    <div>
                        <div class="mb-4">
                            <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Gratis Selamanya</span>
                            <h3 class="font-headline text-xl font-bold text-on-surface mt-1">Pohon Pertama</h3>
                            <p class="text-xs text-on-surface-variant mt-1">Untuk memulai merangkai silsilah keluarga intimu.</p>
                        </div>

                        <div class="flex items-baseline gap-1 my-6">
                            <span class="font-headline text-3xl font-extrabold text-on-surface">Gratis</span>
                            <span class="text-xs text-on-surface-variant">selamanya</span>
                        </div>

                        <ul class="space-y-3 text-sm text-on-surface-variant mb-8">
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-primary text-base mt-0.5">check_circle</span>
                                <span><strong class="text-on-surface">1</strong> pohon silsilah keluarga</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-primary text-base mt-0.5">check_circle</span>
                                <span>Hingga <strong class="text-on-surface">100 anggota</strong> keluarga</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-primary text-base mt-0.5">check_circle</span>
                                <span>Bagan kanvas & mode fokus mobile</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-primary text-base mt-0.5">check_circle</span>
                                <span>Export bagan standar (PNG & PDF)</span>
                            </li>
                        </ul>
                    </div>

                    <flux:button href="{{ route('login') }}" variant="outline" class="w-full justify-center">
                        Mulai gratis sekarang
                    </flux:button>
                </div>

                <!-- 2. Upgrade Tree (Rp 5.000) - Highlighted -->
                <div class="rounded-2xl bg-surface p-7 border-2 border-emerald-500 dark:border-emerald-500 shadow-xl relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute top-3.5 right-3.5">
                        <span class="inline-block bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 text-xs font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">Paling Praktis</span>
                    </div>

                    <div>
                        <div class="mb-4">
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Pohon yang Ada</span>
                            <h3 class="font-headline text-xl font-bold text-on-surface mt-1">Upgrade ke Premium</h3>
                            <p class="text-xs text-on-surface-variant mt-1">Sudah terlanjur isi pohon gratis? Cukup upgrade tanpa buat ulang!</p>
                        </div>

                        <div class="flex items-baseline gap-1 my-6">
                            <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">Rp</span>
                            <span class="font-headline text-3xl font-extrabold text-on-surface">5.000</span>
                            <span class="text-xs text-on-surface-variant">/ pohon (sekali bayar)</span>
                        </div>

                        <ul class="space-y-3 text-sm text-on-surface-variant mb-8">
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-base mt-0.5">workspace_premium</span>
                                <span><strong class="text-on-surface">Anggota Tak Terbatas</strong> (unlimited)</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-base mt-0.5">menu_book</span>
                                <span>📖 <strong class="text-on-surface">Cetak Buku Silsilah</strong> (A4 Multi-Halaman)</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-base mt-0.5">group_add</span>
                                <span>Fitur kolaborasi keluarga penuh</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-base mt-0.5">verified</span>
                                <span>Aktif selamanya tanpa biaya langganan</span>
                            </li>
                        </ul>
                    </div>

                    <flux:button href="{{ route('login') }}" variant="primary" class="w-full justify-center !bg-emerald-600 hover:!bg-emerald-700">
                        Masuk & upgrade pohon
                    </flux:button>
                </div>

                <!-- 3. Tambah Pohon Baru (Rp 10.000) -->
                <div class="rounded-2xl bg-zinc-900 text-white p-7 relative overflow-hidden flex flex-col justify-between border border-zinc-800 shadow-lg">
                    <div class="absolute top-3.5 right-3.5">
                        <span class="inline-block bg-amber-400 text-zinc-950 text-xs font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">Diskon 15%</span>
                    </div>

                    <div>
                        <div class="mb-4">
                            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">Pohon Baru Tambahan</span>
                            <h3 class="font-headline text-xl font-bold text-white mt-1">Tambah Pohon Baru</h3>
                            <p class="text-xs text-zinc-400 mt-1">Untuk keluarga pasangan atau cabang keluarga lain.</p>
                        </div>

                        <div class="flex items-baseline gap-1 my-6">
                            <span class="text-sm text-zinc-400">Rp</span>
                            <span class="font-headline text-3xl font-extrabold text-white">10.000</span>
                            <span class="text-xs text-zinc-400">/ pohon (hemat kuota)</span>
                        </div>

                        <ul class="space-y-3 text-sm text-zinc-300 mb-8">
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-amber-400 text-base mt-0.5">check_circle</span>
                                <span>Otomatis langsung berstatus <strong class="text-white">Pohon Premium</strong></span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-amber-400 text-base mt-0.5">check_circle</span>
                                <span>Anggota keluarga <strong class="text-white">tanpa batas</strong></span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-amber-400 text-base mt-0.5">check_circle</span>
                                <span>Termasuk <strong class="text-white">Cetak Buku Silsilah (PDF)</strong></span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-amber-400 text-base mt-0.5">check_circle</span>
                                <span>Bebas pilih jumlah kuota dengan diskon 15%</span>
                            </li>
                        </ul>
                    </div>

                    <flux:button href="{{ route('login') }}" class="w-full justify-center !bg-white !text-zinc-950 hover:!bg-zinc-200">
                        Masuk & tambah pohon
                    </flux:button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-surface-variant/40 py-10">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-8">
                <div class="max-w-xs">
                    <div class="flex items-center gap-2.5 mb-3">
                        <div class="size-8 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center shadow-xs p-1">
                            <x-app-logo-icon class="size-full" />
                        </div>
                        <span class="font-headline font-bold text-on-surface">Pohon Silsilah</span>
                    </div>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Platform manajemen silsilah keluarga. Menyatukan cerita antar generasi.</p>
                </div>

                <div class="flex gap-12 text-sm">
                    <div>
                        <h4 class="font-semibold text-on-surface mb-3">Produk</h4>
                        <ul class="space-y-2 text-on-surface-variant">
                            <li><a href="#fitur" class="hover:text-primary transition-colors">Fitur</a></li>
                            <li><a href="#harga" class="hover:text-primary transition-colors">Harga</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-on-surface mb-3">Legal</h4>
                        <ul class="space-y-2 text-on-surface-variant">
                            <li><a href="{{ route('legal.privacy') }}" class="hover:text-primary transition-colors">Kebijakan Privasi</a></li>
                            <li><a href="{{ route('legal.terms') }}" class="hover:text-primary transition-colors">Syarat & Ketentuan</a></li>
                            <li><a href="{{ route('legal.refund') }}" class="hover:text-primary transition-colors">Kebijakan Refund</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="border-t border-surface-variant/40 mt-8 pt-6">
                <p class="text-xs text-outline">&copy; {{ date('Y') }} Pohon Silsilah. Hak cipta dilindungi.</p>
            </div>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
