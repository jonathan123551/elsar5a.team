<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // لو مش لوجين أصلاً → رجّعه لصفحة الدخول
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        // لو مش هو إيميل الأدمن → 403
        if (auth()->user()->email !== 'elsar5ateam2026@gmail.com') {
            abort(403, 'غير مصرح لك بدخول لوحة التحكم.');
        }

        return $next($request);
    }
}
