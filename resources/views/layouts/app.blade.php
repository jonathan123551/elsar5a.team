<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'فريق الصرخة المسرحي')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#020617">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @hasSection('meta_robots')
        <meta name="robots" content="@yield('meta_robots')">
    @endif
    <meta name="description" content="@yield('meta_description', 'فريق الصرخة المسرحي — حجز تذاكر العروض أونلاين.')">
    <link rel="icon" type="image/png" href="{{ asset('images/sarkha-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/sarkha-logo.png') }}">

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-tap-highlight-color: transparent;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        /* Mobile-friendly tap targets — every clickable element on
           the public + admin surfaces should be at least 44×44 with
           a little visual feedback. Scoped to the elements the rest
           of the design system already uses so we don't surprise
           desktop UIs. iOS in particular needs an explicit
           -webkit-tap-highlight-color override to avoid the default
           grey flash. */
        button,
        [role="button"],
        input[type="submit"],
        input[type="button"],
        a {
            -webkit-tap-highlight-color: transparent;
        }
        button,
        input[type="submit"],
        input[type="button"] {
            touch-action: manipulation; /* kill 300ms double-tap zoom delay on iOS */
        }
        input,
        select,
        textarea {
            /* iOS Safari auto-zooms into any input under 16px on
               focus, which yanks the layout. Force a minimum logical
               font-size on small viewports so the keyboard appears
               without zooming. */
            font-size: 16px;
        }
        @media (min-width: 640px) {
            input, select, textarea { font-size: inherit; }
        }

        /* Safari iOS sometimes leaves a phantom rubber-band white
           strip behind the dark stage background when the user
           overscrolls. Anchor html/body to the stage color so it
           never bleeds through. */
        html { background-color: #020617; }

        /* خلفية أجواء مسرح */
        .stage-bg {
            background:
                radial-gradient(circle at top, rgba(255,255,255,0.14), transparent 55%),
                radial-gradient(circle at 20% 0, rgba(251,191,36,0.2), transparent 60%),
                radial-gradient(circle at 80% 0, rgba(239,68,68,0.25), transparent 60%),
                #020617;
        }

        .scream-hero {
            position: relative;
            overflow: hidden;
        }
        .scream-hero::before {
            content: "";
            position: absolute;
            inset: -40%;
            background:
                radial-gradient(circle at 10% 0, rgba(251,191,36,0.22), transparent 60%),
                radial-gradient(circle at 90% 10%, rgba(248,113,113,0.28), transparent 60%);
            opacity: 0.9;
            filter: blur(30px);
            z-index: -1;
        }
        .scream-border {
            border-radius: 1.5rem;
            background: linear-gradient(135deg, rgba(250,204,21,0.5), rgba(248,113,113,0.5));
            padding: 1px;
        }
        .scream-card {
            border-radius: 1.4rem;
            background: radial-gradient(circle at top, rgba(15,23,42,0.95), rgba(2,6,23,0.96));
        }

        @keyframes screamGlow {
            0%, 100% { text-shadow: 0 0 10px rgba(250,204,21,0.4); transform: translateY(0); }
            50% { text-shadow: 0 0 22px rgba(248,113,113,0.9); transform: translateY(-2px); }
        }
        .scream-title {
            animation: screamGlow 2.4s ease-in-out infinite;
        }

        @keyframes screamPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(250,204,21,0.0); }
            50% { box-shadow: 0 0 35px 0 rgba(250,204,21,0.45); }
        }
        .scream-pulse {
            animation: screamPulse 3s ease-in-out infinite;
        }

        .logo-light {
            filter: drop-shadow(0 0 25px rgba(255,255,255,0.7));
        }
    </style>
    <style>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

/* =========================================================
   STICKY ACTION FOOTER
   ---------------------------------------------------------
   Native-feeling sticky action bar pattern used across the
   public booking flow and admin approval/edit screens.

   How it works
   ------------
   Pages add `data-sticky-action` to the natural in-flow
   action container (Approve/Reject buttons, "Submit
   booking" button, "Save changes" button, etc.).

   On boot we clone each natural action into a fixed footer
   pinned to the bottom of the viewport. While the natural
   action is OFF-SCREEN we fade the floating clone in; the
   instant the user scrolls far enough to see the real
   action in its natural place at the bottom of the page,
   we fade the clone back out so the layout never has two
   competing CTAs visible at once.

   Clones don't double-submit — they synthesize a click on
   the original control, which goes through the original
   form's submit handler (including any in-progress /
   disabled state).
   ========================================================= */
