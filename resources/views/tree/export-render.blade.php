<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    {{-- Inline built CSS directly — no @vite, no external dependencies --}}
    @php
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
        $cssContent = $cssFile ? file_get_contents(public_path('build/' . $cssFile)) : '';
        // Strip external fonts to prevent Puppeteer hanging on network requests
        $cssContent = preg_replace('/@import\s+url\([^)]+\);?/', '', $cssContent);
        $cssContent = preg_replace('/@import\s+"[^"]+";?/', '', $cssContent);
        $cssContent = preg_replace("/@import\s+'[^']+';?/", '', $cssContent);
    @endphp
    <style>{!! $cssContent !!}</style>

    <style>
        /* Use system fonts for export — avoids fetching Google Fonts */
        html, body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            background: #ffffff !important;
            margin: 0;
            padding: 0;
            display: inline-block !important;
            width: max-content !important;
            height: max-content !important;
            min-width: 100vw;
            min-height: 100vh;
        }
        .pt-sm {
            overflow: visible !important;
            min-height: auto !important;
            cursor: default !important;
            border: none !important;
            border-radius: 0 !important;
            background: transparent !important;
        }
        .tree-inner {
            transform: none !important;
        }
        .pt-zoom-controls,
        .pt-options {
            display: none !important;
        }
    </style>
</head>
<body>
    <div id="export-canvas" style="display: inline-block; padding: 40px; width: max-content; height: max-content;">
        @if(($viewType ?? 'horizontal') === 'simple')
            <div class="tree simple-tree" id="simpleTree">
                <ul>
                    @foreach($rootMembers as $member)
                        <x-simple-tree-node :member="$member" :all-members="$allMembers" />
                    @endforeach
                </ul>
            </div>
        @else
            <div class="tree" id="myTree">
                <ul>
                    @foreach($rootMembers as $member)
                        <x-tree-node :member="$member" :all-members="$allMembers" />
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('export-canvas');
            if (canvas) {
                const width = Math.max(canvas.scrollWidth + 80, 1920);
                const height = Math.max(canvas.scrollHeight + 80, 1080);

                const style = document.createElement('style');
                style.innerHTML = `@page { size: ${width}px ${height}px; margin: 0; }`;
                document.head.appendChild(style);

                document.body.style.width = width + 'px';
                document.body.style.height = height + 'px';
            }
        });
    </script>
</body>
</html>
