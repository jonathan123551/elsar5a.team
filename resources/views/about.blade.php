@extends('layouts.app')

@section('title', 'عن فريق الصرخة المسرحي')

@section('content')
{{--
    "About" page — visual redesign only.

    The DB-backed copy ($about->description, $about->founded_year and
    the social links) is preserved verbatim. The page is restructured
    around three sections so the same content reads as a cinematic
    story instead of a stack of identical bordered rows:

      1. HERO   — title, soft accent line, founded-year chip
      2. CREED  — the multi-line description rendered as a single
                  pull-quote-style block, with each non-empty line
                  becoming a paragraph (preserves the admin's
                  line-by-line authoring style).
      3. CONNECT — social links + return CTA.

    Decorations (gradient orbs, spotlight) are pointer-events:none and
    aria-hidden so they never interfere with touch / assistive tech.
    All motion respects prefers-reduced-motion.
--}}

<section class="about-shell" dir="rtl">

    {{-- Decorative backdrop (purely visual, no semantic content). --}}
    <div class="about-backdrop" aria-hidden="true">
        <span class="about-orb about-orb-a"></span>
        <span class="about-orb about-orb-b"></span>
        <span class="about-grain"></span>
    </div>

    {{-- ============ HERO ============ --}}
    <header class="about-hero about-reveal">
        <span class="about-eyebrow">
            <span class="about-eyebrow-dot" aria-hidden="true"></span>
            <span>فريق الصرخة المسرحي</span>
        </span>

        <h1 class="about-title">
            <span class="about-title-line">عن</span>
            <span class="about-title-line about-title-accent">الصرخة</span>
        </h1>

        <span class="about-divider" aria-hidden="true"></span>

        @if($about && $about->founded_year)
            <div class="about-founded">
                <span class="about-founded-label">منذ عام</span>
                <span class="about-founded-year">{{ $about->founded_year }}</span>
            </div>
        @endif
    </header>

    {{-- ============ CREED ============ --}}
    @if($about && $about->description)
        @php
            // Preserve the admin's line-by-line authoring style without
            // dropping the existing copy.
            $lines = array_values(array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', $about->description)),
                fn ($l) => $l !== ''
            ));
        @endphp

        <article class="about-creed about-reveal">
            <span class="about-quote-mark about-quote-mark-open" aria-hidden="true">”</span>

            <div class="about-creed-body">
                @foreach($lines as $i => $line)
                    <p class="about-creed-line"
                       style="--i: {{ $i }};">
                        {{ $line }}
                    </p>
                @endforeach
            </div>

            <span class="about-quote-mark about-quote-mark-close" aria-hidden="true">“</span>
        </article>
    @else
        <article class="about-empty about-reveal">
            <span aria-hidden="true">🎭</span>
            <p>لم يتم إضافة معلومات عن الفريق بعد.</p>
        </article>
    @endif

    {{-- ============ CONNECT ============ --}}
    @if($about && ($about->youtube || $about->facebook || $about->instagram))
        <section class="about-connect about-reveal" aria-label="تواصل معنا">
            <h2 class="about-connect-title">تابعنا</h2>

            <div class="about-connect-grid">
                @if($about->youtube)
                    <a href="{{ $about->youtube }}"
                       target="_blank" rel="noopener noreferrer"
                       class="about-social about-social-yt"
                       aria-label="YouTube">
                        <svg class="about-social-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2C0 8.1 0 12 0 12s0 3.9.5 5.8a3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1c.5-1.9.5-5.8.5-5.8s0-3.9-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z"/>
                        </svg>
                        <div class="about-social-text">
                            <span class="about-social-label">YouTube</span>
                            <span class="about-social-hint">شاهد عروضنا</span>
                        </div>
                        <span class="about-social-chev" aria-hidden="true">↗</span>
                    </a>
                @endif

                @if($about->facebook)
                    <a href="{{ $about->facebook }}"
                       target="_blank" rel="noopener noreferrer"
                       class="about-social about-social-fb"
                       aria-label="Facebook">
                        <svg class="about-social-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M24 12a12 12 0 1 0-13.9 11.9v-8.4H7v-3.5h3.1V9.4c0-3 1.8-4.7 4.6-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 .9-2 1.9v2.3h3.4l-.5 3.5h-2.9v8.4A12 12 0 0 0 24 12"/>
                        </svg>
                        <div class="about-social-text">
                            <span class="about-social-label">Facebook</span>
                            <span class="about-social-hint">آخر الأخبار</span>
                        </div>
                        <span class="about-social-chev" aria-hidden="true">↗</span>
                    </a>
                @endif

                @if($about->instagram)
                    <a href="{{ $about->instagram }}"
                       target="_blank" rel="noopener noreferrer"
                       class="about-social about-social-ig"
                       aria-label="Instagram">
                        <svg class="about-social-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2 0 1.8.2 2.3.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.5.3 1.1.4 2.3 0 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c0 1.2-.2 1.8-.4 2.3-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.5.2-1.1.3-2.3.4-1.3 0-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2 0-1.8-.2-2.3-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.5-.3-1.1-.4-2.3 0-1.3-.1-1.7-.1-4.9s0-3.6.1-4.9c0-1.2.2-1.8.4-2.3.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.5-.2 1.1-.3 2.3-.4 1.3-.1 1.7-.1 4.9-.1M12 0C8.7 0 8.3 0 7.1.1 5.8.1 5 .3 4.2.6c-.8.3-1.5.7-2.2 1.4C1.3 2.7.9 3.4.6 4.2.3 5 .1 5.8.1 7.1 0 8.3 0 8.7 0 12s0 3.7.1 4.9c.1 1.3.3 2.1.6 2.9.3.8.7 1.5 1.4 2.2.7.7 1.4 1.1 2.2 1.4.8.3 1.6.5 2.9.6 1.2.1 1.6.1 4.9.1s3.7 0 4.9-.1c1.3-.1 2.1-.3 2.9-.6.8-.3 1.5-.7 2.2-1.4.7-.7 1.1-1.4 1.4-2.2.3-.8.5-1.6.6-2.9.1-1.2.1-1.6.1-4.9s0-3.7-.1-4.9c-.1-1.3-.3-2.1-.6-2.9-.3-.8-.7-1.5-1.4-2.2C21.3 1.3 20.6.9 19.8.6 19 .3 18.2.1 16.9.1 15.7 0 15.3 0 12 0zm0 5.8a6.2 6.2 0 1 0 0 12.4 6.2 6.2 0 0 0 0-12.4zm0 10.2a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.4-11.8a1.4 1.4 0 1 0 0 2.9 1.4 1.4 0 0 0 0-2.9z"/>
                        </svg>
                        <div class="about-social-text">
                            <span class="about-social-label">Instagram</span>
                            <span class="about-social-hint">من خلف الكواليس</span>
                        </div>
                        <span class="about-social-chev" aria-hidden="true">↗</span>
                    </a>
                @endif
            </div>
        </section>
    @endif

    {{-- ============ CTA ============ --}}
    <nav class="about-cta about-reveal" aria-label="تنقل">
        <a href="{{ route('shows.index') }}" class="about-cta-primary">
            <span>اكتشف عروضنا</span>
            <span aria-hidden="true" class="about-cta-arrow">←</span>
        </a>

        <a href="{{ url('/') }}" class="about-cta-ghost">
            الرئيسية
        </a>
    </nav>

