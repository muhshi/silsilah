@php
    $demoTree = $demoTree ?? \App\Models\FamilyTree::where('is_public', true)->whereNull('view_password')->whereNotNull('slug')->select(['id', 'slug'])->first();
    $demoUrl = ! empty($demoTree?->slug) ? route('tree.public', $demoTree->slug) : route('tree.public', 'demo');
@endphp
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
                    <a href="{{ $demoUrl }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 dark:text-emerald-400 hover:text-emerald-800 transition-colors">
                        <flux:icon name="sparkles" class="size-4" />
                        <span>Lihat Demo</span>
                    </a>
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
                    <span>Baru: Gotong Royong Data Reuni &amp; Banner 1 Halaman</span>
                </div>

                <h1 class="font-headline text-4xl sm:text-5xl lg:text-[3.5rem] font-extrabold text-on-surface tracking-tight leading-[1.1] mb-6">
                    Satu tempat untuk merangkai cerita keluargamu.
                </h1>

                <p class="text-lg text-on-surface-variant leading-relaxed mb-10 max-w-xl">
                    Bangun pohon silsilah keluarga, hitung nasab kekerabatan adat, pasang pengingat haul otomatis, serta cetak buku dan banner silsilah eksklusif — semudah mengisi profil.
                </p>

                <div class="flex flex-wrap items-center gap-3">
                    @auth
                        <flux:button href="{{ route('dashboard') }}" variant="primary" class="px-6 font-semibold shadow-sm">
                            Buka Dashboard
                        </flux:button>
                    @else
                        <flux:button href="{{ route('login') }}" variant="primary" class="px-6 font-semibold shadow-sm">
                            Mulai Buat Silsilah Gratis
                        </flux:button>
                    @endauth

                    <flux:button href="{{ $demoUrl }}" variant="outline" class="px-6 font-semibold" icon="sparkles">
                        Lihat Demo Interaktif
                    </flux:button>
                </div>

                <div class="mt-4 flex items-center gap-4 text-xs text-on-surface-variant">
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-emerald-600 text-sm">verified</span>
                        Gratis tanpa kartu kredit
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-emerald-600 text-sm">devices</span>
                        Mendukung PWA di Android &amp; iOS
                    </span>
                </div>
            </div>

            <!-- Tree illustration & Interactive Teaser -->
            <div class="mt-16 lg:mt-20">
                <div class="rounded-2xl border border-surface-variant/60 bg-surface-container-low p-1.5 sm:p-3 shadow-lg relative group">
                    <div class="rounded-xl overflow-hidden bg-[#F9F6F1] flex items-center justify-center relative">
                        <img src="{{ asset('images/hero-tree.png') }}" alt="Pohon Keluarga" class="w-full h-auto object-cover">

                        <!-- Interactive Demo Overlay Pill -->
                        <div class="absolute bottom-4 right-4 sm:bottom-6 sm:right-6">
                            <a href="{{ $demoUrl }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border border-emerald-500/40 shadow-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 hover:scale-105 hover:bg-white transition-all">
                                <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                                <flux:icon name="sparkles" class="size-4 text-emerald-600 dark:text-emerald-400" />
                                <span>Coba Demo Langsung &rarr;</span>
                            </a>
                        </div>
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
                <p class="text-on-surface-variant leading-relaxed">Fitur lengkap yang kamu butuhkan untuk menyusun, menghubungkan, dan mengabadikan silsilah keluarga — tanpa kerumitan.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Fitur 1 --}}
                <div class="rounded-2xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/40 transition-all shadow-xs group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="size-11 rounded-xl bg-primary-container flex items-center justify-center text-2xl">
                            🧮
                        </div>
                        <span class="inline-block bg-primary/10 text-primary text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Kalkulator Nasab</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">🧮 Kalkulator Kekerabatan &amp; Nasab</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Hitung hubungan kekerabatan dan sebutan adat (Misanan, Mindhoan, Pakdhe, Besan) dengan 1-klik bagikan ke WA grup.</p>
                </div>

                {{-- Fitur 2 --}}
                <div class="rounded-2xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/40 transition-all shadow-xs group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="size-11 rounded-xl bg-amber-100 dark:bg-amber-950/80 flex items-center justify-center text-2xl">
                            🕌
                        </div>
                        <span class="inline-block bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Pengingat Haul &amp; Ulang Tahun</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">🕌 Pengingat Haul &amp; Milad Keluarga</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Pengingat haul dan ulang tahun tanpa biaya API WhatsApp, plus langganan kalender Google/Apple Calendar otomatis.</p>
                </div>

                {{-- Fitur 3 (Highlighted: Buku Silsilah) --}}
                <div class="rounded-2xl bg-surface p-6 border-2 border-emerald-500/60 dark:border-emerald-600/60 hover:border-emerald-600 transition-all shadow-sm group relative overflow-hidden">
                    <div class="flex items-center justify-between mb-4">
                        <div class="size-11 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 flex items-center justify-center text-2xl">
                            📖
                        </div>
                        <span class="inline-block bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">Siap Cetak A4</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">📖 Ekspor Buku Silsilah PDF</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Cetak buku silsilah keluarga terstruktur per generasi (anak pertama beserta pasangan, anak kedua, dst) lengkap dengan hadits silaturahmi dan indeks nama.</p>
                </div>

                {{-- Fitur 4 --}}
                <div class="rounded-2xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/40 transition-all shadow-xs group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="size-11 rounded-xl bg-secondary-container flex items-center justify-center text-2xl">
                            🤝
                        </div>
                        <span class="inline-block bg-secondary/10 text-secondary text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Usulan Data Reuni</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">🤝 Gotong Royong Data Saat Reuni</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Kerabat bisa mengusulkan data anak/pasangan secara mandiri saat kumpul keluarga, admin tinggal approve 1-klik tanpa repot entri manual.</p>
                </div>

                {{-- Fitur 5 --}}
                <div class="rounded-2xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/40 transition-all shadow-xs group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="size-11 rounded-xl bg-blue-100 dark:bg-blue-950/80 flex items-center justify-center text-2xl">
                            📱
                        </div>
                        <span class="inline-block bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">PWA Mobile</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">📱 Otomatis Mode HP &amp; Bisa Di-install (PWA)</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Membuka mode fokus di HP dan mode kanvas di laptop, dapat di-install di layar utama HP untuk navigasi cepat dan nyaman.</p>
                </div>

                {{-- Fitur 6 --}}
                <div class="rounded-2xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/40 transition-all shadow-xs group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="size-11 rounded-xl bg-surface-variant flex items-center justify-center text-2xl">
                            🔒
                        </div>
                        <span class="inline-block bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Privasi</span>
                    </div>
                    <h3 class="font-headline font-bold text-on-surface mb-2 text-base">🔒 Privasi &amp; Keamanan Keluarga</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Pilihan pohon publik atau privat dengan kata sandi, kontrol kolaborasi keluarga, dan perlindungan privasi data pribadi tiap kerabat.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="harga" class="py-20 lg:py-28">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="max-w-lg mb-14">
                <h2 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface tracking-tight mb-3">Harga sederhana &amp; terjangkau</h2>
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
                                <span>Bagan kanvas &amp; mode fokus mobile</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-primary text-base mt-0.5">check_circle</span>
                                <span>Export bagan standar (PNG &amp; PDF)</span>
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
                        Masuk &amp; upgrade pohon
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
                        Masuk &amp; tambah pohon
                    </flux:button>
                </div>
            </div>
        </div>
    </section>

    <!-- Ready CTA Section -->
    <section class="py-16 lg:py-20 bg-surface-container-low border-t border-surface-variant/40">
        <div class="max-w-4xl mx-auto px-5 sm:px-8 text-center">
            <h2 class="font-headline text-3xl sm:text-4xl font-extrabold text-on-surface tracking-tight mb-4">
                Mulai abadikan silsilah keluargamu hari ini.
            </h2>
            <p class="text-base sm:text-lg text-on-surface-variant max-w-xl mx-auto mb-8">
                Bergabunglah dengan ribuan keluarga yang telah merawat ikatan silaturahmi antar generasi. Coba demo interaktif atau daftar gratis sekarang.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                @auth
                    <flux:button href="{{ route('dashboard') }}" variant="primary" class="px-6 font-semibold shadow-sm">
                        Buka Dashboard
                    </flux:button>
                @else
                    <flux:button href="{{ route('login') }}" variant="primary" class="px-6 font-semibold shadow-sm">
                        Mulai Buat Silsilah Gratis
                    </flux:button>
                @endauth
                <flux:button href="{{ $demoUrl }}" variant="outline" class="px-6 font-semibold" icon="sparkles">
                    Lihat Demo Interaktif
                </flux:button>
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
                            <li><a href="{{ $demoUrl }}" class="hover:text-primary transition-colors">Demo Interaktif</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-on-surface mb-3">Legal</h4>
                        <ul class="space-y-2 text-on-surface-variant">
                            <li><a href="{{ route('legal.privacy') }}" class="hover:text-primary transition-colors">Kebijakan Privasi</a></li>
                            <li><a href="{{ route('legal.terms') }}" class="hover:text-primary transition-colors">Syarat &amp; Ketentuan</a></li>
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
