@extends('layouts.app')

@section('title', 'العروض السابقة')

@section('content')

{{-- ============================================================
     ARCHIVE / العروض السابقة
     ------------------------------------------------------------
     Mobile-first cinematic archive browser. Touch users get a
     horizontal-snap poster reel that feels like flipping through
     theater playbills. Desktop users get a staggered magazine
     grid where alternating rows offset vertically for a more
     editorial, less template-y feel.

     No libraries — only Tailwind + a tiny vanilla JS
     IntersectionObserver for stagger reveal that gracefully
     degrades to static if IO or `prefers-reduced-motion` is set.
============================================================ --}}

<style>
    /* ============================================================
       Theater-curtain frame — used as a hover/focus accent around
       posters. Implemented as a gradient `padding` on a wrapper so
       we don't pay for an animated border re-layout. */
    .arch-frame {
        position: relative;
        border-radius: 1.25rem;
        overflow: hidden;
        background: linear-gradient(140deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
        transition: transform .6s cubic-bezier(.2,.7,.2,1), box-shadow .6s ease;
        will-change: transform;
    }
    .arch-frame::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        padding: 1px;
        background: linear-gradient(135deg, rgba(250,204,21,0.12), rgba(248,113,113,0.08) 50%, rgba(255,255,255,0.04));
        -webkit-mask:
            linear-gradient(#000 0 0) content-box,
            linear-gradient(#000 0 0);
        -webkit-mask-composite: xor;
                mask-composite: exclude;
        pointer-events: none;
        transition: opacity .4s ease;
        opacity: .8;
    }
    .arch-frame:hover,
    .arch-frame:focus-within {
        transform: translateY(-4px);
        box-shadow: 0 22px 60px -28px rgba(250,204,21,0.45),
                    0 12px 30px -18px rgba(248,113,113,0.35);
    }
    .arch-frame:hover::before,
    .arch-frame:focus-within::before {
        background: linear-gradient(135deg, rgba(250,204,21,0.55), rgba(248,113,113,0.4) 50%, rgba(255,255,255,0.12));
        opacity: 1;
    }

    /* Poster image: stable aspect ratio + gentle Ken-Burns zoom on
       hover. `object-cover` makes posters of inconsistent ratios
       still feel curated. */
    .arch-poster {
        width: 100%;
        aspect-ratio: 2 / 3;
        object-fit: cover;
        transition: transform 1.2s cubic-bezier(.2,.7,.2,1), filter .5s ease;
        filter: saturate(.92) contrast(1.05);
    }
    .arch-frame:hover .arch-poster {
        transform: scale(1.06);
        filter: saturate(1) contrast(1.1);
    }

    /* Title overlay — sits on the poster with a long graduated
       black scrim so titles read in any image. Lifts up slightly
       on hover for a 'reveal' feel. */
    .arch-overlay {
        position: absolute;
        inset-inline: 0;
        bottom: 0;
        padding: 1rem 1.05rem 1.1rem;
        background: linear-gradient(
            to top,
            rgba(2,6,23,0.92) 0%,
            rgba(2,6,23,0.78) 35%,
            rgba(2,6,23,0.45) 65%,
            rgba(2,6,23,0)    100%
        );
        transform: translateY(0);
        transition: transform .55s cubic-bezier(.2,.7,.2,1);
    }
    .arch-frame:hover .arch-overlay {
        transform: translateY(-4px);
    }

    /* Year chip — small ticket-stub-style pill. */
    .arch-year {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        font-size: 10.5px;
        letter-spacing: .12em;
        font-weight: 600;
        color: rgba(250,204,21,0.95);
        background: rgba(250,204,21,0.08);
        border: 1px solid rgba(250,204,21,0.25);
        border-radius: 9999px;
        backdrop-filter: blur(4px);
    }
    .arch-year::before {
        content: "";
        width: 4px;
        height: 4px;
        border-radius: 9999px;
        background: rgba(250,204,21,0.95);
        box-shadow: 0 0 8px rgba(250,204,21,0.7);
    }

    /* The "discover" hint at the bottom of every card. Slides in
       from the right on hover/focus on devices that support hover.
       Touch devices get it shown by default so it isn't hidden
       behind an invisible interaction. */
    .arch-cta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: rgba(255,255,255,0.95);
        opacity: 1;
        transform: translateX(0);
        transition: transform .45s cubic-bezier(.2,.7,.2,1), opacity .3s ease;
    }
    @media (hover: hover) {
        .arch-cta { opacity: .6; transform: translateX(6px); }
        .arch-frame:hover .arch-cta,
        .arch-frame:focus-within .arch-cta { opacity: 1; transform: translateX(0); }
    }
    .arch-cta svg {
        transition: transform .4s ease;
    }
    .arch-frame:hover .arch-cta svg { transform: translateX(-3px); }

    /* ============================================================
       MOBILE poster reel — horizontal snap-scroller, one card
       visible with a small peek of the next. Touch users can swipe;
       the dot indicators below give them a sense of progress. */
    .arch-reel {
        display: flex;
        gap: 1rem;
        padding: 0.25rem 1.25rem 0.5rem;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        scroll-padding-inline: 1.25rem;
    }
    .arch-reel::-webkit-scrollbar { display: none; }
    .arch-reel > * {
        flex: 0 0 78%;
        scroll-snap-align: center;
        scroll-snap-stop: always;
    }
    @media (min-width: 480px) {
        .arch-reel > * { flex-basis: 62%; }
    }

    .arch-dots {
        display: flex;
        gap: 6px;
        justify-content: center;
        margin-top: 14px;
    }
    .arch-dot {
        width: 5px;
        height: 5px;
        border-radius: 9999px;
        background: rgba(255,255,255,0.18);
        transition: width .3s ease, background .3s ease;
    }
    .arch-dot.is-active {
        width: 18px;
        background: rgba(250,204,21,0.9);
    }

    /* ============================================================
       DESKTOP magazine grid — alternating offset rows for a more
       editorial feel. Every other card pushes down a bit so the
       grid breathes instead of looking like a rigid template. */
    @media (min-width: 768px) {
        .arch-mag {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 2rem 1.75rem;
            padding: 0 .25rem;
        }
        .arch-mag > *:nth-child(3n+2) { transform: translateY(2.25rem); }
    }
    @media (min-width: 1024px) {
        .arch-mag {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 2.25rem 1.75rem;
        }
        .arch-mag > *:nth-child(3n+2) { transform: none; }
        .arch-mag > *:nth-child(4n+2) { transform: translateY(2.5rem); }
        .arch-mag > *:nth-child(4n+4) { transform: translateY(-1.25rem); }
    }

    /* ============================================================
       Stagger reveal — JS toggles `.is-in` on each card as it
       scrolls into view. Falls back to "always visible" if IO is
       missing or the user prefers reduced motion. */
    .arch-reveal {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity .9s ease, transform .9s cubic-bezier(.2,.7,.2,1);
    }
    .arch-reveal.is-in {
        opacity: 1;
        transform: translateY(0);
    }
    @media (prefers-reduced-motion: reduce) {
        .arch-reveal { opacity: 1; transform: none; transition: none; }
        .arch-frame, .arch-poster, .arch-overlay, .arch-cta { transition: none; }
        .arch-frame:hover .arch-poster { transform: none; }
        .arch-frame:hover { transform: none; }
    }

    /* ============================================================
       Hero — dramatic title band with a soft amber glow that
       echoes the booking page header. Cheap and CSS-only. */
    .arch-hero {
        position: relative;
        text-align: center;
        padding: 2.5rem 1rem 0.5rem;
    }
    .arch-hero::before {
        content: "";
        position: absolute;
        inset: -20% auto auto 50%;
        transform: translateX(50%);
        width: min(90vw, 38rem);
        height: 14rem;
        background:
            radial-gradient(closest-side, rgba(250,204,21,0.18), transparent 70%),
            radial-gradient(closest-side, rgba(248,113,113,0.10), transparent 75%);
        filter: blur(8px);
        pointer-events: none;
        z-index: -1;
    }
    .arch-hero h1 {
        font-size: clamp(1.85rem, 7vw, 3.25rem);
        font-weight: 800;
        background: linear-gradient(135deg, #fde68a 0%, #fbbf24 35%, #f87171 90%);
        -webkit-background-clip: text;
                background-clip: text;
        color: transparent;
        letter-spacing: -0.01em;
        text-shadow: 0 0 24px rgba(250,204,21,0.25);
    }

    /* ============================================================
       Empty state — cinematic "curtain down" feel rather than a
       plain empty card. Uses the same gradient hairline as the
       footer to tie the visual language together. */
    .arch-empty {
        position: relative;
        text-align: center;
        padding: 4rem 1.5rem;
        border-radius: 1.5rem;
        overflow: hidden;
        background:
            radial-gradient(ellipse at top, rgba(250,204,21,0.08), transparent 60%),
            radial-gradient(ellipse at bottom, rgba(248,113,113,0.06), transparent 60%),
            rgba(2,6,23,0.5);
        border: 1px solid rgba(255,255,255,0.08);
    }
    .arch-empty::after {
        content: "";
        position: absolute;
        inset-inline: 0;
        bottom: 0;
        height: 1px;
        background: linear-gradient(to left, transparent, rgba(250,204,21,0.35), transparent);
    }
</style>

<section class="space-y-8">

    {{-- ============== Hero / title band ============== --}}
    <header class="arch-hero">
        <p class="text-[11px] sm:text-xs tracking-[0.22em] uppercase text-amber-200/70">
            archive · أرشيف
        </p>
        <h1 class="mt-2">🎭 العروض السابقة</h1>
        <p class="mt-3 text-sm text-gray-300/85 max-w-md mx-auto leading-relaxed">
            استعرض رحلتنا على المسرح — كل عرض، كل صرخة، كل لحظة.
            @if(!$archives->isEmpty())
                <span class="block mt-1 text-xs text-gray-500">
                    {{ $archives->count() }} عرض في الأرشيف
                </span>
            @endif
        </p>
    </header>

    @if($archives->isEmpty())

        {{-- ============== Empty state ============== --}}
        <div class="arch-empty">
            <div class="text-5xl mb-3" aria-hidden="true">🎭</div>
            <h2 class="text-lg font-semibold text-gray-200">الستارة لم تُرفع بعد</h2>
            <p class="mt-2 text-sm text-gray-400 max-w-sm mx-auto">
                لا توجد عروض سابقة مضافة حتى الآن. عُد قريبًا — الأرشيف يكبر مع كل صرخة.
            </p>
        </div>

    @else

        {{-- ============== MOBILE: poster reel ============== --}}
        <div class="md:hidden">
            <div id="archReel" class="arch-reel" role="region" aria-label="عروض سابقة - قابل للسحب">
                @foreach($archives as $i => $archive)
                    @include('site._archive_card', ['archive' => $archive, 'index' => $i])
                @endforeach
            </div>

            <div class="arch-dots" id="archDots" aria-hidden="true">
                @foreach($archives as $i => $_)
                    <span class="arch-dot {{ $i === 0 ? 'is-active' : '' }}"></span>
                @endforeach
            </div>

            <p class="mt-3 text-center text-[11px] text-gray-500">
                ← اسحب لاستعراض الأرشيف →
            </p>
        </div>

        {{-- ============== DESKTOP: magazine grid ============== --}}
        <div class="hidden md:block">
            <div class="arch-mag">
                @foreach($archives as $i => $archive)
                    @include('site._archive_card', ['archive' => $archive, 'index' => $i])
                @endforeach
            </div>
        </div>

    @endif

</section>

@if(!$archives->isEmpty())
<script>
    (function () {
        var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // ====================================================
        // Stagger reveal — IntersectionObserver based, with a
        // graceful fallback. Each card already starts hidden
        // (opacity:0 in CSS); we add `.is-in` to fade it up.
        // Fallback: if IO isn't available or motion is reduced,
        // every card is shown instantly.
        // ====================================================
        var cards = document.querySelectorAll('.arch-reveal');
        if (!('IntersectionObserver' in window) || prefersReduced) {
            cards.forEach(function (c) { c.classList.add('is-in'); });
        } else {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var el = entry.target;
                    // Use the element's data-stagger so cards in the
                    // same row appear staggered without us having to
                    // know their index here.
                    var delay = parseInt(el.getAttribute('data-stagger') || '0', 10);
                    setTimeout(function () { el.classList.add('is-in'); }, delay);
                    io.unobserve(el);
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

            cards.forEach(function (c) { io.observe(c); });
        }

        // ====================================================
        // Mobile reel — keep the dot indicator in sync with the
        // currently snapped card. We use IO again rather than
        // scroll position math so it stays accurate across
        // momentum scrolling and orientation changes.
        // ====================================================
        var reel = document.getElementById('archReel');
        var dotsWrap = document.getElementById('archDots');
        if (reel && dotsWrap && 'IntersectionObserver' in window) {
            var items = reel.children;
            var dots = dotsWrap.children;
            var snapIo = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var idx = Array.prototype.indexOf.call(items, entry.target);
                    if (idx < 0) return;
                    for (var i = 0; i < dots.length; i++) {
                        dots[i].classList.toggle('is-active', i === idx);
                    }
                });
            }, { root: reel, threshold: 0.65 });
            for (var i = 0; i < items.length; i++) snapIo.observe(items[i]);
        }
    })();
</script>
@endif

@endsection
