<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ImageProxyController extends Controller
{
    /**
     * Secure image proxy to bypass CORS for tree exports while preventing SSRF
     */
    public function proxy(Request $request): Response
    {
        $url = $request->query('url');

        if (! $url || ! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(400, 'Invalid or missing URL parameter.');
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        // 1. Only allow HTTP and HTTPS
        if (! in_array($scheme, ['http', 'https'], true)) {
            abort(400, 'Only HTTP and HTTPS protocols are supported.');
        }

        // 2. Block obvious loopback hostnames
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            abort(403, 'Access to local loopback hosts is forbidden.');
        }

        // 3. Resolve host to IP and ensure no private or reserved network addresses
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : gethostbynamel($host);

        if (! $ips || count($ips) === 0) {
            abort(400, 'Host could not be resolved.');
        }

        foreach ($ips as $ip) {
            $isPublicIp = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );

            if (! $isPublicIp || str_starts_with($ip, '127.') || str_starts_with($ip, '0.')) {
                abort(403, 'Access to private, loopback, or reserved IP ranges is forbidden.');
            }
        }

        try {
            // 4. Fetch the image without following open redirects to prevent bypasses
            $response = Http::timeout(8)
                ->withoutRedirecting()
                ->get($url);

            if (! $response->successful()) {
                abort(502, 'Failed to fetch the remote image.');
            }

            // 5. Enforce image Content-Type
            $contentType = $response->header('Content-Type') ?: '';
            $mime = strtolower(trim(explode(';', $contentType)[0]));

            if (! str_starts_with($mime, 'image/')) {
                abort(422, 'The remote resource is not an image.');
            }

            return response($response->body(), 200)
                ->header('Content-Type', $mime)
                ->header('Cache-Control', 'max-age=86400, public');
        } catch (Exception $e) {
            if ($e instanceof HttpExceptionInterface) {
                throw $e;
            }

            abort(502, 'Could not retrieve remote image.');
        }
    }
}