</section>

<style>
/* =========================================================
   ABOUT — premium, cinematic, mobile-first.
   All rules scoped to .about-shell so nothing else on the
   site is affected.
   ========================================================= */
.about-shell {
    --about-gold:        #fbbf24;
    --about-gold-soft:   rgba(251, 191, 36, 0.18);
    --about-gold-strong: rgba(251, 191, 36, 0.55);
    --about-ink:         #f1f5fb;
    --about-ink-2:       #c2cad8;
    --about-ink-3:       #8590a6;
    --about-line:        rgba(255, 255, 255, 0.08);
    --about-line-strong: rgba(255, 255, 255, 0.16);
    --about-ease:        cubic-bezier(.2, .7, .2, 1);

    position: relative;
    max-width: 56rem;
    margin: 0 auto;
    padding: 24px 16px 56px;
    padding-bottom: max(56px, env(safe-area-inset-bottom));
    isolation: isolate;
    color: var(--about-ink);
    font-family: "IBM Plex Sans Arabic", "Space Grotesk", system-ui, sans-serif;
}

/* ---------- Backdrop (decorative only) ---------- */
.about-backdrop {
    position: absolute;
    inset: 0;
    z-index: -1;
    overflow: hidden;
    pointer-events: none;
    border-radius: 0;
}
.about-orb {
    position: absolute;
    width: 60vmin;
    height: 60vmin;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.55;
    will-change: transform;
}
.about-orb-a {
    top: -10vmin;
    right: -18vmin;
    background: radial-gradient(circle at 30% 30%, rgba(251,191,36,0.55), rgba(251,191,36,0) 70%);
    animation: aboutFloatA 14s var(--about-ease) infinite alternate;
}
.about-orb-b {
    bottom: -20vmin;
    left: -22vmin;
    background: radial-gradient(circle at 60% 40%, rgba(192,132,252,0.40), rgba(34,211,238,0.18) 60%, transparent 70%);
    animation: aboutFloatB 18s var(--about-ease) infinite alternate;
}
.about-grain {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
        radial-gradient(rgba(255,255,255,0.025) 1px, transparent 1px);
    background-size: 3px 3px, 7px 7px;
    background-position: 0 0, 1px 2px;
    opacity: 0.6;
    mix-blend-mode: overlay;
}
@keyframes aboutFloatA {
    from { transform: translate3d(0,0,0)    scale(1); }
    to   { transform: translate3d(-30px,20px,0) scale(1.06); }
}
@keyframes aboutFloatB {
    from { transform: translate3d(0,0,0)   scale(1); }
    to   { transform: translate3d(24px,-26px,0) scale(1.08); }
}

