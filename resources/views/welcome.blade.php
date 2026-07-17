<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pohon Keluarga - Lestarikan Sejarah Keluargamu</title>

    <link rel="icon" href="/favicon.ico" sizes="any">

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxStyles
</head>
<body class="font-body antialiased bg-background text-on-background selection:bg-primary selection:text-on-primary">

    <!-- Navigation -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-surface/80 backdrop-blur-md border-b border-surface-variant/40">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="/" class="flex items-center gap-2.5 group">
                    <div class="size-8 rounded-lg bg-primary flex items-center justify-center shadow-sm">
                        <x-app-logo-icon class="size-4 fill-current text-on-primary" />
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
                <p class="text-sm font-semibold text-primary tracking-wide uppercase mb-5">Open-source & gratis</p>

                <h1 class="font-headline text-4xl sm:text-5xl lg:text-[3.5rem] font-extrabold text-on-surface tracking-tight leading-[1.1] mb-6">
                    Satu tempat untuk merangkai cerita keluargamu.
                </h1>

                <p class="text-lg text-on-surface-variant leading-relaxed mb-10 max-w-xl">
                    Bangun pohon silsilah, simpan kisah tiap generasi, dan bagikan warisan keluarga — semudah mengisi profil.
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
                    <div class="rounded-xl overflow-hidden bg-surface-container aspect-[16/9] flex items-center justify-center relative">
                        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-surface-container/80 z-10"></div>

                        {{-- Stylized family tree diagram --}}
                        <div class="relative z-0 flex flex-col items-center gap-6 py-10">
                            {{-- Generation 1 --}}
                            <div class="flex items-center gap-4">
                                <div class="size-14 sm:size-16 rounded-full bg-primary-container border-[3px] border-surface-container-lowest shadow-sm flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-xl sm:text-2xl">person</span>
                                </div>
                                <div class="w-6 h-0.5 bg-outline-variant rounded-full"></div>
                                <div class="size-14 sm:size-16 rounded-full bg-secondary-container border-[3px] border-surface-container-lowest shadow-sm flex items-center justify-center">
                                    <span class="material-symbols-outlined text-secondary text-xl sm:text-2xl">person</span>
                                </div>
                            </div>

                            {{-- Connector --}}
                            <div class="w-0.5 h-6 bg-outline-variant rounded-full"></div>

                            {{-- Horizontal branch --}}
                            <div class="relative">
                                <div class="absolute top-0 left-1/4 right-1/4 h-0.5 bg-outline-variant rounded-full"></div>
                                <div class="flex items-start gap-12 sm:gap-20">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-0.5 h-4 bg-outline-variant rounded-full"></div>
                                        <div class="size-11 sm:size-13 rounded-full bg-tertiary-container border-[3px] border-surface-container-lowest shadow-sm flex items-center justify-center">
                                            <span class="material-symbols-outlined text-tertiary text-lg sm:text-xl">person</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-0.5 h-4 bg-outline-variant rounded-full"></div>
                                        <div class="size-11 sm:size-13 rounded-full bg-primary-container border-[3px] border-surface-container-lowest shadow-sm flex items-center justify-center">
                                            <span class="material-symbols-outlined text-primary text-lg sm:text-xl">person</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-0.5 h-4 bg-outline-variant rounded-full"></div>
                                        <div class="size-11 sm:size-13 rounded-full bg-secondary-container border-[3px] border-surface-container-lowest shadow-sm flex items-center justify-center">
                                            <span class="material-symbols-outlined text-secondary text-lg sm:text-xl">person</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                <p class="text-on-surface-variant leading-relaxed">Fitur yang kamu butuhkan untuk menyusun silsilah — tanpa kerumitan.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="rounded-xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/30 transition-colors group">
                    <div class="size-10 rounded-lg bg-primary-container flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-primary text-xl">account_tree</span>
                    </div>
                    <h3 class="font-headline font-semibold text-on-surface mb-1.5">Pohon Interaktif</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Visualisasi silsilah yang responsif. Mudah dinavigasi dan menampilkan struktur serumit apa pun.</p>
                </div>

                <div class="rounded-xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/30 transition-colors group">
                    <div class="size-10 rounded-lg bg-secondary-container flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-secondary text-xl">badge</span>
                    </div>
                    <h3 class="font-headline font-semibold text-on-surface mb-1.5">Profil Mendetail</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Simpan foto, biografi, profesi, hingga tanggal penting tiap anggota keluarga.</p>
                </div>

                <div class="rounded-xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/30 transition-colors group">
                    <div class="size-10 rounded-lg bg-tertiary-container flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-tertiary text-xl">group_add</span>
                    </div>
                    <h3 class="font-headline font-semibold text-on-surface mb-1.5">Kolaborasi</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Undang kerabat untuk ikut mengelola dan melengkapi data silsilah bersama-sama.</p>
                </div>

                <div class="rounded-xl bg-surface p-6 border border-surface-variant/40 hover:border-primary/30 transition-colors group">
                    <div class="size-10 rounded-lg bg-primary-container flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-primary text-xl">picture_as_pdf</span>
                    </div>
                    <h3 class="font-headline font-semibold text-on-surface mb-1.5">Export & Cetak</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">Download pohon keluarga dalam format PDF atau gambar beresolusi tinggi untuk dicetak.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="harga" class="py-20 lg:py-28">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="max-w-lg mb-14">
                <h2 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface tracking-tight mb-3">Harga sederhana</h2>
                <p class="text-on-surface-variant leading-relaxed">Mulai gratis, tambah pohon kalau butuh. Tanpa langganan bulanan.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 max-w-3xl">
                <!-- Free -->
                <div class="rounded-xl bg-surface-container-lowest p-7 border border-surface-variant/40">
                    <h3 class="font-headline text-lg font-bold text-on-surface mb-1">Pohon Pertama</h3>
                    <p class="text-sm text-on-surface-variant mb-6">Untuk memulai silsilah keluarga utamamu.</p>

                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="font-headline text-3xl font-extrabold text-on-surface">Gratis</span>
                        <span class="text-sm text-on-surface-variant">selamanya</span>
                    </div>

                    <ul class="space-y-3 mb-8 text-sm text-on-surface-variant">
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-primary text-lg">check_circle</span>
                            <span><strong class="text-on-surface">1</strong> pohon keluarga</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-primary text-lg">check_circle</span>
                            <span>Anggota keluarga <strong class="text-on-surface">tak terbatas</strong></span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-primary text-lg">check_circle</span>
                            <span>Export PDF & gambar</span>
                        </li>
                    </ul>

                    <flux:button href="{{ route('login') }}" variant="outline" class="w-full justify-center">
                        Mulai sekarang
                    </flux:button>
                </div>

                <!-- Premium -->
                <div class="rounded-xl bg-on-surface text-surface p-7 relative overflow-hidden">
                    <div class="absolute top-3 right-3">
                        <span class="inline-block bg-tertiary-container text-on-tertiary-container text-xs font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">Populer</span>
                    </div>

                    <h3 class="font-headline text-lg font-bold mb-1">Tambah Pohon</h3>
                    <p class="text-sm opacity-70 mb-6">Untuk keluarga pasangan atau cabang lain.</p>

                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="text-sm opacity-60">Rp</span>
                        <span class="font-headline text-3xl font-extrabold">10rb</span>
                        <span class="text-sm opacity-60">/ pohon</span>
                    </div>

                    <ul class="space-y-3 mb-8 text-sm opacity-80">
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-lg opacity-60">check_circle</span>
                            <span>Bayar per pohon baru</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-lg opacity-60">check_circle</span>
                            <span>Promo: <strong class="text-white">Beli 5 gratis 1</strong></span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-lg opacity-60">check_circle</span>
                            <span>Semua fitur paket gratis</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-lg opacity-60">check_circle</span>
                            <span>Akses fitur kolaborasi</span>
                        </li>
                    </ul>

                    <flux:button href="{{ route('login') }}" variant="primary" class="w-full justify-center">
                        Masuk & tambah kuota
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
                        <div class="size-7 rounded-md bg-primary flex items-center justify-center">
                            <x-app-logo-icon class="size-3.5 fill-current text-on-primary" />
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
                            <li><a href="#" class="hover:text-primary transition-colors">Kebijakan Privasi</a></li>
                            <li><a href="#" class="hover:text-primary transition-colors">Syarat & Ketentuan</a></li>
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
