@extends('layouts.app')

@section('title', 'عروض فريق الصرخة المسرحي')

@section('content')

{{-- ============================================================
     HOMEPAGE — cinematic refresh
     ------------------------------------------------------------
     Preserves the existing brand identity (scream-hero / scream-
     border / scream-pulse / scream-card / scream-title classes,
     the Arabic copy verbatim, the LIVE · THEATER · SCREAM eyebrow,
     and the inverted-logo treatment) and layers on top:

       * a clearer primary CTA pair anchored to the show grid
       * a stronger fluid type scale on mobile via clamp()
       * a theater-curtain hairline section divider that echoes
         the new minimal footer
       * stagger-reveal entrance for show cards via a tiny
         IntersectionObserver (gracefully degrades / honours
         prefers-reduced-motion)
       * an arch-style mask-composite hover frame on each show
         card so the homepage and archive feel like one product
       * a more cinematic empty state ("المسرح في استراحة قصيرة")
============================================================ --}}

<style>
    /* Hero polish — bigger fluid headline, a CTA row, a gentle
       float on the logo. The base structure still uses the
       existing scream-* classes so the identity is preserved. */
    .hp-hero-title {
        font-size: clamp(1.6rem, 5.5vw, 2.6rem);
        line-height: 1.32;
    }
    .hp-cta-row {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        margin-top: .85rem;
    }
    .hp-cta-primary {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .7rem 1.15rem;
        min-height: 44px;
        border-radius: 9999px;
        font-weight: 800;
        font-size: .9rem;
        letter-spacing: .02em;
        color: #1b1208;
        background: linear-gradient(180deg, #fde68a, #f59e0b);
        box-shadow:
            0 12px 28px -10px rgba(245,158,11,.55),
            inset 0 1px 0 rgba(255,255,255,.55);
        transition: transform .28s cubic-bezier(.2,.7,.2,1),
                    box-shadow .35s ease;
    }
    .hp-cta-primary:hover {
        transform: translateY(-2px);
        box-shadow:
            0 18px 36px -10px rgba(245,158,11,.75),
            0 0 22px rgba(251,191,36,.4),
            inset 0 1px 0 rgba(255,255,255,.55);
    }
    .hp-cta-ghost {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .7rem 1.15rem;
        min-height: 44px;
        border-radius: 9999px;
        font-weight: 600;
        font-size: .9rem;
        color: #f1f5fb;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.18);
        transition: background .25s, border-color .25s, transform .25s;
    }
    .hp-cta-ghost:hover {
        background: rgba(255,255,255,.08);
        border-color: rgba(255,255,255,.3);
        transform: translateY(-2px);
    }

    /* Gentle float on the hero logo — slow, low amplitude. */
    @keyframes hpLogoFloat {
        0%, 100% { transform: translateY(0); }
        50%      { transform: translateY(-6px); }
    }
    .hp-logo-wrap { animation: hpLogoFloat 5s ease-in-out infinite; }
    @media (prefers-reduced-motion: reduce) {
        .hp-logo-wrap { animation: none; }
    }

    /* Mobile scroll-cue under the hero. Hidden on md+ where the
       show grid is already in viewport. */
    @keyframes hpBounce {
        0%, 100% { transform: translateY(0);  opacity: .55; }
        50%      { transform: translateY(4px); opacity: 1; }
    }
    .hp-scroll-cue {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: .25rem;
        margin: 1.25rem auto 0;
        font-size: 10px;
        letter-spacing: .25em;
        text-transform: uppercase;
        color: rgba(229,231,235,.45);
    }
    .hp-scroll-cue::after {
        content: "↓";
        font-size: 18px;
        color: rgba(250,204,21,.75);
        animation: hpBounce 2s cubic-bezier(.5,0,.5,1) infinite;
    }
    @media (min-width: 768px) { .hp-scroll-cue { display: none; } }
    @media (prefers-reduced-motion: reduce) {
        .hp-scroll-cue::after { animation: none; }
    }

    /* Section divider — theater-curtain hairline. Same gradient
       language as the new minimal footer. */
    .hp-divider {
        position: relative;
        height: 1px;
        width: 100%;
        margin: 2.25rem 0 2rem;
        overflow: visible;
    }
    .hp-divider::before,
    .hp-divider::after {
        content: "";
        position: absolute;
        inset: 0;
    }
    .hp-divider::before {
        background: linear-gradient(to left, transparent, rgba(250,204,21,.3), transparent);
    }
    .hp-divider::after {
        background: linear-gradient(to left, transparent, rgba(248,113,113,.2), transparent);
        filter: blur(1px);
    }

    /* Section header — eyebrow + gradient-text title that matches
       the archive hero. */
    .hp-section-eyebrow {
        font-size: 11px;
        letter-spacing: .22em;
        text-transform: uppercase;
        color: rgba(252,211,77,.7);
    }
    .hp-section-title {
        font-size: clamp(1.4rem, 4vw, 2rem);
        font-weight: 800;
        letter-spacing: -.01em;
        background: linear-gradient(135deg, #fde68a 0%, #fbbf24 50%, #f87171 100%);
        -webkit-background-clip: text;
                background-clip: text;
        color: transparent;
        text-shadow: 0 0 22px rgba(250,204,21,.18);
    }

    /* Show card — theater-curtain frame echoing the archive
       cards. mask-composited gradient on a wrapper so there's no
       live-animated border. */
    .hp-show {
        position: relative;
        border-radius: 1.25rem;
        overflow: hidden;
        background: linear-gradient(180deg, rgba(15,23,42,.7), rgba(2,6,23,.78));
        transition: transform .55s cubic-bezier(.2,.7,.2,1),
                    box-shadow .55s ease;
        will-change: transform;
    }
    .hp-show::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        padding: 1px;
        background: linear-gradient(135deg,
            rgba(250,204,21,.18),
            rgba(248,113,113,.10) 50%,
            rgba(255,255,255,.05));
        -webkit-mask:
            linear-gradient(#000 0 0) content-box,
            linear-gradient(#000 0 0);
        -webkit-mask-composite: xor;
                mask-composite: exclude;
        pointer-events: none;
        transition: opacity .4s ease;
        opacity: .8;
    }
    .hp-show:hover,
    .hp-show:focus-within {
        transform: translateY(-4px);
        box-shadow:
            0 22px 48px -22px rgba(250,204,21,.45),
            0 14px 28px -16px rgba(248,113,113,.3);
    }
    .hp-show:hover::before,
    .hp-show:focus-within::before {
        background: linear-gradient(135deg,
            rgba(250,204,21,.55),
            rgba(248,113,113,.4) 50%,
            rgba(255,255,255,.12));
        opacity: 1;
    }

    /* Stagger reveal — IO toggles `.is-in`. Falls back to instant
       visibility if IO is missing or motion is reduced. */
    .hp-reveal {
        opacity: 0;
        transform: translateY(24px);
        transition: opacity .85s ease,
                    transform .85s cubic-bezier(.2,.7,.2,1);
    }
    .hp-reveal.is-in {
        opacity: 1;
        transform: translateY(0);
    }
    @media (prefers-reduced-motion: reduce) {
        .hp-reveal { opacity: 1; transform: none; transition: none; }
        .hp-show, .hp-show:hover { transform: none; transition: none; }
    }

    /* Empty state — cinematic "stage is on a break" treatment.
       Echoes the archive empty state for visual consistency. */
    .hp-empty {
        position: relative;
        text-align: center;
        padding: 3.25rem 1.5rem;
        border-radius: 1.5rem;
        overflow: hidden;
        background:
            radial-gradient(ellipse at top,    rgba(250,204,21,.08), transparent 60%),
            radial-gradient(ellipse at bottom, rgba(248,113,113,.06), transparent 60%),
            rgba(2,6,23,.55);
        border: 1px solid rgba(255,255,255,.08);
    }
    .hp-empty::after {
        content: "";
        position: absolute;
        inset-inline: 0;
        bottom: 0;
        height: 1px;
        background: linear-gradient(to left, transparent, rgba(250,204,21,.35), transparent);
    }
</style>


{{-- ===================== HERO ===================== --}}
<section class="scream-hero mb-4 md:mb-6">
    <div class="scream-border scream-pulse">
        <div class="scream-card px-5 py-6 md:px-8 md:py-8
                    flex flex-col md:flex-row gap-6 items-center">

            <div class="flex-1 space-y-3 text-center md:text-right">
                <p class="text-amber-300 text-[11px] font-semibold tracking-[0.32em] uppercase">
                    LIVE · THEATER · SCREAM
                </p>

                <h1 class="scream-title hp-hero-title font-extrabold">
                    كثيرًا ما فَسدت عقولُنا مما حملته لها مدخلاتُنا…
                    ونحن هنا لنُغيِّر ذلك، فقط بالصُّراخ.
                </h1>

                <p class="text-sm md:text-base text-gray-200 leading-relaxed">
                    نَصرخ هنا وهناك، نَدعو الجميع للمجيء إلينا ومنحنا من وقتهم القليل؛
                    فنحن لا نريد سوى حواسِّكم. ثم نصرخ، نبحث في مدخلاتِكم لنُخرِج ما هو فاسد
                    ونزرع بدلاً منه ثمرًا صالحًا، لا نريد سوى عقولِكم.
                </p>

                <p class="text-xs md:text-sm text-gray-300 leading-relaxed">
                    والآن نصرخ بالتعاليم الصحيحة لنغيِّر ما فَسَد.
                    وكل ما نحتاجه هو أن تأتوا إلى <span class="text-amber-300 font-semibold">مصدر الصراخ</span>؛
                    فدائمًا يكون على المسرح.
                    <span class="text-rose-300">❤ نجول، نصرخ… فيزداد العقل وعيًا ❤</span>
                </p>

                <div class="hp-cta-row justify-center md:justify-start">
                    @if(!$shows->isEmpty())
                        <a href="#shows"
                           class="hp-cta-primary"
                           aria-label="انتقل إلى قسم العروض المتاحة">
                            <span aria-hidden="true">🎟️</span>
                            <span>احجز مقعدك</span>
                        </a>
                    @endif
                    <a href="{{ route('archive') }}" class="hp-cta-ghost">
                        <span aria-hidden="true">🎭</span>
                        <span>الأرشيف</span>
                    </a>
                </div>
            </div>

            {{-- Logo --}}
            <div class="flex-1 flex justify-center md:justify-end">
                <div class="hp-logo-wrap relative w-36 h-36 md:w-52 md:h-52
                            rounded-full border border-amber-400/60 overflow-hidden
                            shadow-[0_0_50px_rgba(250,204,21,0.65)]
                            flex items-center justify-center">
                    <img src="{{ asset('images/sarkha-logo.png') }}"
                         alt="فريق الصرخة المسرحي"
                         loading="eager"
                         class="w-24 h-24 md:w-36 md:h-36 object-contain
                                filter invert brightness-125">
                </div>
            </div>

        </div>
    </div>

    @if(!$shows->isEmpty())
        <div class="text-center">
            <a href="#shows" class="hp-scroll-cue" aria-label="تصفّح العروض">
                <span>تصفّح</span>
            </a>
        </div>
    @endif
</section>


{{-- Theater-curtain hairline divider --}}
<div class="hp-divider" aria-hidden="true"></div>


{{-- ===================== SHOWS ===================== --}}
<section id="shows" class="space-y-5 scroll-mt-24">

    <header class="space-y-1 mb-1">
        <p class="hp-section-eyebrow text-center md:text-right">
            shows · المعروضة حاليًا
        </p>
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h2 class="hp-section-title">العروض المتاحة</h2>
            @if(!$shows->isEmpty())
                <span class="text-[11px] px-3 py-1 rounded-full bg-white/5 border border-white/10 text-gray-300">
                    <span aria-hidden="true">🎭</span>
                    {{ $shows->count() }} {{ $shows->count() === 1 ? 'عرض' : 'عروض' }} متاح للحجز
                </span>
            @endif
        </div>
    </header>

    @if($shows->isEmpty())

        <div class="hp-empty">
            <div class="text-5xl mb-3" aria-hidden="true">🎭</div>
            <h3 class="text-lg font-semibold text-gray-200">المسرح في استراحة قصيرة</h3>
            <p class="mt-2 text-sm text-gray-400 max-w-sm mx-auto leading-relaxed">
                لسه مفيش عروض متاحة حاليًا — انتظرنا قريبًا، الستارة سترتفع من جديد ❤️
            </p>
            <div class="mt-5">
                <a href="{{ route('archive') }}" class="hp-cta-ghost">
                    <span aria-hidden="true">🎭</span>
                    <span>تصفّح الأرشيف</span>
                </a>
            </div>
        </div>

    @else

        <div class="grid md:grid-cols-2 gap-5">
            @foreach($shows as $i => $show)
                <article class="hp-show hp-reveal group p-4 flex flex-col justify-between"
                         data-stagger="{{ $i * 90 }}">

                    {{-- "Featured" badge for the first show. --}}
                    @if($loop->first)
                        <div class="mb-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                         bg-amber-400/10 border border-amber-400/60
                                         text-[11px] font-semibold text-amber-200 uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-300 animate-pulse"
                                      aria-hidden="true"></span>
                                عرض مُميز
                            </span>
                        </div>
                    @endif

                    {{-- Poster --}}
                    @if($show->poster_path)
                        <div class="relative mb-4 rounded-xl overflow-hidden
                                    border border-white/10 bg-black/50">
                            <img src="{{ $show->poster_path }}"
                                 alt="{{ $show->title }}"
                                 loading="lazy"
                                 decoding="async"
                                 class="w-full h-auto object-contain
                                        transition-transform duration-700 ease-out
                                        group-hover:scale-[1.035]">

                            <div class="absolute bottom-2 left-2
                                        text-[11px] px-2 py-1 rounded-full
                                        bg-black/70 border border-white/20
                                        text-gray-200 backdrop-blur">
                                <span aria-hidden="true">🎫</span>
                                احجز مقعدك قبل النفاد
                            </div>
                        </div>
                    @endif

                    <div class="space-y-3">
                        <h3 class="text-lg md:text-xl font-bold text-white leading-snug">
                            {{ $show->title }}
                        </h3>

                        <p class="text-sm text-gray-300/90 leading-relaxed whitespace-pre-line">
                            {{ $show->description }}
                        </p>

                        <div class="space-y-2 text-xs text-gray-400">
                            <p class="font-semibold text-gray-300 flex items-center gap-1.5">
                                <span aria-hidden="true">🕒</span>
                                المواعيد القادمة:
                            </p>
                            <ul class="space-y-1">
                                @forelse($show->showTimes->take(2) as $time)
                                    <li class="flex items-center justify-between gap-2
                                               bg-white/5 hover:bg-white/10
                                               rounded-lg px-3 py-1.5
                                               border border-white/5 transition">
                                        <span class="font-medium text-gray-200">
                                            {{ $time->date->format('d/m/Y') }}
                                            <span class="text-gray-500 mx-1">•</span>
                                            {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                                        </span>
                                        <span class="text-amber-300 font-bold whitespace-nowrap">
                                            {{ $time->ticket_price }}
                                            <span class="text-[10px] text-amber-200/70">ج</span>
                                        </span>
                                    </li>
                                @empty
                                    <li class="text-[11px] text-gray-500 px-3">
                                        لا توجد مواعيد متاحة حاليًا لهذا العرض.
                                    </li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="pt-2 flex justify-between items-center">
                            <span class="text-xs text-gray-400 flex items-center gap-1.5">
                                <span class="inline-block w-2 h-2 rounded-full
                                             bg-emerald-400/80 animate-pulse"
                                      aria-hidden="true"></span>
                                {{ $show->showTimes->count() }} موعد متاح
                            </span>
                            <a href="{{ route('shows.show', $show) }}"
                               class="inline-flex items-center gap-1.5
                                      text-sm font-bold bg-amber-400 text-black
                                      px-4 py-2 rounded-full
                                      hover:bg-amber-300
                                      hover:shadow-[0_8px_24px_-8px_rgba(250,204,21,0.7)]
                                      transition">
                                تفاصيل &amp; حجز
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 20 20"
                                     class="w-3.5 h-3.5"
                                     fill="currentColor"
                                     aria-hidden="true">
                                    <path fill-rule="evenodd"
                                          d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                          clip-rule="evenodd" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

    @endif

</section>


@if(!$shows->isEmpty())
<script>
    (function () {
        // Stagger reveal for the show cards. Mirrors the archive
        // page's pattern: IO toggles `is-in`, with a graceful
        // fallback for reduced motion or missing IO support.
        var prefersReduced =
            window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        var cards = document.querySelectorAll('.hp-reveal');
        if (!cards.length) return;

        if (!('IntersectionObserver' in window) || prefersReduced) {
            cards.forEach(function (c) { c.classList.add('is-in'); });
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                var delay = parseInt(el.getAttribute('data-stagger') || '0', 10);
                setTimeout(function () { el.classList.add('is-in'); }, delay);
                io.unobserve(el);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

        cards.forEach(function (c) { io.observe(c); });
    })();
</script>
@endif

@endsection