/* ---------- HERO ---------- */
.about-hero {
    text-align: center;
    padding: 28px 8px 16px;
    display: grid;
    justify-items: center;
    gap: 14px;
}

.about-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--about-line);
    color: var(--about-ink-2);
    font-size: 11px;
    letter-spacing: 0.06em;
    backdrop-filter: blur(8px) saturate(140%);
    -webkit-backdrop-filter: blur(8px) saturate(140%);
}
.about-eyebrow-dot {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: var(--about-gold);
    box-shadow: 0 0 12px var(--about-gold-strong);
    animation: aboutPulse 2.4s var(--about-ease) infinite;
}
@keyframes aboutPulse {
    0%, 100% { transform: scale(1);    opacity: 1;   }
    50%      { transform: scale(1.4);  opacity: 0.6; }
}

.about-title {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    font-family: "Space Grotesk", "IBM Plex Sans Arabic", system-ui, sans-serif;
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.02em;
    font-size: clamp(2.25rem, 8vw, 4rem);
    margin: 0;
}
.about-title-line {
    display: block;
    color: var(--about-ink);
}
.about-title-accent {
    background: linear-gradient(135deg, #fde68a 0%, #fbbf24 45%, #f59e0b 100%);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
    filter: drop-shadow(0 8px 24px rgba(251,191,36,0.22));
}

.about-divider {
    width: 64px;
    height: 3px;
    border-radius: 999px;
    background: linear-gradient(90deg, transparent, var(--about-gold), transparent);
    box-shadow: 0 0 14px var(--about-gold-strong);
}

.about-founded {
    display: inline-flex;
    align-items: baseline;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 999px;
    background: rgba(251,191,36,0.06);
    border: 1px solid var(--about-gold-soft);
    color: var(--about-ink-2);
    font-size: 13px;
}
.about-founded-label { color: var(--about-ink-3); }
.about-founded-year {
    color: var(--about-gold);
    font-weight: 700;
    font-feature-settings: "tnum";
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.02em;
}

/* ---------- CREED (description) ---------- */
.about-creed {
    position: relative;
    margin: 28px auto 0;
    max-width: 44rem;
    padding: 28px 22px;
    border-radius: 28px;
    background: linear-gradient(180deg, rgba(20,24,38,0.66), rgba(8,10,20,0.78));
    border: 1px solid var(--about-line);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.04),
        0 30px 60px -30px rgba(0,0,0,0.65);
    backdrop-filter: blur(18px) saturate(140%);
    -webkit-backdrop-filter: blur(18px) saturate(140%);
    overflow: hidden;
}
.about-creed::before {
    content: "";
    position: absolute;
    inset: -1px;
    border-radius: inherit;
    padding: 1px;
    background: linear-gradient(140deg, rgba(251,191,36,0.35), rgba(255,255,255,0.05) 35%, rgba(192,132,252,0.25));
    -webkit-mask:
        linear-gradient(#000 0 0) content-box,
        linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
            mask-composite: exclude;
    pointer-events: none;
    opacity: 0.7;
}

.about-quote-mark {
    position: absolute;
    font-family: "Georgia", "IBM Plex Serif", serif;
    font-size: 96px;
    line-height: 1;
    color: var(--about-gold);
    opacity: 0.16;
    pointer-events: none;
    user-select: none;
}
.about-quote-mark-open  { top: 6px;    right: 18px; }
.about-quote-mark-close { bottom: -18px; left: 18px; }

.about-creed-body {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 14px;
    text-align: start;
}
.about-creed-line {
    margin: 0;
    font-size: 16px;
    line-height: 1.85;
    color: var(--about-ink);
    letter-spacing: 0.005em;
    animation: aboutLineIn .55s var(--about-ease) both;
    animation-delay: calc(var(--i, 0) * 80ms + 120ms);
}
.about-creed-line:first-child {
    font-size: 19px;
    font-weight: 600;
    color: #fff7e3;
    line-height: 1.6;
}

@media (min-width: 640px) {
    .about-creed { padding: 36px 36px; }
    .about-creed-line { font-size: 17px; }
    .about-creed-line:first-child { font-size: 22px; }
}
@media (min-width: 1024px) {
    .about-creed { padding: 48px 56px; }
    .about-creed-line:first-child { font-size: 24px; }
}

@keyframes aboutLineIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0);   }
}

