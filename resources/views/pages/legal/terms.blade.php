<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Syarat & Ketentuan - Pohon Silsilah</title>
    <link rel="icon" href="/favicon.ico">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="font-body antialiased bg-background text-on-background selection:bg-primary selection:text-on-primary">
    <!-- Header -->
    <header class="border-b border-surface-variant/40 bg-surface/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-5 sm:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="size-8 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center p-1">
                    <x-app-logo-icon class="size-full" />
                </div>
                <span class="font-headline font-bold text-on-surface">Pohon Silsilah</span>
            </a>
            <flux:button href="{{ route('home') }}" variant="ghost" size="sm">Kembali ke Beranda</flux:button>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-5 sm:px-8 py-12 sm:py-16">
        <div class="mb-10">
            <p class="text-xs font-bold text-primary uppercase tracking-widest mb-2">Legal & Perjanjian Layanan</p>
            <h1 class="text-3xl sm:text-4xl font-headline font-extrabold text-on-surface">Syarat & Ketentuan</h1>
            <p class="text-sm text-on-surface-variant mt-2">Terakhir diperbarui: 27 Oktober 2026</p>
        </div>

        <div class="space-y-8 text-on-surface-variant text-sm sm:text-base leading-relaxed">
            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">1. Penerimaan Ketentuan</h2>
                <p>Dengan mendaftar, mengakses, atau menggunakan layanan Pohon Silsilah, Anda menyatakan setuju untuk terikat oleh Syarat dan Ketentuan ini. Jika Anda tidak menyetujui salah satu ketentuan, Anda dipersilakan untuk tidak menggunakan layanan kami.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">2. Akun & Keamanan</h2>
                <p>Pengguna bertanggung jawab untuk menjaga kerahasiaan akun Google yang digunakan untuk masuk ke platform ini. Segala aktivitas yang terjadi di bawah akun Anda adalah tanggung jawab Anda sepenuhnya.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">3. Model Layanan & Kuota Pohon Digital</h2>
                <p>Layanan kami menerapkan model freemium:</p>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Setiap pengguna baru berhak mendapatkan **1 (satu) Kuota Pohon Keluarga secara Gratis** tanpa batas waktu.</li>
                    <li>Pembuatan pohon silsilah tambahan memerlukan pembelian kuota digital seharga Rp10.000 per pohon baru melalui mitra Payment Gateway resmi kami (Duitku / QRIS).</li>
                    <li>Kuota yang telah dibeli berlaku seumur hidup akun (*lifetime digital quota*) dan tidak memiliki masa kedaluwarsa.</li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">4. Kepemilikan Konten</h2>
                <p>Seluruh data silsilah, foto, riwayat biografi, dan dokumen keluarga yang Anda unggah adalah milik Anda sepenuhnya. Pohon Silsilah tidak mengklaim hak kepemilikan intelektual atas data keluarga Anda.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">5. Batasan Penggunaan</h2>
                <p>Pengguna dilarang menggunakan layanan kami untuk:</p>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Mengunggah materi yang melanggar hukum, pornografi, ujaran kebencian, atau pencemaran nama baik.</li>
                    <li>Mencoba merusak, membebani server secara tidak wajar, atau mengeksploitasi celah keamanan sistem.</li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">6. Perubahan Ketentuan</h2>
                <p>Kami berhak memperbarui Syarat dan Ketentuan ini sewaktu-waktu. Perubahan substansial akan diinformasikan melalui pembaruan tanggal di halaman ini.</p>
            </section>
        </div>
    </main>

    <footer class="border-t border-surface-variant/40 py-8 text-center text-xs text-outline">
        <p>&copy; {{ date('Y') }} Pohon Silsilah. Hak cipta dilindungi.</p>
    </footer>
</body>
</html>
