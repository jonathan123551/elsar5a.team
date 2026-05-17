<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ✅ Middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
        ]);

        // ✅ Apply security headers on every web response. Lightweight
        // header set (HSTS / X-Frame-Options / Referrer-Policy /
        // Permissions-Policy / X-Content-Type-Options) — no CSP yet
        // because of the Tailwind CDN + several inline <script>/<style>
        // blocks. See app/Http/Middleware/SecurityHeaders.php.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // ✅ Disable CSRF for WhatsApp Webhook (VERY IMPORTANT)
        $middleware->validateCsrfTokens(except: [
            'webhook/whatsapp',
            'chatwoot-webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Belt-and-suspenders: never surface the Laravel ignition
        // debug page in production, even if APP_DEBUG is left on by
        // mistake. We render the themed Arabic error views from
        // resources/views/errors/ instead.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {

            if ($request->expectsJson()) {
                return null; // let JSON callers fall through to default
            }

            if (app()->environment('production')) {

                $status = 500;

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    $status = $e->getStatusCode();
                }
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $status = 404;
                }
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return null;
                }

                $view = match (true) {
                    $status === 404 => 'errors.404',
                    $status === 429 => 'errors.429',
                    $status >= 500  => 'errors.500',
                    default         => null,
                };

                if ($view) {
                    return response()->view($view, [], $status);
                }
            }

            return null;
        });
    })
    ->create();
