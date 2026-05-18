{{-- resources/views/shows/show.blade.php --}}
@extends('layouts.app')

@section('title', $show->title)

@section('content')

{{-- ============================================================
     SHOW DETAIL — cinematic refresh
     ------------------------------------------------------------
     Premium event landing for a single upcoming show. Same
     visual language as the archive-detail / homepage / archive
     list / footer (theater-curtain hairlines, eyebrow + gradient
     title, mask-composite card frame, stagger-reveal sections).

     Brand identity preserved verbatim:
       - الرسالة: نجول… نصرخ… فيزداد العقل وعيًا
       - The Arabic copy: المواعيد المتاحة, احجز الآن, Sold Out,
         سعر التذكرة, etc.
       - The amber / red theatre palette
       - The 🎭/🎟️ glyph language
       - object-cover poster treatment (was already 9:13-ish)
============================================================ --}}

<style>
[data-show-detail] {
    --sd-radius:        1.5rem;
    --sd-radius-lg:     2rem;
    --sd-border:        rgba(255,255,255,0.10);
    --sd-border-strong: rgba(255,255,255,0.18);
    --sd-text:          #f1f5fb;
    --sd-text-2:        rgba(229,231,235,0.85);
    --sd-text-3:        rgba(229,231,235,0.55);
    --sd-ease:          cubic-bezier(.2,.7,.2,1);
}

/* ============================================================
   MISSION RIBBON — replaces the old plain amber-text
   single-line message with a tiny pill-style ribbon. */
.sd-mission {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    margin-bottom: 1.1rem;
    padding: .3rem .85rem;
    border-radius: 9999px;
    background: rgba(250,204,21,.06);
    border: 1px solid rgba(250,204,21,.32);
    font-size: 11px;
    letter-spacing: .14em;
    color: rgba(252,211,77,.95);
}

/* ============================================================
   HERO — poster + blurred backdrop layer, the same recipe as
   the archive-detail page so the two surfaces feel like one
   product. */
.sd-hero {
    position: relative;
    overflow: hidden;
    border-radius: var(--sd-radius-lg);
    border: 1px solid var(--sd-border);
    background: #020617;
    isolation: isolate;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.04),
        0 30px 60px -30px rgba(0,0,0,0.9);
}
.sd-hero-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
    background-size: cover;
    background-position: center;
    filter: blur(38px) saturate(160%);
    transform: scale(1.25);
    opacity: .55;
    animation: sdBgPan 22s ease-in-out infinite alternate;
}
.sd-hero-bg::after {
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at center, transparent 0%, rgba(2,6,23,.55) 60%, rgba(2,6,23,.95) 100%);
}
@keyframes sdBgPan {
    0%   { transform: scale(1.25) translate3d(0, 0, 0); }
    100% { transform: scale(1.32) translate3d(2%, -2%, 0); }
}

.sd-hero-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
    padding: clamp(1rem, 4vw, 2rem);
}
@media (min-width: 768px) {
    .sd-hero-grid {
        grid-template-columns: minmax(220px, 320px) 1fr;
        gap: 2rem;
        align-items: center;
    }
}

/* Front poster — keep object-cover (the previous look) for a
   premium uniform 3/4 portrait crop. The blurred backdrop
   carries the full image content already. */
.sd-poster {
    position: relative;
    width: 100%;
    max-width: 22rem;
    margin-inline: auto;
    aspect-ratio: 3 / 4;
    border-radius: 1.25rem;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.10);
    box-shadow:
        0 30px 60px -20px rgba(0,0,0,0.9),
        0 0 60px rgba(250,204,21,0.20);
}
.sd-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .8s var(--sd-ease);
}
.sd-poster:hover img,
.sd-poster:focus-within img { transform: scale(1.04); }

