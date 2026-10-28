<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Silsilah - {{ $tree->name }}</title>

    {{-- Inline built CSS directly — no external font requests that could stall Browsershot --}}
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
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .page-break {
                page-break-after: always !important;
                break-after: page !important;
            }
            .avoid-break {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        html, body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            background: #ffffff !important;
            color: #1e293b;
            margin: 0;
            padding: 0;
            width: 210mm;
            -webkit-font-smoothing: antialiased;
        }

        .serif-title {
            font-family: "Playfair Display", "Merriweather", "Georgia", "Times New Roman", serif !important;
        }

        /* Standard A4 Page Container */
        .book-page {
            width: 210mm;
            min-height: 297mm;
            box-sizing: border-box;
            position: relative;
            background: #ffffff;
        }

        .page-content {
            padding: 20mm 16mm 18mm 16mm;
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Running Header & Footer for Content Pages */
        .page-running-header {
            position: absolute;
            top: 8mm;
            left: 16mm;
            right: 16mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3mm;
            font-size: 8pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .page-running-footer {
            position: absolute;
            bottom: 8mm;
            left: 16mm;
            right: 16mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 3mm;
            font-size: 8pt;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    {{-- ======================================================== --}}
    {{-- 1. HALAMAN SAMPUL DEPAN (ROYAL HERITAGE COVER PAGE)     --}}
    {{-- ======================================================== --}}
    <div class="book-page page-break" style="background: linear-gradient(150deg, #041f16 0%, #083324 50%, #02120d 100%); color: #ffffff; padding: 14mm;">
        {{-- Double Gold Frame with Corner Arabesque Accents --}}
        <div style="border: 2px solid #d4af37; height: calc(297mm - 28mm); box-sizing: border-box; padding: 5mm; position: relative; display: flex; flex-direction: column; justify-content: space-between; text-align: center;">
            <div style="border: 1px solid rgba(212, 175, 55, 0.45); height: 100%; box-sizing: border-box; padding: 12mm 8mm; display: flex; flex-direction: column; justify-content: space-between;">
                
                {{-- Header & Crest --}}
                <div>
                    {{-- Decorative Top Ornament --}}
                    <div style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 24px;">
                        <div style="height: 1px; width: 45px; background: linear-gradient(to right, transparent, #d4af37);"></div>
                        <span style="color: #d4af37; font-size: 14px; letter-spacing: 0.3em;">❖ ❖ ❖</span>
                        <div style="height: 1px; width: 45px; background: linear-gradient(to left, transparent, #d4af37);"></div>
                    </div>

                    {{-- Family Crest Emblem SVG --}}
                    <div style="margin: 0 auto 20px; width: 76px; height: 76px; border-radius: 50%; background: radial-gradient(circle, rgba(212,175,55,0.2) 0%, rgba(212,175,55,0.03) 70%); border: 1.5px solid #d4af37; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 44px; height: 44px; color: #f6e05e;" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.87-3.13-7-7-7zm-1 18h2v2h-2v-2zm-3-1h8v1H8v-1z"/>
                        </svg>
                    </div>

                    <div style="font-size: 10pt; font-weight: 600; letter-spacing: 0.25em; color: #e2c068; text-transform: uppercase; margin-bottom: 8px;">
                        Dokumentasi Silsilah & Nasab Keluarga
                    </div>

                    <h1 class="serif-title" style="font-size: 26pt; font-weight: 700; color: #ffffff; margin: 0 0 14px 0; letter-spacing: 0.08em; line-height: 1.2;">
                        BUKU SILSILAH KELUARGA
                    </h1>

                    {{-- Golden Divider --}}
                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 18px;">
                        <div style="height: 1px; width: 80px; background: linear-gradient(to right, transparent, #d4af37);"></div>
                        <div style="width: 8px; height: 8px; background: #d4af37; transform: rotate(45deg);"></div>
                        <div style="height: 1px; width: 80px; background: linear-gradient(to left, transparent, #d4af37);"></div>
                    </div>

                    {{-- Tree Name (Bani / Trah) --}}
                    <div class="serif-title" style="font-size: 28pt; font-weight: 800; color: #fef08a; letter-spacing: 0.06em; line-height: 1.25; margin-bottom: 14px; text-shadow: 0 2px 10px rgba(0,0,0,0.5);">
                        {{ strtoupper($tree->name) }}
                    </div>

                    @if($tree->description)
                        <div style="font-size: 11pt; color: #cbd5e1; font-style: italic; max-width: 140mm; margin: 0 auto; line-height: 1.6;">
                            "{{ $tree->description }}"
                        </div>
                    @else
                        <div style="font-size: 10.5pt; color: #94a3b8; font-style: italic; max-width: 140mm; margin: 0 auto; line-height: 1.6;">
                            "Menyambung Tali Silaturahmi, Menjaga Kemurnian Nasab, dan Mengabadikan Jejak Luhur Para Pendahulu untuk Generasi Mendatang"
                        </div>
                    @endif
                </div>

                {{-- Center Emblem Pattern --}}
                <div style="margin: 20px auto; opacity: 0.75;">
                    <svg style="width: 120px; height: 32px; color: #d4af37;" viewBox="0 0 120 32" fill="none">
                        <path d="M60 0L70 16L60 32L50 16L60 0Z" fill="currentColor" fill-opacity="0.3"/>
                        <circle cx="60" cy="16" r="4" fill="currentColor"/>
                        <line x1="0" y1="16" x2="45" y2="16" stroke="currentColor" stroke-width="1"/>
                        <line x1="75" y1="16" x2="120" y2="16" stroke="currentColor" stroke-width="1"/>
                    </svg>
                </div>

                {{-- Footer Info --}}
                <div>
                    {{-- Summary Stat Badges --}}
                    <div style="display: inline-flex; align-items: center; justify-content: center; gap: 20px; background: rgba(2, 44, 30, 0.7); border: 1px solid rgba(212, 175, 55, 0.4); border-radius: 999px; padding: 8px 24px; margin-bottom: 24px;">
                        <span style="font-size: 10pt; color: #fef08a; font-weight: 600;">
                            ✨ {{ $stats['total_generations'] }} Generasi Terdata
                        </span>
                        <span style="color: rgba(212, 175, 55, 0.5);">|</span>
                        <span style="font-size: 10pt; color: #ffffff; font-weight: 500;">
                            👥 {{ $stats['total_members'] }} Anggota Keluarga
                        </span>
                    </div>

                    <div style="font-size: 9.5pt; color: #94a3b8; margin-bottom: 4px;">
                        Dicetak pada: <strong style="color: #e2e8f0;">{{ $stats['printed_at'] }}</strong>
                    </div>
                    <div style="font-size: 8.5pt; color: #64748b; letter-spacing: 0.05em;">
                        Diterbitkan & Dihimpun melalui <strong>Silsilah.org</strong>
                    </div>
                </div>

            </div>
        </div>
    </div>


    {{-- ======================================================== --}}
    {{-- 2. HALAMAN MUKADIMAH & STATISTIK SILSILAH               --}}
    {{-- ======================================================== --}}
    <div class="book-page page-break" style="padding: 16mm;">
        {{-- Running Header --}}
        <div class="page-running-header">
            <span>Buku Silsilah Keluarga — {{ $tree->name }}</span>
            <span>Mukadimah & Keutamaan Silaturahmi</span>
        </div>

        <div style="padding-top: 10mm;">
            {{-- Section Title --}}
            <div style="text-align: center; margin-bottom: 24px;">
                <span style="color: #059669; font-size: 9pt; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase;">
                    Kalam Hikmah & Landasan Syariat
                </span>
                <h2 class="serif-title" style="font-size: 18pt; font-weight: 700; color: #0f172a; margin: 4px 0 8px 0;">
                    MUKADIMAH &amp; KEUTAMAAN SILATURAHMI
                </h2>
                <div style="height: 2px; width: 60px; background: #059669; margin: 0 auto;"></div>
            </div>

            {{-- Hadits Box 1 --}}
            <div style="background: #f0fdf4; border-left: 4px solid #059669; border-radius: 0 10px 10px 0; padding: 14px 18px; margin-bottom: 16px;">
                <div style="font-size: 9pt; font-weight: 700; color: #065f46; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em;">
                    📖 Hadits Riwayat Al-Bukhari & Muslim
                </div>
                <div style="font-size: 10.5pt; color: #166534; font-style: italic; line-height: 1.55;">
                    "Barangsiapa yang ingin dilapangkan rezekinya dan dipanjangkan umurnya, hendaklah ia menyambung tali silaturahmi."
                </div>
                <div style="font-size: 8.5pt; color: #15803d; text-align: right; margin-top: 4px; font-weight: 600;">
                    (HR. Al-Bukhari No. 5986 & Muslim No. 2557)
                </div>
            </div>

            {{-- Hadits Box 2 --}}
            <div style="background: #fffbeb; border-left: 4px solid #d97706; border-radius: 0 10px 10px 0; padding: 14px 18px; margin-bottom: 24px;">
                <div style="font-size: 9pt; font-weight: 700; color: #92400e; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em;">
                    📖 Hadits Riwayat At-Tirmidzi
                </div>
                <div style="font-size: 10.5pt; color: #78350f; font-style: italic; line-height: 1.55;">
                    "Pelajarilah dari nasab-nasab kalian apa-apa yang dapat kalian gunakan untuk menyambung tali silaturahmi kalian..."
                </div>
                <div style="font-size: 8.5pt; color: #b45309; text-align: right; margin-top: 4px; font-weight: 600;">
                    (HR. At-Tirmidzi No. 1979)
                </div>
            </div>

            {{-- Introductory Narrative --}}
            <div style="font-size: 10pt; line-height: 1.7; color: #334155; margin-bottom: 24px; text-align: justify;">
                <p style="margin-bottom: 10px;">
                    Segala puji bagi Allah Tuhan Semesta Alam yang telah menciptakan manusia bersuku-suku dan berbangsa-bangsa agar saling mengenal. Buku Silsilah Keluarga <strong>{{ $tree->name }}</strong> ini disusun sebagai ikhtiar berharga untuk mencatat, menjaga, dan merawat silsilah garis keturunan. Dengan catatan ini, diharapkan setiap keturunan, anak, cucu, cicit, hingga generasi penerus berikutnya senantiasa mengenali akar silsilahnya, mempererat ikatan persaudaraan, dan tidak terputus tali nasabnya.
                </p>
            </div>

            {{-- Executive Statistics Grid (Infographic) --}}
            <div style="margin-bottom: 26px;">
                <h3 style="font-size: 11pt; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 6px;">
                    📊 Ringkasan Statistik Keluarga
                </h3>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                    {{-- Stat 1 --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 8pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Anggota</div>
                        <div style="font-size: 18pt; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ $stats['total_members'] }}</div>
                        <div style="font-size: 8pt; color: #94a3b8;">Orang Terdata</div>
                    </div>

                    {{-- Stat 2 --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 8pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Rentang Generasi</div>
                        <div style="font-size: 18pt; font-weight: 800; color: #059669; margin-top: 2px;">{{ $stats['total_generations'] }}</div>
                        <div style="font-size: 8pt; color: #94a3b8;">Tingkatan Generasi</div>
                    </div>

                    {{-- Stat 3 --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 8pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Pernikahan</div>
                        <div style="font-size: 18pt; font-weight: 800; color: #d97706; margin-top: 2px;">{{ $stats['total_marriages'] }}</div>
                        <div style="font-size: 8pt; color: #94a3b8;">Pasangan Terdaftar</div>
                    </div>

                    {{-- Stat 4 --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 8pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Komposisi Gender</div>
                        <div style="font-size: 13pt; font-weight: 800; color: #0284c7; margin-top: 4px;">
                            {{ $stats['total_male'] }} <span style="font-size: 9pt; font-weight: 500; color: #64748b;">Pria</span> • {{ $stats['total_female'] }} <span style="font-size: 9pt; font-weight: 500; color: #64748b;">Wanita</span>
                        </div>
                        <div style="font-size: 8pt; color: #94a3b8;">Keseimbangan Garis</div>
                    </div>

                    {{-- Stat 5 --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 8pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Status Keberadaan</div>
                        <div style="font-size: 13pt; font-weight: 800; color: #16a34a; margin-top: 4px;">
                            {{ $stats['total_living'] }} <span style="font-size: 9pt; font-weight: 500; color: #64748b;">Hidup</span> • {{ $stats['total_deceased'] }} <span style="font-size: 9pt; font-weight: 500; color: #64748b;">Wafat</span>
                        </div>
                        <div style="font-size: 8pt; color: #94a3b8;">Semoga Diberkahi & Dirahmati</div>
                    </div>

                    {{-- Stat 6 --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 8pt; font-weight: 700; color: #64748b; text-transform: uppercase;">Penerbitan</div>
                        <div style="font-size: 11pt; font-weight: 700; color: #475569; margin-top: 6px;">{{ $stats['printed_at'] }}</div>
                        <div style="font-size: 8pt; color: #94a3b8;">Edisi Resmi Cetak</div>
                    </div>
                </div>
            </div>

            {{-- Showcase Leluhur Pendiri (Root Ancestors) --}}
            <div style="background: #faf5ff; border: 1.5px solid #d8b4fe; border-radius: 10px; padding: 14px 18px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="font-size: 14px;">👑</span>
                    <h4 style="font-size: 10pt; font-weight: 700; color: #6b21a8; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">
                        Pucuk Silsilah & Leluhur Pendiri (Generasi I)
                    </h4>
                </div>
                <div style="font-size: 9.5pt; color: #581c87; line-height: 1.6;">
                    Silsilah ini berakar dan bermula dari:
                    @foreach($rootMembers as $idx => $root)
                        <strong>{{ trim($root->first_name . ' ' . $root->last_name) }}</strong>@if($root->birth_date) (l. {{ $root->birth_date->format('Y') }})@endif@if(!$loop->last), @else.@endif
                    @endforeach
                    Seluruh keturunan yang tercatat dalam lembar-lembar berikutnya bersumber dari pertalian darah dan nasab luhur ini.
                </div>
            </div>
        </div>

        {{-- Running Footer --}}
        <div class="page-running-footer">
            <span>Silsilah.org — Platform Silsilah Keluarga Digital</span>
            <span>Halaman 2</span>
        </div>
    </div>


    {{-- ======================================================== --}}
    {{-- 3. DIREKTORI SILSILAH PER GENERASI                      --}}
    {{-- ======================================================== --}}
    @php
        $pageCounter = 3;
    @endphp

    @foreach($membersByGen as $genNumber => $members)
        <div class="book-page page-break" style="padding: 16mm;">
            {{-- Running Header --}}
            <div class="page-running-header">
                <span>{{ $tree->name }}</span>
                <span>{{ $generationLabels[$genNumber] ?? "Generasi {$genNumber}" }}</span>
            </div>

            <div style="padding-top: 8mm;">
                {{-- Generation Chapter Banner --}}
                <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 16px;">
                    <div>
                        <span style="font-size: 8.5pt; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.1em;">
                            Bab {{ $genNumber }}
                        </span>
                        <h2 class="serif-title" style="font-size: 16pt; font-weight: 800; color: #0f172a; margin: 2px 0 0 0;">
                            {{ $generationLabels[$genNumber] ?? "Generasi {$genNumber}" }}
                        </h2>
                    </div>
                    <div style="background: #f1f5f9; border-radius: 999px; padding: 4px 12px; font-size: 9pt; font-weight: 600; color: #475569;">
                        {{ $members->count() }} Anggota
                    </div>
                </div>

                {{-- Member Cards Grid (Avoid Break to prevent awkward splits) --}}
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach($members as $m)
                        @php
                            $fullName = trim("{$m->first_name} {$m->last_name}");
                            
                            // Avatar resolution
                            $avatarUrl = null;
                            if ($m->photo) {
                                $avatarUrl = str_starts_with($m->photo, 'http') ? $m->photo : asset('storage/' . $m->photo);
                            } elseif ($m->avatar_id) {
                                $avatarUrl = asset('images/avatar/' . $m->avatar_id);
                            }

                            // Parents
                            $father = $m->father_id ? $allMembers->firstWhere('id', $m->father_id) : null;
                            $mother = $m->mother_id ? $allMembers->firstWhere('id', $m->mother_id) : null;

                            // Spouses
                            $spouses = collect();
                            if ($m->gender === 'male') {
                                foreach ($m->marriagesAsHusband as $mr) {
                                    $w = $allMembers->firstWhere('id', $mr->wife_id);
                                    if ($w) $spouses->push(['member' => $w, 'marriage' => $mr]);
                                }
                            } else {
                                foreach ($m->marriagesAsWife as $mr) {
                                    $h = $allMembers->firstWhere('id', $mr->husband_id);
                                    if ($h) $spouses->push(['member' => $h, 'marriage' => $mr]);
                                }
                            }

                            // Children
                            $childField = $m->gender === 'male' ? 'father_id' : 'mother_id';
                            $children = $allMembers->where($childField, $m->id)
                                ->sortBy([
                                    fn ($a, $b) => ($a->birth_date ? $a->birth_date->timestamp : PHP_INT_MAX) <=> ($b->birth_date ? $b->birth_date->timestamp : PHP_INT_MAX),
                                    fn ($a, $b) => ($a->order ?? 999) <=> ($b->order ?? 999),
                                    fn ($a, $b) => $a->id <=> $b->id,
                                ]);
                        @endphp

                        <div class="avoid-break" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                            <div style="display: flex; gap: 14px; align-items: flex-start;">
                                
                                {{-- Left: Avatar & Gender --}}
                                <div style="flex-shrink: 0; text-align: center; width: 56px;">
                                    <div style="width: 52px; height: 52px; border-radius: 50%; overflow: hidden; border: 2px solid {{ $m->gender === 'male' ? '#38bdf8' : '#f472b6' }}; background: #f8fafc; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                                        @if($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="{{ $fullName }}" style="width: 100%; height: 100%; object-fit: cover;" />
                                        @else
                                            <span style="font-size: 18px; font-weight: 700; color: {{ $m->gender === 'male' ? '#0284c7' : '#db2777' }};">
                                                {{ mb_substr($m->first_name, 0, 1) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div style="margin-top: 4px;">
                                        <span style="font-size: 7.5pt; font-weight: 700; padding: 2px 6px; border-radius: 4px; background: {{ $m->gender === 'male' ? '#e0f2fe' : '#fce7f3' }}; color: {{ $m->gender === 'male' ? '#0369a1' : '#be185d' }};">
                                            {{ $m->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Right: Details --}}
                                <div style="flex-grow: 1;">
                                    {{-- Name & Status Bar --}}
                                    <div style="display: flex; justify-content: space-between; align-items: baseline; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; margin-bottom: 6px;">
                                        <div>
                                            <span style="font-size: 11pt; font-weight: 800; color: #0f172a;">
                                                {{ $fullName }}
                                            </span>
                                            <span style="font-size: 8pt; font-family: monospace; color: #64748b; margin-left: 6px;">
                                                #M{{ $m->id }}
                                            </span>
                                        </div>
                                        <div>
                                            @if($m->is_living)
                                                <span style="background: #dcfce7; color: #15803d; font-size: 7.5pt; font-weight: 700; padding: 2px 8px; border-radius: 999px;">
                                                    ● Masih Hidup
                                                </span>
                                            @else
                                                <span style="background: #f1f5f9; color: #475569; font-size: 7.5pt; font-weight: 700; padding: 2px 8px; border-radius: 999px;">
                                                    ✝ Wafat (Rahimahullah)
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Key Attributes Grid --}}
                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px 12px; font-size: 8.5pt; line-height: 1.45; color: #334155;">
                                        
                                        {{-- Lahir / Wafat --}}
                                        <div>
                                            <span style="color: #64748b;">Kelahiran:</span>
                                            <strong>{{ $m->birth_date ? $m->birth_date->locale('id')->isoFormat('D MMMM Y') : '-' }}</strong>
                                            @if($m->birth_place) <span style="color: #64748b;">di {{ $m->birth_place }}</span> @endif
                                        </div>

                                        @if(!$m->is_living)
                                            <div>
                                                <span style="color: #64748b;">Wafat:</span>
                                                <strong>{{ $m->death_date ? $m->death_date->locale('id')->isoFormat('D MMMM Y') : '-' }}</strong>
                                                @if($m->death_place) <span style="color: #64748b;">di {{ $m->death_place }}</span> @endif
                                            </div>
                                        @else
                                            <div>
                                                <span style="color: #64748b;">Profesi:</span>
                                                <strong>{{ $m->profession ?: '-' }}</strong>
                                            </div>
                                        @endif

                                        {{-- Orang Tua --}}
                                        <div style="grid-column: span 2;">
                                            <span style="color: #64748b;">Orang Tua:</span>
                                            @if($father || $mother)
                                                Ayah: <strong>{{ $father ? trim($father->first_name . ' ' . $father->last_name) : '-' }}</strong>
                                                &nbsp;|&nbsp;
                                                Ibu: <strong>{{ $mother ? trim($mother->first_name . ' ' . $mother->last_name) : '-' }}</strong>
                                            @else
                                                <span style="color: #94a3b8; font-style: italic;">Leluhur Utama / Tidak tercatat</span>
                                            @endif
                                        </div>

                                        {{-- Pasangan --}}
                                        @if($spouses->isNotEmpty())
                                            <div style="grid-column: span 2;">
                                                <span style="color: #64748b;">Pasangan:</span>
                                                @foreach($spouses as $sp)
                                                    <strong>{{ trim($sp['member']->first_name . ' ' . $sp['member']->last_name) }}</strong>
                                                    @if($sp['marriage']->marriage_date)
                                                        <span style="color: #64748b;">(Nikah: {{ \Carbon\Carbon::parse($sp['marriage']->marriage_date)->format('Y') }})</span>
                                                    @endif
                                                    @if(!$loop->last), @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- Anak-anak --}}
                                        @if($children->isNotEmpty())
                                            <div style="grid-column: span 2; background: #f8fafc; border-radius: 6px; padding: 4px 8px; margin-top: 2px;">
                                                <span style="color: #64748b; font-weight: 600;">Putera/Puteri ({{ $children->count() }} orang):</span>
                                                <div style="margin-top: 2px; color: #1e293b;">
                                                    @foreach($children as $cIdx => $child)
                                                        <span>
                                                            {{ $loop->iteration }}. <strong>{{ trim($child->first_name . ' ' . $child->last_name) }}</strong> 
                                                            <span style="color: #64748b; font-size: 7.5pt;">(Gen {{ $memberGenerations[$child->id] ?? ($genNumber + 1) }})</span>@if(!$loop->last), @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Bio / Notes if present --}}
                                        @if($m->bio || $m->address)
                                            <div style="grid-column: span 2; font-size: 8pt; color: #475569; margin-top: 2px;">
                                                @if($m->address) <span style="color: #64748b;">Domisili: {{ $m->address }}</span> @endif
                                                @if($m->bio) &bull; <span style="font-style: italic;">"{{ Str::limit($m->bio, 120) }}"</span> @endif
                                            </div>
                                        @endif

                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Running Footer --}}
            <div class="page-running-footer">
                <span>Buku Silsilah: {{ $tree->name }}</span>
                <span>Halaman {{ $pageCounter++ }}</span>
            </div>
        </div>
    @endforeach


    {{-- ======================================================== --}}
    {{-- 4. INDEKS NAMA ANGGOTA ALFABETIS (A — Z)                --}}
    {{-- ======================================================== --}}
    <div class="book-page" style="padding: 16mm;">
        {{-- Running Header --}}
        <div class="page-running-header">
            <span>{{ $tree->name }}</span>
            <span>Indeks Alfabetis Anggota (A — Z)</span>
        </div>

        <div style="padding-top: 8mm;">
            <div style="text-align: center; margin-bottom: 16px;">
                <h2 class="serif-title" style="font-size: 16pt; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">
                    INDEKS ANGGOTA KELUARGA (A — Z)
                </h2>
                <div style="font-size: 8.5pt; color: #64748b;">
                    Daftar pencarian cepat seluruh anggota keluarga beserta generasi dan nomor referensi
                </div>
                <div style="height: 1.5px; width: 50px; background: #0f172a; margin: 6px auto 0 auto;"></div>
            </div>

            <table style="width: 100%; border-collapse: collapse; font-size: 8pt; color: #1e293b;">
                <thead>
                    <tr style="background: #f1f5f9; border-top: 1.5px solid #0f172a; border-bottom: 1.5px solid #0f172a;">
                        <th style="padding: 5px 6px; text-align: left; width: 28px;">No</th>
                        <th style="padding: 5px 6px; text-align: left;">Nama Lengkap</th>
                        <th style="padding: 5px 6px; text-align: center; width: 40px;">L/P</th>
                        <th style="padding: 5px 6px; text-align: center; width: 50px;">Gen</th>
                        <th style="padding: 5px 6px; text-align: left; width: 100px;">Kelahiran</th>
                        <th style="padding: 5px 6px; text-align: left;">Orang Tua / Pasangan</th>
                        <th style="padding: 5px 6px; text-align: center; width: 65px;">Status</th>
                        <th style="padding: 5px 6px; text-align: right; width: 50px;">ID</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($indexMembers as $idx => $m)
                        @php
                            $father = $m->father_id ? $allMembers->firstWhere('id', $m->father_id) : null;
                            $mother = $m->mother_id ? $allMembers->firstWhere('id', $m->mother_id) : null;
                            $relationHint = '-';
                            if ($father) {
                                $relationHint = 'Anak dari ' . $father->first_name;
                            } elseif ($mother) {
                                $relationHint = 'Anak dari ' . $mother->first_name;
                            }
                        @endphp
                        <tr style="border-bottom: 1px solid #e2e8f0; {{ $idx % 2 === 1 ? 'background: #f8fafc;' : '' }}">
                            <td style="padding: 4px 6px; color: #64748b;">{{ $idx + 1 }}</td>
                            <td style="padding: 4px 6px; font-weight: 700; color: #0f172a;">
                                {{ trim($m->first_name . ' ' . $m->last_name) }}
                            </td>
                            <td style="padding: 4px 6px; text-align: center; color: {{ $m->gender === 'male' ? '#0284c7' : '#db2777' }}; font-weight: 700;">
                                {{ $m->gender === 'male' ? 'L' : 'P' }}
                            </td>
                            <td style="padding: 4px 6px; text-align: center; font-weight: 600; color: #059669;">
                                Gen {{ $memberGenerations[$m->id] ?? 1 }}
                            </td>
                            <td style="padding: 4px 6px; color: #475569;">
                                {{ $m->birth_date ? $m->birth_date->format('d/m/Y') : '-' }}
                            </td>
                            <td style="padding: 4px 6px; color: #64748b; font-size: 7.5pt;">
                                {{ $relationHint }}
                            </td>
                            <td style="padding: 4px 6px; text-align: center;">
                                @if($m->is_living)
                                    <span style="color: #16a34a; font-weight: 600;">Hidup</span>
                                @else
                                    <span style="color: #64748b;">Wafat</span>
                                @endif
                            </td>
                            <td style="padding: 4px 6px; text-align: right; font-family: monospace; color: #94a3b8;">
                                #M{{ $m->id }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Running Footer --}}
        <div class="page-running-footer">
            <span>Silsilah.org — Dokumen Resmi Silsilah Keluarga</span>
            <span>Halaman {{ $pageCounter }}</span>
        </div>
    </div>

</body>
</html>