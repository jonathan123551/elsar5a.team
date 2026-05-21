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
        /* =========================================================
           MOBILE-ZOOM / VIEWPORT STABILITY BASELINE
           ---------------------------------------------------------
           Goals (in priority order):
             1. NEVER auto-zoom on input focus (iOS Safari does this
                whenever a form control's computed font-size is
                < 16px).
             2. NEVER fire the 300ms double-tap zoom delay on
                interactive elements.
             3. NEVER let `:hover` styles stick after a tap on
                touch devices (the "I tapped a card and now it's
                permanently bigger" bug).
             4. NEVER bounce the layout when the iOS address bar
                collapses (use 100dvh, not 100vh).
             5. Preserve pinch-to-zoom for accessibility. We do NOT
                set `user-scalable=no` or `maximum-scale=1` in the
                viewport meta — those break low-vision users for
                no real product benefit.

           See viewport <meta> above for the meta-tag side of this:
           `width=device-width, initial-scale=1, viewport-fit=cover`
           with NO max/min scale, so pinch-zoom remains available.
           ========================================================= */
        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
            /* Stage colour anchored on the root element so the iOS
               overscroll rubber-band doesn't reveal a white strip. */
            background-color: #020617;
            /* Use the dynamic viewport so the iOS address-bar
               collapse doesn't trigger a layout shift. 100vh is the
               fallback for older browsers. */
            min-height: 100vh;
            min-height: 100dvh;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-tap-highlight-color: transparent;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            /* Prevent rubber-band overscroll from propagating to
               sticky elements (the navbar and sticky-action footer
               jitter without this on iOS). */
            overscroll-behavior-y: contain;
        }

        /* Mobile-friendly tap targets — every clickable element on
           the public + admin surfaces should be at least 44×44 with
           a little visual feedback. iOS in particular needs an
           explicit -webkit-tap-highlight-color override to avoid the
           default grey flash. */
        button,
        [role="button"],
        input[type="submit"],
        input[type="button"],
        a,
        label,
        summary {
            -webkit-tap-highlight-color: transparent;
            /* `touch-action: manipulation` keeps pinch-zoom alive
               globally but suppresses the 300ms double-tap-zoom
               delay *on this element*. Exactly the
               accessibility-preserving behaviour we want. */
            touch-action: manipulation;
        }

        /* iOS Safari auto-zooms into ANY form control whose
           computed font-size is below 16px on focus, which yanks
           the layout. `!important` here defeats Tailwind utility
           classes like `text-sm` / `text-[14px]` applied on parents
           (Tailwind's Preflight sets `input { font-size: 100% }`,
           so the input inherits whatever the parent says).
           Excludes hidden / range / checkbox / radio because their
           size is unrelated to the zoom heuristic. */
        @media (max-width: 639.98px) {
            input:not([type="hidden"]):not([type="range"]):not([type="checkbox"]):not([type="radio"]),
            select,
            textarea {
                font-size: 16px !important;
                /* `text-size-adjust: 100%` on inputs in particular
                   keeps Safari from rescaling text inside the input
                   while typing on landscape orientation. */
                -webkit-text-size-adjust: 100%;
                text-size-adjust: 100%;
            }
        }

        /* =========================================================
           Touch-device :hover lock fix.
           ---------------------------------------------------------
           Mobile Safari and Chrome-on-Android treat the FIRST tap
           on a hover-styled element as a "hover activation" — the
           :hover state remains matched until the user taps
           somewhere else. Tailwind utilities like
           `hover:scale-105` / `group-hover:scale-[1.035]` therefore
           leave cards visibly enlarged after a tap, which is the
           "certain sections suddenly become enlarged" symptom in
           the bug report.

           `@media (hover: none)` matches devices without a real
           hover capability (i.e. touch devices), and the selector
           below neutralises transform-based hover effects there
           while leaving non-hover animations (button spinners,
           pulse keyframes, etc.) and desktop hover effects intact.
           ========================================================= */
        @media (hover: none) {
            [class*="scale-"]:hover,
            .group:hover [class*="scale-"],
            .group:focus-within [class*="scale-"] {
                transform: none !important;
            }
        }

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

    {{-- ================= Footer =================
         Minimal, cinematic, theater-curtain inspired. The old pages/links
         block was removed by request — navigation already lives in the
         header, so the footer doesn't need to duplicate it. We keep only
         the brand whisper and a thin amber→red gradient hairline that
         echoes the stage-curtain motif used elsewhere on the site. --}}
    <footer class="mt-12 sm:mt-16" role="contentinfo">
        {{-- Stage-curtain hairline. Three stacked layers so it reads as
             a glowing seam rather than a flat border, but it costs us
             nothing at runtime — pure CSS gradients. --}}
        <div aria-hidden="true" class="relative h-px w-full overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-l from-transparent via-amber-300/30 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-l from-transparent via-red-400/20 to-transparent blur-[1px]"></div>
        </div>

        <div class="relative">
            {{-- Subtle stage glow under the hairline. Pointer-events
                 disabled so it never blocks interactive content above. --}}
            <div aria-hidden="true"
                 class="pointer-events-none absolute inset-x-0 -top-6 mx-auto h-12 max-w-md
                        bg-gradient-to-t from-transparent via-amber-400/5 to-amber-300/0 blur-2xl"></div>

            <div class="relative max-w-5xl mx-auto px-4 py-6 sm:py-8 text-center">
                <p class="text-[11px] sm:text-xs tracking-[0.18em] uppercase text-gray-500">
                    Elsar5a Theatre Team
                </p>
                <p class="mt-2 text-[13px] sm:text-sm text-gray-300/90 italic">
                    نجول، نصرخ… فيزداد العقل وعيًا
                </p>
                <p class="mt-3 text-[10px] sm:text-[11px] text-gray-500">
                    © {{ now()->year }} فريق الصرخة المسرحي
                </p>
            </div>
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