/* Theatre-mask poster fallback when there's no poster_path. */
.sd-poster-empty {
    background: linear-gradient(135deg, #1e293b, #020617);
    display: grid;
    place-items: center;
    font-size: clamp(3rem, 8vw, 5rem);
    color: rgba(255,255,255,.4);
}

.sd-poster-badge {
    position: absolute;
    top: .7rem;
    right: .7rem;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .35rem .7rem;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .12em;
    color: #fef3c7;
    background: rgba(2,6,23,.55);
    border: 1px solid rgba(250,204,21,.45);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.sd-hero-body {
    text-align: center;
}
@media (min-width: 768px) {
    .sd-hero-body { text-align: right; }
}

.sd-hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .25rem .7rem;
    border-radius: 9999px;
    background: rgba(250,204,21,.08);
    border: 1px solid rgba(250,204,21,.35);
    color: rgba(252,211,77,.95);
    font-size: 11px;
    letter-spacing: .22em;
    text-transform: uppercase;
    font-weight: 600;
}

.sd-hero-title {
    font-size: clamp(1.75rem, 6vw, 2.8rem);
    line-height: 1.16;
    font-weight: 800;
    letter-spacing: -.01em;
    margin-top: .85rem;
    background: linear-gradient(135deg, #fde68a 0%, #fbbf24 50%, #f87171 100%);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
    text-shadow: 0 0 28px rgba(250,204,21,.18);
}

.sd-hero-desc {
    margin-top: 1rem;
    color: var(--sd-text-2);
    font-size: clamp(.95rem, 2.5vw, 1.02rem);
    line-height: 1.85;
    white-space: pre-line;
}

.sd-pill-row {
    margin-top: 1.1rem;
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: .4rem;
}
@media (min-width: 768px) {
    .sd-pill-row { justify-content: flex-start; }
}
.sd-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .35rem .8rem;
    border-radius: 9999px;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.14);
    color: var(--sd-text-2);
    font-size: 12px;
    font-weight: 500;
}

/* CTA row directly under the hero copy. The primary CTA scrolls
   to the showtimes block. */
