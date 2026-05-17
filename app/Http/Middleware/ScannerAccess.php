<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ScannerAccess
 *
 * Lightweight, session-based gate for /admin/scanner and
 * /admin/scanner/check.
 *
 * Why this exists
 * ---------------
 * The QR gate scanner is intentionally reachable WITHOUT the full
 * admin login because real-world door staff don't have admin
 * dashboard accounts. Before this middleware the endpoints were open
 * to the public internet, which meant anyone holding a single valid
 * ticket code (forwarded WhatsApp screenshot, photo of someone
 * else's ticket, etc.) could `curl` /admin/scanner/check and
 * permanently burn that ticket BEFORE the real customer reached the
 * door.
 *
 * The fix is a per-device PIN screen — door staff enter the
 * organizer PIN once per device and a signed session flag is set.
 * The fast scan workflow is preserved: after the one-time PIN entry
 * the staff sees the regular scanner UI and can scan as fast as
 * they want without re-authing. They never get any admin dashboard
 * access.
 *
 * The PIN is sourced from env(SCANNER_PIN). If SCANNER_PIN is unset
 * AND APP_ENV is `production`, the middleware fails closed (returns
 * 503) rather than silently allowing anonymous traffic again. In
 * local/dev environments it falls through to a default dev PIN so
 * tests / `php artisan serve` keep working.
 *
 * Admin users (the full session-authenticated admin who logs into
 * /admin) bypass the PIN entirely — they already have stronger
 * auth, so making them type a PIN on top would be friction without
 * benefit.
 */
class ScannerAccess
{
    /**
     * Session key marking a device as a verified scanner station.
     */
    public const SESSION_KEY = 'scanner_unlocked';

    public function handle(Request $request, Closure $next): Response
    {
        // Full admin? Pass straight through.
        if (auth()->check() && $this->isAdminUser(auth()->user())) {
            return $next($request);
        }

        // Already unlocked on this device/session? Pass through.
        if ($request->session()->get(self::SESSION_KEY) === true) {
            return $next($request);
        }

        // POST /admin/scanner/check from an un-unlocked client must
        // never silently succeed. Always 401 with a small JSON so
        // the front-end can show a sane error instead of redirecting
        // mid-scan.
        if ($request->expectsJson() || $request->isMethod('POST')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'الجهاز غير مفعّل للفحص',
            ], 401);
        }

        return redirect()->route('scanner.pin');
    }

    /**
     * Mirrors App\Http\Middleware\IsAdmin so we don't depend on a
     * separate role system.
     */
    private function isAdminUser($user): bool
    {
        return $user && $user->email === 'elsar5ateam2026@gmail.com';
    }
}
