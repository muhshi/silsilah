<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Undangan Kolaborasi - {{ $tree->name }}</title>

    <!-- Open Graph / WhatsApp Preview Meta Tags -->
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Undangan Kolaborasi Silsilah {{ $tree->name }}" />
    <meta property="og:description" content="Anda diundang untuk bergabung sebagai Editor untuk mengelola diagram pohon silsilah keluarga {{ $tree->name }} di Silsilah." />
    <meta property="og:site_name" content="Silsilah Keluarga" />

    <!-- Google Fonts & Tailwind -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="bg-slate-50 dark:bg-zinc-900 min-h-screen flex items-center justify-center p-4 font-['Plus_Jakarta_Sans',sans-serif]">
    <div class="w-full max-w-md">
        <div class="bg-white dark:bg-zinc-800/90 rounded-2xl shadow-xl border border-slate-200 dark:border-zinc-700/80 p-8 space-y-6 text-center backdrop-blur-md">
            {{-- Icon Header --}}
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 mb-1 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>

            {{-- Info --}}
            <div class="space-y-2">
                <span class="inline-block px-3 py-1 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold rounded-full">
                    Undangan Kolaborasi
                </span>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                    Silsilah {{ $tree->name }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    @if($owner)
                        <strong class="text-slate-800 dark:text-slate-200">{{ $owner->name }}</strong> mengundang Anda untuk bergabung sebagai <strong>Editor</strong> silsilah keluarga.
                    @else
                        Anda diundang untuk bergabung sebagai <strong>Editor</strong> silsilah keluarga ini.
                    @endif
                </p>
            </div>

            {{-- Actions --}}
            <div class="pt-2">
                @auth
                    <form method="POST" action="{{ route('invitation.accept.process', $invitation->token) }}">
                        @csrf
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-4 rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <span>Terima Undangan & Bergabung</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('auth.google') }}" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-4 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M12.24 10.285V13.4h6.887c-.58 3.012-3.056 5.228-6.887 5.228-4.325 0-7.834-3.509-7.834-7.834s3.509-7.834 7.834-7.834c2.008 0 3.829.742 5.228 1.966l2.367-2.367C17.514 1.13 15.029 0 12.24 0 5.48 0 0 5.48 0 12.24s5.48 12.24 12.24 12.24c7.054 0 12.062-4.957 12.062-12.062 0-.742-.087-1.467-.23-2.133H12.24z"/>
                        </svg>
                        <span>Masuk dengan Google untuk Bergabung</span>
                    </a>
                @endauth
            </div>

            {{-- Footer Back link --}}
            <div class="pt-2">
                <a href="{{ route('home') }}" class="text-xs text-slate-400 dark:text-slate-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                    ← Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</body>
</html>