/* ---------- Empty state ---------- */
.about-empty {
    margin: 28px auto 0;
    max-width: 44rem;
    padding: 28px;
    text-align: center;
    border-radius: 24px;
    border: 1px dashed var(--about-line-strong);
    color: var(--about-ink-3);
    background: rgba(255,255,255,0.02);
    font-size: 14px;
}
.about-empty span { font-size: 32px; display: block; margin-bottom: 8px; }

/* ---------- CONNECT (social) ---------- */
.about-connect {
    margin: 36px auto 0;
    max-width: 44rem;
}
.about-connect-title {
    text-align: center;
    font-size: 12px;
    letter-spacing: 0.24em;
    color: var(--about-ink-3);
    text-transform: uppercase;
    margin: 0 0 14px;
}

.about-connect-grid {
    display: grid;
    gap: 10px;
}
@media (min-width: 640px) {
    .about-connect-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

.about-social {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 18px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--about-line);
    color: var(--about-ink);
    text-decoration: none;
    min-height: 64px;
    transition: transform .25s var(--about-ease),
                background .25s var(--about-ease),
                border-color .25s var(--about-ease),
                box-shadow .25s var(--about-ease);
    -webkit-tap-highlight-color: transparent;
}
.about-social:hover,
.about-social:focus-visible {
    transform: translateY(-2px);
    background: rgba(255,255,255,0.06);
    border-color: var(--about-line-strong);
    box-shadow: 0 18px 36px -22px rgba(0,0,0,0.6);
    outline: none;
}
.about-social:active { transform: translateY(0); }

