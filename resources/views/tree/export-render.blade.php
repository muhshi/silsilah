<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    {{-- Inline built CSS directly — no @vite, no external dependencies --}}
    @php
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
        $cssContent = $cssFile ? file_get_contents(public_path('build/' . $cssFile)) : '';
    @endphp
    <style>{!! $cssContent !!}</style>

    <style>
        /* Use system fonts for export — avoids fetching Google Fonts */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            background: #ffffff !important;
            margin: 0;
            padding: 0;
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
    <div style="display: inline-block; padding: 40px;">
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
</body>
</html>
