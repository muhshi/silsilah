<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceDetector
{
    /**
     * Check if the incoming HTTP request originates from a mobile handheld device (HP / smartphone).
     */
    public static function isMobile(?Request $request = null): bool
    {
        $request ??= request();
        $userAgent = $request->userAgent() ?? '';

        if (empty($userAgent)) {
            return false;
        }

        // Match common smartphone indicators (Android Mobile, iPhone, iPod, BlackBerry, Windows Phone, Opera Mini, etc.)
        // Excludes standalone desktop and large tablet agents without 'mobile' token.
        $pattern = '/(android.*mobile|iphone|ipod|blackberry|iemobile|opera mini|mobile safari|webos|ucbrowser.*mobile|windows phone|mobile)/i';

        return (bool) preg_match($pattern, $userAgent);
    }

    /**
     * Determine default tree view mode based on explicit query parameters and device type.
     * Returns 'vertical' (Fokus) for mobile devices, or 'horizontal' (Canvas / Bagan) for desktop.
     *
     * @return 'horizontal'|'vertical'|'simple'
     */
    public static function resolveTreeViewType(Request $request): string
    {
        $view = strtolower(trim((string) $request->query('view', '')));

        if (in_array($view, ['canvas', 'horizontal', 'bagan'], true)) {
            return 'horizontal';
        }

        if (in_array($view, ['vertical', 'fokus', 'focus'], true)) {
            return 'vertical';
        }

        if (in_array($view, ['simple'], true)) {
            return 'simple';
        }

        // Default: Mobile (HP) gets 'vertical' (Fokus), Desktop/Laptop gets 'horizontal' (Canvas)
        return self::isMobile($request) ? 'vertical' : 'horizontal';
    }
}