.about-social-icon {
    width: 28px;
    height: 28px;
    flex-shrink: 0;
}
.about-social-yt .about-social-icon { color: #f87171; }
.about-social-fb .about-social-icon { color: #60a5fa; }
.about-social-ig .about-social-icon { color: #f472b6; }

.about-social-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
    line-height: 1.2;
    flex: 1;
    min-width: 0;
}
.about-social-label {
    font-size: 15px;
    font-weight: 600;
}
.about-social-hint {
    font-size: 11.5px;
    color: var(--about-ink-3);
}
.about-social-chev {
    font-size: 14px;
    color: var(--about-ink-3);
    transition: transform .25s var(--about-ease), color .25s var(--about-ease);
}
.about-social:hover .about-social-chev,
.about-social:focus-visible .about-social-chev {
    transform: translate(-2px, -2px);
    color: var(--about-gold);
}

/* ---------- CTA ---------- */
.about-cta {
    margin: 40px auto 0;
    max-width: 44rem;
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: stretch;
}
@media (min-width: 480px) {
    .about-cta {
        flex-direction: row;
        justify-content: center;
        align-items: center;
    }
}

.about-cta-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 22px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 700;
    color: #1b1208;
    background: linear-gradient(180deg, #fde68a, #f59e0b);
    border: 1px solid rgba(255,255,255,0.4);
    box-shadow:
        0 18px 36px -14px rgba(245, 158, 11, 0.55),
        inset 0 1px 0 rgba(255,255,255,0.55);
    transition: transform .2s var(--about-ease), box-shadow .25s var(--about-ease);
    text-decoration: none;
    min-height: 48px;
    -webkit-tap-highlight-color: transparent;
}
.about-cta-primary:hover,
.about-cta-primary:focus-visible {
    transform: translateY(-2px);
    box-shadow:
        0 26px 48px -16px rgba(245, 158, 11, 0.7),
        0 0 22px rgba(251, 191, 36, 0.45),
        inset 0 1px 0 rgba(255,255,255,0.55);
    outline: none;
}
.about-cta-primary:active { transform: translateY(0); }
.about-cta-arrow {
    display: inline-block;
    transition: transform .25s var(--about-ease);
}
.about-cta-primary:hover .about-cta-arrow,
.about-cta-primary:focus-visible .about-cta-arrow {
    transform: translateX(-3px);
}

.about-cta-ghost {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 22px;
    border-radius: 999px;
    font-size: 13px;
    color: var(--about-ink-2);
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--about-line);
    text-decoration: none;
    min-height: 44px;
    transition: background .2s var(--about-ease), color .2s var(--about-ease), border-color .2s var(--about-ease);
    -webkit-tap-highlight-color: transparent;
}
.about-cta-ghost:hover,
.about-cta-ghost:focus-visible {
    background: rgba(255,255,255,0.08);
    color: var(--about-ink);
    border-color: var(--about-line-strong);
    outline: none;
}

/* ---------- Scroll reveal ---------- */
.about-reveal {
    opacity: 0;
    transform: translateY(14px);
    transition: opacity .65s var(--about-ease), transform .65s var(--about-ease);
}
.about-reveal.is-in {
    opacity: 1;
    transform: translateY(0);
}

/* ---------- Reduced motion ---------- */
@media (prefers-reduced-motion: reduce) {
    .about-orb,
    .about-eyebrow-dot,
    .about-creed-line { animation: none !important; }
    .about-reveal {
        opacity: 1;
        transform: none;
        transition: none;
    }
    .about-cta-primary:hover { transform: none; }
    .about-social:hover      { transform: none; }
}

/* ---------- iOS Safari smoothing ---------- */
@supports (-webkit-touch-callout: none) {
    .about-creed,
    .about-eyebrow {
        -webkit-transform: translateZ(0);
    }
}
</style>

<script>
// Scroll-reveal — kept tiny on purpose. IntersectionObserver is the
// only API we need; the page degrades gracefully on ancient browsers
// (reveals stay visible because is-in is applied unconditionally).
(function () {
    var nodes = document.querySelectorAll('.about-reveal');
    if (!('IntersectionObserver' in window)) {
        nodes.forEach(function (n) { n.classList.add('is-in'); });
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) {
                e.target.classList.add('is-in');
                io.unobserve(e.target);
            }
        });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.12 });

    nodes.forEach(function (n) { io.observe(n); });
})();
</script>
@endsection
