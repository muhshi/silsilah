<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Poster Silsilah - {{ $tree->name }}</title>

    {{-- Inline compiled app CSS if available --}}
    @php
        $manifestPath = public_path('build/manifest.json');
        $cssContent = '';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
            if ($cssFile && file_exists(public_path('build/' . $cssFile))) {
                $cssContent = file_get_contents(public_path('build/' . $cssFile));
                $cssContent = preg_replace('/@import\s+url\([^)]+\);?/', '', $cssContent);
                $cssContent = preg_replace('/@import\s+"[^"]+";?/', '', $cssContent);
                $cssContent = preg_replace("/@import\s+'[^']+';?/", '', $cssContent);
            }
        }
    @endphp
    @if($cssContent)
        <style>{!! $cssContent !!}</style>
    @endif

    <style>
        @page {
            size: 841mm 594mm landscape;
            margin: 0;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 841mm;
            height: 594mm;
            min-height: 594mm;
            max-height: 594mm;
            background: #fffdfa !important;
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow: hidden;
        }

        .serif-font {
            font-family: "Cinzel", "Playfair Display", "Merriweather", "Georgia", "Times New Roman", serif !important;
        }

        .arabic-font {
            font-family: "Amiri", "Scheherazade New", "Traditional Arabic", "Arial", sans-serif;
        }

        /* Poster Canvas */
        .poster-container {
            width: 841mm;
            height: 594mm;
            padding: 12mm 14mm;
            position: relative;
            background: #fffdfa;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Luxury Ornate Double Border */
        .ornate-border-outer {
            position: absolute;
            top: 7mm;
            left: 7mm;
            right: 7mm;
            bottom: 7mm;
            border: 4.5px solid #92400e;
            border-radius: 4px;
            pointer-events: none;
            z-index: 10;
        }

        .ornate-border-inner {
            position: absolute;
            top: 10mm;
            left: 10mm;
            right: 10mm;
            bottom: 10mm;
            border: 1.8px solid #d97706;
            border-radius: 2px;
            pointer-events: none;
            z-index: 10;
        }

        .ornate-corner {
            position: absolute;
            width: 50mm;
            height: 50mm;
            z-index: 11;
            pointer-events: none;
        }
        .corner-tl { top: 9mm; left: 9mm; }
        .corner-tr { top: 9mm; right: 9mm; transform: scaleX(-1); }
        .corner-bl { bottom: 9mm; left: 9mm; transform: scaleY(-1); }
        .corner-br { bottom: 9mm; right: 9mm; transform: scale(-1); }

        /* Header Elements */
        .poster-header {
            text-align: center;
            padding: 2mm 20mm 4mm 20mm;
            position: relative;
            z-index: 5;
        }

        .basmalah {
            font-size: 22pt;
            color: #92400e;
            line-height: 1.2;
            margin-bottom: 2mm;
            letter-spacing: 0.05em;
        }

        .poster-title-label {
            font-size: 13pt;
            letter-spacing: 0.28em;
            color: #b45309;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 1mm;
        }

        .poster-title-main {
            font-size: 34pt;
            font-weight: 900;
            letter-spacing: 0.06em;
            color: #78350f;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.15;
            text-shadow: 0 1px 2px rgba(180, 83, 9, 0.15);
        }

        .hadith-banner {
            margin: 2.5mm auto 0 auto;
            max-width: 520mm;
            background: linear-gradient(90deg, rgba(254, 243, 199, 0) 0%, rgba(254, 243, 199, 0.85) 15%, rgba(254, 243, 199, 0.85) 85%, rgba(254, 243, 199, 0) 100%);
            border-top: 1px solid rgba(217, 119, 6, 0.4);
            border-bottom: 1px solid rgba(217, 119, 6, 0.4);
            padding: 2mm 8mm;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6mm;
        }

        .hadith-arabic {
            font-size: 14pt;
            color: #78350f;
            direction: rtl;
        }

        .hadith-translation {
            font-size: 11pt;
            font-style: italic;
            color: #92400e;
        }

        .hadith-source {
            font-size: 10pt;
            font-weight: 700;
            color: #b45309;
            white-space: nowrap;
        }

        /* Root Showcase (Leluhur Utama) */
        .root-showcase-section {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 3mm 0 2mm 0;
            position: relative;
            z-index: 5;
        }

        .root-showcase-card {
            background: linear-gradient(135deg, #ffffff 0%, #fffdf7 50%, #fef3c7 100%);
            border: 2.5px solid #d97706;
            box-shadow: 0 8px 24px -4px rgba(180, 83, 9, 0.22);
            border-radius: 12px;
            padding: 3mm 8mm;
            display: flex;
            align-items: center;
            gap: 6mm;
            position: relative;
        }

        .root-badge-top {
            position: absolute;
            top: -3.5mm;
            left: 50%;
            transform: translateX(-50%);
            background: #92400e;
            color: #ffffff;
            font-size: 9pt;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 1mm 5mm;
            border-radius: 9999px;
            border: 1px solid #fef3c7;
            white-space: nowrap;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .root-person {
            display: flex;
            align-items: center;
            gap: 3.5mm;
        }

        .root-avatar {
            width: 17mm;
            height: 17mm;
            border-radius: 9999px;
            border: 2px solid #d97706;
            object-fit: cover;
            background: #f8fafc;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .root-info {
            display: flex;
            flex-direction: column;
        }

        .root-role {
            font-size: 9pt;
            font-weight: 700;
            color: #b45309;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .root-name {
            font-size: 15pt;
            font-weight: 800;
            color: #78350f;
            line-height: 1.2;
        }

        .root-years {
            font-size: 10pt;
            color: #64748b;
            font-weight: 600;
        }

        .root-marriage-icon {
            font-size: 20pt;
            color: #d97706;
            padding: 0 2mm;
            font-weight: 300;
        }

        /* Bracket connecting root to columns */
        .pedigree-trunk-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            height: 6mm;
            position: relative;
            z-index: 3;
        }

        .pedigree-vertical-stem {
            width: 2.5px;
            height: 3mm;
            background: #d97706;
        }

        .pedigree-horizontal-bar {
            height: 2px;
            background: #d97706;
            position: relative;
        }

        /* Multi-Column Grid */
        .columns-container {
            display: flex;
            gap: 4mm;
            flex: 1;
            width: 100%;
            padding: 1mm 4mm 2mm 4mm;
            position: relative;
            z-index: 5;
            align-items: stretch;
            min-height: 0;
            overflow: hidden;
        }

        .column-card {
            flex: 1 1 0;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .column-header {
            padding: 3mm 3mm;
            text-align: center;
            border-bottom: 2px solid;
            position: relative;
        }

        .column-branch-badge {
            display: inline-block;
            font-size: 8.5pt;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.8mm 3mm;
            border-radius: 9999px;
            margin-bottom: 1.2mm;
        }

        .column-couple-title {
            font-size: 13pt;
            font-weight: 800;
            line-height: 1.2;
            word-wrap: break-word;
        }

        .column-years {
            font-size: 9pt;
            font-weight: 600;
            opacity: 0.85;
            margin-top: 0.8mm;
        }

        /* Column Body & Descendants Cascade */
        .column-body {
            flex: 1;
            padding: 2.5mm 2.5mm;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 2mm;
            background: #fafaf9;
        }

        .cucu-group {
            background: #ffffff;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            padding: 2mm 2.5mm;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            gap: 1.2mm;
        }

        .cucu-header {
            display: flex;
            align-items: flex-start;
            gap: 1.5mm;
        }

        .cucu-bullet {
            font-size: 9pt;
            color: #b45309;
            font-weight: 800;
            line-height: 1.3;
            flex-shrink: 0;
        }

        .cucu-content {
            flex: 1;
            min-width: 0;
        }

        .cucu-name {
            font-size: 11.5pt;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.25;
            word-wrap: break-word;
        }

        .cucu-years {
            font-size: 8.5pt;
            color: #64748b;
            font-weight: 500;
        }

        /* Cicit & Canggah Sub-tree Flow */
        .cicit-tree-container {
            margin-top: 1mm;
            padding-left: 2mm;
            border-left: 1.5px dashed #cbd5e1;
            margin-left: 1.5mm;
            display: flex;
            flex-direction: column;
            gap: 0.8mm;
        }

        .cicit-item {
            display: flex;
            align-items: flex-start;
            gap: 1.2mm;
            font-size: 10pt;
            line-height: 1.25;
            color: #334155;
        }

        .cicit-branch-icon {
            color: #94a3b8;
            font-weight: 600;
            flex-shrink: 0;
            font-size: 9pt;
        }

        .cicit-name {
            font-weight: 600;
            color: #334155;
            word-wrap: break-word;
        }

        .cicit-years {
            font-size: 8pt;
            color: #64748b;
            font-weight: normal;
        }

        .canggah-tree-container {
            padding-left: 2.5mm;
            border-left: 1px dotted #e2e8f0;
            margin-left: 1.5mm;
            margin-top: 0.5mm;
            display: flex;
            flex-direction: column;
            gap: 0.5mm;
        }

        .canggah-item {
            font-size: 8.8pt;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 1mm;
        }

        /* Poster Footer */
        .poster-footer {
            height: 18mm;
            border-top: 1.5px solid #d97706;
            margin: 0 4mm 1mm 4mm;
            padding-top: 2mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 5;
        }

        .footer-left {
            display: flex;
            flex-direction: column;
            gap: 0.8mm;
        }

        .footer-cert-title {
            font-size: 11pt;
            font-weight: 800;
            color: #78350f;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .footer-stats-line {
            font-size: 9.5pt;
            color: #475569;
            font-weight: 500;
        }

        .footer-right {
            display: flex;
            align-items: center;
            gap: 3.5mm;
            background: #ffffff;
            border: 1.5px solid #d97706;
            border-radius: 8px;
            padding: 1.5mm 3.5mm;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .qr-code-box {
            width: 14mm;
            height: 14mm;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .qr-code-box svg {
            width: 14mm !important;
            height: 14mm !important;
        }

        .qr-label-container {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .qr-label-title {
            font-size: 9pt;
            font-weight: 800;
            color: #78350f;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .qr-label-desc {
            font-size: 8pt;
            color: #64748b;
            line-height: 1.2;
            max-width: 48mm;
        }
    </style>
</head>
<body>

    {{-- Corner Ornament SVG Definitions --}}
    @php
        $cornerSvg = '
        <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 10 L45 10 C35 15, 25 25, 20 35 L20 80 L10 80 Z" fill="#b45309" opacity="0.85"/>
            <path d="M15 15 L15 50 C20 40, 30 30, 40 25 L80 25 L80 15 Z" fill="#d97706" opacity="0.9"/>
            <circle cx="20" cy="20" r="4" fill="#78350f" />
            <path d="M25 25 Q 40 40 25 55 Q 10 40 25 25 Z" fill="#f59e0b" opacity="0.6"/>
            <path d="M10 10 L90 10 L90 14 L14 14 L14 90 L10 90 Z" fill="#92400e"/>
            <circle cx="12" cy="12" r="2.5" fill="#fef3c7" />
        </svg>';
    @endphp

    <div class="poster-container">
        {{-- Ornate Borders and Corner Accents --}}
        <div class="ornate-border-outer"></div>
        <div class="ornate-border-inner"></div>

        <div class="ornate-corner corner-tl">{!! $cornerSvg !!}</div>
        <div class="ornate-corner corner-tr">{!! $cornerSvg !!}</div>
        <div class="ornate-corner corner-bl">{!! $cornerSvg !!}</div>
        <div class="ornate-corner corner-br">{!! $cornerSvg !!}</div>

        {{-- Top Header Section --}}
        <header class="poster-header">
            <div class="basmalah arabic-font">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</div>
            <div class="poster-title-label serif-font">SILSILAH KELUARGA BESAR</div>
            <h1 class="poster-title-main serif-font">{{ $tree->name }}</h1>

            <div class="hadith-banner">
                <span class="hadith-arabic arabic-font">{{ $hadith['arabic'] }}</span>
                <span class="hadith-translation serif-font">"{{ $hadith['translation'] }}"</span>
                <span class="hadith-source">({{ $hadith['source'] }})</span>
            </div>
        </header>

        {{-- Top Center Showcase: Leluhur Utama (Kakek & Nenek Buyut) --}}
        <div class="root-showcase-section">
            <div class="root-showcase-card">
                <div class="root-badge-top serif-font">👑 Leluhur Pendiri (Generasi I)</div>

                @if($root)
                    @php
                        $rootAvatar = $root->photo
                            ? (str_starts_with($root->photo, 'http') ? $root->photo : asset('storage/' . $root->photo))
                            : asset('images/no_profile_pic.jpg');
                        $rootYears = app(\App\Services\TreeBannerExportService::class)->formatYears($root);
                    @endphp
                    <div class="root-person">
                        <img src="{{ $rootAvatar }}" class="root-avatar" alt="{{ $root->first_name }}" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'">
                        <div class="root-info">
                            <span class="root-role serif-font">{{ $root->gender === 'female' ? 'Simbah Putri / Nenek' : 'Simbah Kakung / Kakek' }}</span>
                            <span class="root-name serif-font">{{ app(\App\Services\TreeBannerExportService::class)->formatMemberName($root) }}</span>
                            @if($rootYears)
                                <span class="root-years">{{ $rootYears }}</span>
                            @endif
                        </div>
                    </div>
                @endif

                @if($root && $rootSpouse)
                    <div class="root-marriage-icon">⚭</div>
                @endif

                @if($rootSpouse)
                    @php
                        $spouseAvatar = $rootSpouse->photo
                            ? (str_starts_with($rootSpouse->photo, 'http') ? $rootSpouse->photo : asset('storage/' . $rootSpouse->photo))
                            : asset('images/no_profile_pic.jpg');
                        $spouseYears = app(\App\Services\TreeBannerExportService::class)->formatYears($rootSpouse);
                    @endphp
                    <div class="root-person">
                        <img src="{{ $spouseAvatar }}" class="root-avatar" alt="{{ $rootSpouse->first_name }}" onerror="this.src='{{ asset('images/no_profile_pic.jpg') }}'">
                        <div class="root-info">
                            <span class="root-role serif-font">{{ $rootSpouse->gender === 'female' ? 'Simbah Putri / Nenek' : 'Simbah Kakung / Kakek' }}</span>
                            <span class="root-name serif-font">{{ app(\App\Services\TreeBannerExportService::class)->formatMemberName($rootSpouse) }}</span>
                            @if($spouseYears)
                                <span class="root-years">{{ $spouseYears }}</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Connecting Stem to Children Columns --}}
        @if(count($columns) > 0)
            <div class="pedigree-trunk-container">
                <div class="pedigree-vertical-stem"></div>
            </div>
        @endif

        {{-- Multi-Column Grid: 1 Column per Child Branch --}}
        <div class="columns-container">
            @forelse($columns as $col)
                @php
                    $p = $col['palette'];
                @endphp
                <div class="column-card" style="border-color: {{ $p['card_border'] }};">
                    {{-- Column Header: Anak & Pasangannya --}}
                    <div class="column-header" style="background-color: {{ $p['header_bg'] }}; border-color: {{ $p['border'] }}; color: {{ $p['header_text'] }};">
                        <span class="column-branch-badge" style="background-color: {{ $p['badge_bg'] }}; color: {{ $p['badge_text'] }}; border: 1px solid {{ $p['border'] }};">
                            {{ $col['branch_label'] }} • {{ $col['total_members'] }} Jiwa
                        </span>
                        <div class="column-couple-title serif-font" style="color: {{ $p['header_text'] }};">
                            {{ $col['couple_label'] }}
                        </div>
                        @if($col['years'])
                            <div class="column-years" style="color: {{ $p['badge_text'] }};">
                                {{ $col['years'] }}
                            </div>
                        @endif
                    </div>

                    {{-- Column Body: Descendants Cascade Vertically --}}
                    <div class="column-body">
                        @forelse($col['descendants'] as $cucuIdx => $cucu)
                            <div class="cucu-group" style="border-left: 3px solid {{ $p['line_color'] }};">
                                {{-- Cucu Row --}}
                                <div class="cucu-header">
                                    <span class="cucu-bullet">{{ $col['index'] }}.{{ $cucuIdx + 1 }}</span>
                                    <div class="cucu-content">
                                        <div class="cucu-name">
                                            {{ $cucu['couple_label'] }}
                                        </div>
                                        @if($cucu['years'])
                                            <span class="cucu-years">{{ $cucu['years'] }}</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Cicit-cicit under this Cucu --}}
                                @if(!empty($cucu['children']))
                                    <div class="cicit-tree-container">
                                        @foreach($cucu['children'] as $cicitIdx => $cicit)
                                            <div class="cicit-item">
                                                <span class="cicit-branch-icon">└</span>
                                                <div>
                                                    <span class="cicit-name">{{ $cicit['couple_label'] }}</span>
                                                    @if($cicit['years'])
                                                        <span class="cicit-years">{{ $cicit['years'] }}</span>
                                                    @endif

                                                    {{-- Canggah (Gen 5) if any --}}
                                                    @if(!empty($cicit['children']))
                                                        <div class="canggah-tree-container">
                                                            @foreach($cicit['children'] as $canggah)
                                                                <div class="canggah-item">
                                                                    <span class="cicit-branch-icon">└─</span>
                                                                    <span>{{ $canggah['couple_label'] }}</span>
                                                                    @if($canggah['years'])
                                                                        <span class="cicit-years">{{ $canggah['years'] }}</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div style="font-size: 9.5pt; color: #94a3b8; font-style: italic; text-align: center; padding: 6mm 2mm;">
                                Belum ada keturunan tercatat pada cabang ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            @empty
                <div style="width: 100%; text-align: center; padding: 20mm; font-size: 14pt; color: #94a3b8; font-style: italic;">
                    Belum ada cabang keturunan (Anak/Cucu). Tambahkan generasi penerus untuk menampilkan bagan lengkap poster dinding.
                </div>
            @endforelse
        </div>

        {{-- Footer Section --}}
        <footer class="poster-footer">
            <div class="footer-left">
                <div class="footer-cert-title serif-font">Piagam Resmi Silsilah Trah {{ $tree->name }}</div>
                <div class="footer-stats-line">
                    Dicetak pada <strong>{{ $stats['printed_at'] }}</strong> • Total: <strong>{{ $totalMembers }}</strong> Anggota Keluarga (<strong>{{ $stats['total_living'] }}</strong> Hidup, <strong>{{ $stats['total_deceased'] }}</strong> Wafat) • <strong>{{ $stats['total_generations'] }}</strong> Generasi • <strong>{{ count($columns) }}</strong> Cabang Utama
                </div>
            </div>

            <div class="footer-right">
                <div class="qr-code-box">
                    {!! $qrCodeSvg !!}
                </div>
                <div class="qr-label-container">
                    <span class="qr-label-title serif-font">Versi Digital</span>
                    <span class="qr-label-desc">Pindai QR Code untuk membuka bagan silsilah interaktif di ponsel pintar Anda.</span>
                </div>
            </div>
        </footer>
    </div>

</body>
</html>
