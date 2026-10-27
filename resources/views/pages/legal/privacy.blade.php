<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kebijakan Privasi - Pohon Silsilah</title>
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
            <p class="text-xs font-bold text-primary uppercase tracking-widest mb-2">Legal & Perlindungan Data</p>
            <h1 class="text-3xl sm:text-4xl font-headline font-extrabold text-on-surface">Kebijakan Privasi</h1>
            <p class="text-sm text-on-surface-variant mt-2">Terakhir diperbarui: 27 Oktober 2026</p>
        </div>

        <div class="space-y-8 text-on-surface-variant text-sm sm:text-base leading-relaxed">
            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">1. Komitmen Privasi Kami</h2>
                <p>Pohon Silsilah ("kami", "aplikasi") sangat menghargai privasi dan kerahasiaan data keluarga Anda. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi informasi pribadi anggota keluarga yang Anda daftarkan di platform kami.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">2. Informasi yang Kami Kumpulkan</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li><strong>Data Akun Pengguna:</strong> Nama, alamat email, dan identitas autentikasi Google SSO saat Anda membuat akun.</li>
                    <li><strong>Data Silsilah Keluarga:</strong> Nama anggota keluarga, tanggal & tempat lahir/wafat, jenis kelamin, foto profil/avatar, hubungan perkawinan, dan biografi singkat yang Anda masukkan secara sukarela.</li>
                    <li><strong>Data Transaksi:</strong> Nomor referensi transaksi pembayaran kuota pohon digital yang diproses melalui mitra resmi Payment Gateway (Duitku). Kami tidak menyimpan informasi kartu debit/kredit sensitif Anda.</li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">3. Penggunaan Informasi</h2>
                <p>Data Anda hanya digunakan untuk:</p>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Menyusun, memvisualisasikan, dan merender bagan silsilah keluarga Anda.</li>
                    <li>Memfasilitasi ekspor silsilah ke dokumen PDF, PNG, atau JSON.</li>
                    <li>Mengelola hak akses kolaborator keluarga yang Anda undang secara eksplisit.</li>
                    <li>Memproses aktivasi kuota digital setelah pembayaran terverifikasi.</li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">4. Kontrol Privasi & Berbagi Data</h2>
                <p>Secara *default*, seluruh pohon keluarga berstatus **Privat** dan hanya dapat dilihat oleh pembuatnya. Anda memiliki kontrol penuh untuk:</p>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Mengaktifkan link berbagi publik (*Public View*).</li>
                    <li>Memproteksi tampilan silsilah publik dengan PIN atau Password khusus.</li>
                    <li>Mengundang anggota keluarga lain sebagai *Editor* atau *Viewer*.</li>
                </ul>
                <p>Kami <strong>tidak pernah menjual, menyewakan, atau membagikan</strong> data silsilah keluarga Anda kepada pihak ketiga untuk kepentingan periklanan.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">5. Hak Menghapus Data</h2>
                <p>Anda berhak menghapus akun, pohon keluarga, atau anggota keluarga kapan saja melalui menu Pengaturan. Saat Anda menghapus pohon silsilah, seluruh data anggota dan foto terkait akan dihapus secara permanen dari server kami.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">6. Hubungi Kami</h2>
                <p>Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini, silakan hubungi tim kami melalui email resmi dukungan layanan kami.</p>
            </section>
        </div>
    </main>

    <footer class="border-t border-surface-variant/40 py-8 text-center text-xs text-outline">
        <p>&copy; {{ date('Y') }} Pohon Silsilah. Hak cipta dilindungi.</p>
    </footer>
</body>
</html>
