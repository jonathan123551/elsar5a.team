<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds a small set of production-grade security headers to every
 * response. Lightweight on purpose — no CSP yet because the site
 * uses the Tailwind CDN + inline <script> + inline <style> in
 * several blade files and a fully locked-down CSP would break them
 * until those are migrated. The headers below are safe with the
 * current stack:
 *
 *   - Strict-Transport-Security: forces HTTPS for the next year on
 *     anything that has hit the site once. Railway serves HTTPS so
 *     this is safe in production.
 *   - X-Content-Type-Options: blocks MIME-sniffing on uploaded
 *     ticket-template images / posters.
 *   - X-Frame-Options: prevents clickjacking by disallowing the
 *     site from being framed.
 *   - Referrer-Policy: stops referrer URLs from leaking ticket
 *     reference codes to third parties.
 *   - Permissions-Policy: explicitly allows `camera=self` so the
 *     scanner keeps working, and denies microphone / geolocation
 *     so a compromised script can't silently turn those on.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;

        if (! $headers->has('Strict-Transport-Security')) {
            $headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set(
            'Permissions-Policy',
            'camera=(self), microphone=(), geolocation=()'
        );

        return $response;
    }
}