.sd-cta-row {
    margin-top: 1.4rem;
    display: flex;
    flex-wrap: wrap;
    gap: .55rem;
    justify-content: center;
}
@media (min-width: 768px) {
    .sd-cta-row { justify-content: flex-start; }
}
.sd-cta-primary {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .8rem 1.3rem;
    min-height: 44px;
    border-radius: 9999px;
    font-weight: 800;
    font-size: .9rem;
    color: #1b1208;
    background: linear-gradient(180deg, #fde68a, #f59e0b);
    box-shadow:
        0 12px 28px -10px rgba(245,158,11,.55),
        inset 0 1px 0 rgba(255,255,255,.55);
    transition: transform .28s var(--sd-ease), box-shadow .35s ease;
}
.sd-cta-primary:hover {
    transform: translateY(-2px);
    box-shadow:
        0 18px 36px -10px rgba(245,158,11,.75),
        0 0 22px rgba(251,191,36,.4),
        inset 0 1px 0 rgba(255,255,255,.55);
}
.sd-cta-ghost {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .8rem 1.3rem;
    min-height: 44px;
    border-radius: 9999px;
    font-weight: 600;
    font-size: .9rem;
    color: #f1f5fb;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.18);
    transition: background .25s, border-color .25s, transform .25s;
}
.sd-cta-ghost:hover {
    background: rgba(255,255,255,.08);
    border-color: rgba(255,255,255,.3);
    transform: translateY(-2px);
}

/* ============================================================
   Theater-curtain hairline divider. */
.sd-divider {
    position: relative;
    height: 1px;
    width: 100%;
    margin: clamp(1.5rem, 4vw, 2.5rem) 0;
}
.sd-divider::before,
.sd-divider::after {
    content: "";
    position: absolute;
    inset: 0;
}
.sd-divider::before {
    background: linear-gradient(to left, transparent, rgba(250,204,21,.3), transparent);
}
.sd-divider::after {
    background: linear-gradient(to left, transparent, rgba(248,113,113,.2), transparent);
    filter: blur(1px);
}

.sd-section-eyebrow {
    font-size: 11px;
    letter-spacing: .24em;
    text-transform: uppercase;
    color: rgba(252,211,77,.75);
    font-weight: 600;
}
.sd-section-title {
    font-size: clamp(1.3rem, 4vw, 1.7rem);
    font-weight: 800;
    letter-spacing: -.005em;
    background: linear-gradient(135deg, #fde68a 0%, #fbbf24 50%, #f87171 100%);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
}

/* ============================================================
   SHOWTIME CARDS — ticket-stub vibe. Each row is a layered
   card with a notched left edge (the "tear here" stub feel),
   an availability indicator dot, and a clear primary CTA. */
.sd-time {
    position: relative;
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.1rem 1rem 1.4rem;
    border-radius: 1.25rem;
    background: linear-gradient(180deg, rgba(15,23,42,.72), rgba(2,6,23,.85));
    border: 1px solid var(--sd-border);
    overflow: hidden;
    transition: transform .35s var(--sd-ease),
                box-shadow .35s ease,
                border-color .35s ease;
}
.sd-time:hover,
.sd-time:focus-within {
    transform: translateY(-2px);
    box-shadow: 0 18px 36px -22px rgba(250,204,21,.3);
}
.sd-time::before {
    /* notch on the right edge for the ticket-stub effect (RTL) */
    content: "";
    position: absolute;
    top: 50%;
    right: -10px;
    width: 20px;
    height: 20px;
    border-radius: 9999px;
    background: #020617;
    border: 1px solid var(--sd-border);
    transform: translateY(-50%);
}
.sd-time-stripe {
    width: 4px;
    align-self: stretch;
    border-radius: 9999px;
}
.sd-time-stripe.is-avail   { background: linear-gradient(180deg, #34d399, #059669); }
.sd-time-stripe.is-few     { background: linear-gradient(180deg, #fbbf24, #d97706); }
.sd-time-stripe.is-soldout { background: linear-gradient(180deg, #f87171, #b91c1c); }

.sd-time.is-soldout { opacity: .65; }

.sd-time-when {
    color: var(--sd-text);
    font-weight: 700;
    font-size: clamp(.95rem, 2.5vw, 1.05rem);
    letter-spacing: -.005em;
}
.sd-time-meta {
    margin-top: .35rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    font-size: 12px;
    color: var(--sd-text-3);
}
.sd-time-price {
    color: #fde68a;
    font-weight: 700;
    font-size: 13px;
}
.sd-time-status {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .15rem .55rem;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .04em;
}
.sd-time-status.is-avail   { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.4); color: #a7f3d0; }
.sd-time-status.is-few     { background: rgba(251,191,36,.12); border: 1px solid rgba(251,191,36,.4); color: #fde68a; }
.sd-time-status.is-soldout { background: rgba(248,113,113,.12); border: 1px solid rgba(248,113,113,.4); color: #fecaca; }
.sd-time-status .dot {
    width: 7px;
    height: 7px;
    border-radius: 9999px;
    background: currentColor;
}
.sd-time-status.is-avail .dot,
.sd-time-status.is-few   .dot {
    animation: sdPulse 1.8s ease-in-out infinite;
}
@keyframes sdPulse {
    0%, 100% { opacity: .55; transform: scale(1);    }
    50%      { opacity: 1;   transform: scale(1.25); }
}

.sd-time-cta {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .65rem 1.1rem;
    min-height: 44px;
    border-radius: 9999px;
    font-weight: 800;
    font-size: 13px;
    letter-spacing: .02em;
    color: #1b1208;
    background: linear-gradient(180deg, #fde68a, #f59e0b);
    box-shadow:
        0 10px 24px -8px rgba(245,158,11,.55),
        inset 0 1px 0 rgba(255,255,255,.5);
    transition: transform .25s var(--sd-ease), box-shadow .3s ease;
    white-space: nowrap;
}
.sd-time-cta:hover {
    transform: translateY(-2px);
    box-shadow:
        0 14px 30px -8px rgba(245,158,11,.7),
        inset 0 1px 0 rgba(255,255,255,.5);
}
.sd-time-cta.is-few {
    background: linear-gradient(180deg, #fde68a, #f59e0b);
}

.sd-time-soldout {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem 1rem;
    border-radius: 9999px;
    font-weight: 700;
    font-size: 12px;
    color: #fecaca;
    background: rgba(248,113,113,.12);
    border: 1px solid rgba(248,113,113,.4);
}

@media (max-width: 480px) {
    .sd-time {
        grid-template-columns: auto 1fr;
        row-gap: .9rem;
        padding: 1rem 1rem 1rem 1.2rem;
    }
    .sd-time-cta,
    .sd-time-soldout {
        grid-column: 1 / -1;
        justify-content: center;
    }
}

/* ============================================================
   EMPTY STATE for showtimes — cinematic, matches the homepage
   "stage on a break" treatment. */
.sd-empty {
    position: relative;
    text-align: center;
    padding: 2.5rem 1.5rem;
    border-radius: var(--sd-radius);
    overflow: hidden;
    background:
        radial-gradient(ellipse at top,    rgba(250,204,21,.08), transparent 60%),
        radial-gradient(ellipse at bottom, rgba(248,113,113,.06), transparent 60%),
        rgba(2,6,23,.55);
    border: 1px solid var(--sd-border);
}

/* ============================================================
   BACK link styled as a small pill. */
.sd-back {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .65rem 1.1rem;
    min-height: 44px;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 13px;
    color: var(--sd-text);
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.18);
    transition: background .25s, border-color .25s, transform .25s;
}
.sd-back:hover {
    background: rgba(255,255,255,.08);
    border-color: rgba(252,211,77,.55);
    transform: translateY(-2px);
}

/* ============================================================
   Stagger reveal. */
.sd-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity .85s ease,
                transform .85s var(--sd-ease);
}
.sd-reveal.is-in { opacity: 1; transform: none; }

@media (prefers-reduced-motion: reduce) {
    .sd-hero-bg,
    .sd-time-status .dot,
    .sd-poster img,
    .sd-reveal,
    .sd-time,
    .sd-time:hover {
        animation: none !important;
        transition: none !important;
        transform: none !important;
    }
    .sd-reveal { opacity: 1; }
}
</style>


<section data-show-detail class="max-w-5xl mx-auto">

    {{-- Mission ribbon (replaces the old plain amber text). --}}
    <p class="sd-mission">
        <span aria-hidden="true">❤</span>
        نجول · نصرخ · فيزداد العقل وعيًا
    </p>

    {{-- ================= HERO ================= --}}
    <header class="sd-hero sd-reveal" data-stagger="0">
        @if($show->poster_path)
            <div class="sd-hero-bg"
                 style="background-image: url('{{ $show->poster_path }}');"
                 aria-hidden="true"></div>
        @endif

        <div class="sd-hero-grid">
            <div class="sd-poster">
                @if($show->poster_path)
                    <img src="{{ $show->poster_path }}"
                         alt="{{ $show->title }}"
                         loading="eager"
                         decoding="async">
                @else
                    <div class="sd-poster-empty"
                         aria-hidden="true">🎭</div>
                @endif

                <span class="sd-poster-badge">
                    <span aria-hidden="true">🎭</span>
                    عرض مسرحي
                </span>
            </div>

            <div class="sd-hero-body">
                <span class="sd-hero-eyebrow">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-300 animate-pulse"
                          aria-hidden="true"></span>
                    Now showing · على المسرح
                </span>

                <h1 class="sd-hero-title">{{ $show->title }}</h1>

                @if(filled($show->description))
                    <p class="sd-hero-desc">{{ $show->description }}</p>
                @endif

                <div class="sd-pill-row">
                    <span class="sd-pill">
                        <span aria-hidden="true">🎟️</span>
                        حجز إلكتروني + تذكرة QR
                    </span>
                    @if($show->showTimes->count())
                        <span class="sd-pill">
                            <span aria-hidden="true">🕒</span>
                            {{ $show->showTimes->count() }}
                            {{ $show->showTimes->count() === 1 ? 'موعد' : 'مواعيد' }}
                        </span>
                    @endif
                </div>

                <div class="sd-cta-row">
                    @if($show->showTimes->count())
                        <a href="#showtimes" class="sd-cta-primary"
                           aria-label="انتقل إلى قسم المواعيد المتاحة">
                            <span aria-hidden="true">🎟️</span>
                            <span>اختر موعدك</span>
                        </a>
                    @endif
                    <a href="{{ route('shows.index') }}" class="sd-cta-ghost">
                        <span aria-hidden="true">←</span>
                        <span>كل العروض</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="sd-divider" aria-hidden="true"></div>

    {{-- ================= SHOWTIMES ================= --}}
    <section id="showtimes" class="space-y-4 scroll-mt-24 sd-reveal"
             data-stagger="80"
             aria-labelledby="sd-times-h">
        <header class="space-y-1">
            <p class="sd-section-eyebrow text-center md:text-right">
                showtimes · مواعيد العرض
            </p>
            <h2 id="sd-times-h" class="sd-section-title">
                المواعيد المتاحة
            </h2>
        </header>

        @forelse($show->showTimes as $time)
            @php
                $totalTickets = $time->total_tickets;

                $reserved = \App\Models\Booking::where('show_time_id', $time->id)
                    ->whereIn('status', ['approved', 'pending'])
                    ->sum('tickets_count');

                $remaining = $totalTickets - $reserved;

                $isSoldOut  = $time->is_sold_out || $remaining <= 0;
                $fewTickets = $remaining > 0 && $remaining <= 10;

                $stripeClass = $isSoldOut ? 'is-soldout' : ($fewTickets ? 'is-few' : 'is-avail');
            @endphp

            <article class="sd-time {{ $isSoldOut ? 'is-soldout' : '' }}">
                <span class="sd-time-stripe {{ $stripeClass }}"
                      aria-hidden="true"></span>

                <div>
                    <div class="sd-time-when">
                        <span aria-hidden="true">📅</span>
                        {{ $time->date->format('d/m/Y') }}
                        <span class="text-gray-500 mx-1">•</span>
                        {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                    </div>

                    <div class="sd-time-meta">
                        <span>
                            سعر التذكرة:
                            <span class="sd-time-price">
                                {{ $time->ticket_price }} جنيه
                            </span>
                        </span>

                        <span class="sd-time-status {{ $stripeClass }}">
                            <span class="dot" aria-hidden="true"></span>
                            @if($isSoldOut)
                                Sold Out
                            @elseif($fewTickets)
                                مقاعد محدودة · {{ $remaining }}
                            @else
                                متاح
                            @endif
                        </span>
                    </div>
                </div>

                @if($isSoldOut)
                    <span class="sd-time-soldout">
                        <span aria-hidden="true">🚫</span>
                        Sold Out
                    </span>
                @else
                    <a href="{{ route('bookings.create', $time) }}"
                       class="sd-time-cta {{ $fewTickets ? 'is-few' : '' }}"
                       aria-label="احجز الآن لموعد {{ $time->date->format('d/m/Y') }} الساعة {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}">
                        <span>احجز الآن</span>
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
                @endif
            </article>
        @empty
            <div class="sd-empty">
                <div class="text-5xl mb-2" aria-hidden="true">🎭</div>
                <h3 class="text-lg font-semibold text-gray-200">
                    لسه مفيش مواعيد متاحة
                </h3>
                <p class="mt-2 text-sm text-gray-400 max-w-sm mx-auto leading-relaxed">
                    تابعنا قريبًا — هنحدّد مواعيد العرض ونبلّغك.
                </p>
            </div>
        @endforelse
    </section>

    <div class="sd-divider" aria-hidden="true"></div>

    <div class="text-center pb-2">
        <a href="{{ route('shows.index') }}" class="sd-back">
            <span aria-hidden="true">←</span>
            <span>رجوع لكل العروض</span>
        </a>
    </div>
</section>

<script>
(function () {
    var prefersReduced =
        window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var els = document.querySelectorAll('[data-show-detail] .sd-reveal');
    if (!els.length) return;

    if (!('IntersectionObserver' in window) || prefersReduced) {
        els.forEach(function (el) { el.classList.add('is-in'); });
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
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });

    els.forEach(function (el) { io.observe(el); });
})();
</script>

@endsection