#sticky-action-footer {
    position: fixed;
    inset-inline: 0;
    bottom: 0;
    z-index: 60;
    padding: 10px 14px max(10px, env(safe-area-inset-bottom)) 14px;
    background: linear-gradient(180deg, rgba(2,6,23,0) 0%, rgba(2,6,23,0.85) 35%, rgba(2,6,23,0.96) 100%);
    border-top: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(14px) saturate(140%);
    -webkit-backdrop-filter: blur(14px) saturate(140%);
    opacity: 0;
    pointer-events: none;
    transform: translateY(12px);
    transition: opacity .22s cubic-bezier(.2,.7,.2,1), transform .22s cubic-bezier(.2,.7,.2,1);
}
#sticky-action-footer.is-visible {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}
#sticky-action-footer .sa-inner {
    max-width: 64rem;
    margin: 0 auto;
}
#sticky-action-footer .sa-inner > * {
    width: 100%;
}
#sticky-action-footer .sa-inner button,
#sticky-action-footer .sa-inner a,
#sticky-action-footer .sa-inner input[type="submit"] {
    min-height: 48px;
}

/* Tiny inline spinner used by submit buttons while their
   handler is in-flight. Kept generic so any form on the site
   can opt in via `is-loading` on the button. */
.btn-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid currentColor;
    border-top-color: transparent;
    animation: btnSpin .7s linear infinite;
    vertical-align: -2px;
    margin-inline-end: 6px;
}
@keyframes btnSpin { to { transform: rotate(360deg); } }
button.is-loading,
input[type="submit"].is-loading {
    opacity: 0.85;
    cursor: progress;
}

/* When a `data-sticky-action` block is present we pad the
   bottom of the main content so the floating CTA never
   covers the last paragraph / submit button on short
   viewports. Calculated to clear the footer + safe area. */
body.has-sticky-action main {
    padding-bottom: calc(110px + env(safe-area-inset-bottom));
}
</style>

</head>
<body class="stage-bg min-h-screen text-gray-100">

    {{-- Navbar --}}
    <header class="border-b border-white/10 bg-black/40 backdrop-blur sticky top-0 z-40">
    <div class="max-w-5xl mx-auto px-3 sm:px-4 py-2 flex items-center justify-between gap-2">

        {{-- اللوجو + الاسم --}}
        <a href="{{ url('/') }}"
           class="flex items-center gap-2 group focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60 rounded-xl pr-1"
           aria-label="الصفحة الرئيسية">
            <img src="{{ asset('images/sarkha-logo.png') }}"
                 class="w-9 h-9 sm:w-10 sm:h-10 object-contain invert brightness-125
                        drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]
                        transition-transform duration-300 group-hover:scale-105"
                 alt="فريق الصرخة المسرحي">

            <div class="leading-tight">
                <div class="text-[12px] sm:text-sm font-semibold whitespace-nowrap">
                    فريق الصرخة المسرحي
                </div>
                <div class="hidden sm:block text-[10px] text-gray-400">
                    حجز تذاكر العروض أونلاين
                </div>
            </div>
        </a>

        {{-- الناف بار --}}
        <nav aria-label="التنقل"
            class="flex items-center gap-0.5 sm:gap-1 text-[11px] sm:text-sm font-medium
                   bg-black/50 backdrop-blur px-1.5 sm:px-2 py-1
                   rounded-full border border-white/10 max-w-full overflow-x-auto scrollbar-hide">

            {{-- Home (تروح لـ / مباشرة، مو لـ /shows) --}}
            <a href="{{ url('/') }}"
               class="px-2 sm:px-2.5 py-1 rounded-full transition whitespace-nowrap
                      hover:bg-amber-400 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60
                      {{ request()->routeIs('home') || request()->is('/') ? 'bg-amber-400 text-black' : 'text-gray-300' }}">
               الرئيسية
            </a>

            {{-- العروض السابقة --}}
            <a href="{{ route('archive') }}"
               class="px-2 sm:px-2.5 py-1 rounded-full transition whitespace-nowrap
                      hover:bg-amber-400 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60
                      {{ request()->routeIs('archive') ? 'bg-amber-400 text-black' : 'text-gray-300' }}">
                العروض السابقة
            </a>

            {{-- About --}}
            <a href="{{ route('about') }}"
               class="px-2 sm:px-2.5 py-1 rounded-full transition whitespace-nowrap
                      hover:bg-amber-400 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60
                      {{ request()->routeIs('about') ? 'bg-amber-400 text-black' : 'text-gray-300' }}">
                عن الفريق
            </a>
        </nav>

    </div>
