<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kebijakan Pengembalian Dana - Pohon Silsilah</title>
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
            <p class="text-xs font-bold text-primary uppercase tracking-widest mb-2">Legal & Transaksi Keuangan</p>
            <h1 class="text-3xl sm:text-4xl font-headline font-extrabold text-on-surface">Kebijakan Pengembalian Dana (Refund)</h1>
            <p class="text-sm text-on-surface-variant mt-2">Terakhir diperbarui: 27 Oktober 2026</p>
        </div>

        <div class="space-y-8 text-on-surface-variant text-sm sm:text-base leading-relaxed">
            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">1. Karakteristik Produk Digital</h2>
                <p>Produk yang diperjualbelikan pada layanan Pohon Silsilah adalah <strong>produk digital non-fisik</strong> berupa penambahan kuota pembuatan pohon silsilah keluarga digital (*digital tree quota*). Setelah transaksi pembayaran diverifikasi oleh Payment Gateway (Duitku / QRIS), kuota otomatis aktif secara instan pada akun pengguna.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">2. Ketentuan Umum Pengembalian Dana</h2>
                <p>Mengingat sifat produk digital yang langsung dapat dikonsumsi dan tidak dapat ditarik kembali secara fisik, seluruh pembelian kuota digital pada dasarnya bersifat <strong>final dan tidak dapat dibatalkan (non-refundable)</strong>, kecuali dalam kondisi khusus yang diatur di bawah ini.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">3. Kondisi Khusus Pengajuan Refund</h2>
                <p>Pengembalian dana dapat disetujui dalam situasi berikut:</p>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li><strong>Pembayaran Ganda (*Duplicate Payment*):</strong> Terjadi pemotongan saldo lebih dari satu kali untuk satu nomor referensi pesanan yang sama akibat gangguan jaringan gateway.</li>
                    <li><strong>Kegagalan Sistem Pemenuhan (*Fulfillment Failure*):</strong> Pembayaran berhasil dipotong dari rekening pengguna namun sistem gagal menambahkan kuota akun dan tim teknis kami tidak dapat memperbaikinya dalam waktu 1x24 jam kerja.</li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-on-surface font-headline">4. Prosedur Pengajuan</h2>
                <p>Untuk mengajukan permohonan pengembalian dana atas kondisi khusus di atas, pengguna wajib mengirimkan permohonan dalam waktu maksimal <strong>3x24 jam</strong> sejak transaksi dilakukan dengan menyertakan:</p>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Email akun terdaftar di Pohon Silsilah.</li>
                    <li>Nomor Order ID (*contoh: SILSILAH-XXXXXXXX*).</li>
                    <li>Bukti struk/transfer pembayaran resmi dari bank/e-wallet.</li>
                </ul>
                <p>Permohonan yang disetujui akan diproses kembali ke rekening atau saldo pengguna melalui metode pengembalian Duitku / transfer bank dalam kurun waktu 3–7 hari kerja.</p>
            </section>
        </div>
    </main>

    <footer class="border-t border-surface-variant/40 py-8 text-center text-xs text-outline">
        <p>&copy; {{ date('Y') }} Pohon Silsilah. Hak cipta dilindungi.</p>
    </footer>
</body>
</html>
