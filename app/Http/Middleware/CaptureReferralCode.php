<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferralCode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('ref') && $request->filled('ref')) {
            session(['referral_code' => strtoupper(trim((string) $request->input('ref')))]);
        }

        return $next($request);
    }
}