</header>


    {{-- المحتوى الرئيسي --}}
    <main class="max-w-5xl mx-auto px-4 py-6 md:py-10">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t border-white/10 bg-black/60 mt-10">
        <div class="max-w-5xl mx-auto px-4 py-5 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-gray-400">
            <div class="text-center md:text-right space-y-1">
                <div>
                    © {{ now()->year }} فريق الصرخة المسرحي — نجول، نصرخ… فيزداد العقل وعيًا.
                </div>
                <div class="text-[10px] text-gray-500">
                    حجز أونلاين • تذاكر QR
                </div>
            </div>

            <nav aria-label="روابط التذييل"
                 class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
                <a href="{{ url('/') }}"
                   class="hover:text-amber-300 transition focus:outline-none focus-visible:underline">
                    الرئيسية
                </a>
                <span class="text-gray-700" aria-hidden="true">•</span>
                <a href="{{ route('about') }}"
                   class="hover:text-amber-300 transition focus:outline-none focus-visible:underline">
                    عن الفريق
                </a>
                <span class="text-gray-700" aria-hidden="true">•</span>
                <a href="{{ route('archive') }}"
                   class="hover:text-amber-300 transition focus:outline-none focus-visible:underline">
                    العروض السابقة
                </a>
                <span class="text-gray-700" aria-hidden="true">•</span>
                <a href="https://www.instagram.com/elsar5a.team"
                   target="_blank" rel="noopener noreferrer"
                   class="hover:text-amber-300 transition focus:outline-none focus-visible:underline">
                    Instagram
                </a>
                <span class="text-gray-700" aria-hidden="true">•</span>
                <a href="https://wa.me/201000000000"
                   target="_blank" rel="noopener noreferrer"
                   class="hover:text-amber-300 transition focus:outline-none focus-visible:underline">
                    تواصل
                </a>
            </nav>
        </div>
    </footer>

    {{--
        Sticky action footer bootstrapper. See the CSS comment above
        for the full design rationale. The script is intentionally
        plain, dependency-free, and gated on the existence of at
        least one `[data-sticky-action]` element so we don't pay
        any runtime cost on pages that don't opt in.
    --}}
    <script>
    (function () {
        function init() {
            var anchors = document.querySelectorAll('[data-sticky-action]');
            if (!anchors.length) return;
            if (!('IntersectionObserver' in window)) return; // graceful no-op on ancient browsers

            document.body.classList.add('has-sticky-action');

            var footer = document.createElement('div');
            footer.id = 'sticky-action-footer';
            footer.setAttribute('role', 'region');
            footer.setAttribute('aria-label', 'إجراءات سريعة');
            footer.innerHTML = '<div class="sa-inner"></div>';
            document.body.appendChild(footer);

            var inner = footer.querySelector('.sa-inner');

            function wireClone(anchor, clone) {
                clone.removeAttribute('data-sticky-action');
                clone.removeAttribute('id');
                clone.querySelectorAll('[id]').forEach(function (n) { n.removeAttribute('id'); });

                var originals = anchor.querySelectorAll('button, a, input[type=submit], input[type=button]');
                var clones    = clone.querySelectorAll('button, a, input[type=submit], input[type=button]');

                clones.forEach(function (c, i) {
                    var orig = originals[i];
                    if (!orig) return;
                    // Demote to a plain button so the clone never tries to
                    // submit its own (cloned, detached) form. We delegate
                    // back to the original control, which preserves any
                    // double-submit guard / disabled state.
                    if (c.tagName === 'BUTTON' || c.tagName === 'INPUT') {
                        c.type = 'button';
                    }
                    // Mirror disabled state explicitly. cloneNode picks up
                    // the original's `disabled` attribute, but it does
                    // NOT pick up `.disabled` set via JS — so a button
                    // that's been programmatically enabled by the form
                    // (e.g. the booking submit becoming amber once the
                    // screenshot is attached) needs us to copy the
                    // current state at clone time.
                    if ('disabled' in c) {
                        c.disabled = !!orig.disabled;
                    }
                    c.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (orig.disabled || orig.classList.contains('is-loading')) return;
                        orig.click();
                    });
                });
            }

            function render(anchor) {
                // Always re-clone on each show so the floating CTA
                // reflects the latest state of the original control
                // (enabled/disabled, label, classes, spinner, etc.).
                inner.innerHTML = '';
                var clone = anchor.cloneNode(true);
                wireClone(anchor, clone);
                inner.appendChild(clone);
            }

            function update() {
                // Hide the floating footer if ANY natural anchor is in view.
                var anyVisible = false;
                var lastAnchor = anchors[anchors.length - 1];
                anchors.forEach(function (a) {
                    var r = a.getBoundingClientRect();
                    var visible = r.top < (window.innerHeight - 40) && r.bottom > 0;
                    if (visible) anyVisible = true;
                });

                if (anyVisible) {
                    footer.classList.remove('is-visible');
                    inner.innerHTML = '';
                } else {
                    render(lastAnchor);
                    footer.classList.add('is-visible');
                }
            }

            // Initial paint + react to scroll, resize, and DOM mutations
            // that affect anchor layout (e.g. form fields expanding).
            update();
            window.addEventListener('scroll', update, { passive: true });
            window.addEventListener('resize', update);
            window.addEventListener('orientationchange', update);

            // ResizeObserver catches cases like "ticket count goes from 1
            // to 3 and the form grows" without us having to manually
            // notify the sticky footer.
            if ('ResizeObserver' in window) {
                var ro = new ResizeObserver(update);
                anchors.forEach(function (a) { ro.observe(a); });
                ro.observe(document.body);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>
</body>
</html>
