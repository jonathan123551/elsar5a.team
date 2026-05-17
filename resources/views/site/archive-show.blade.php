@extends('layouts.app')

@section('title', $archive->title)

@section('content')

{{-- ============================================================
     ARCHIVE DETAIL — cinematic redesign
     ------------------------------------------------------------
     Renders a single archived show as a digital theatre showcase:
     a poster hero with a blurred backdrop layer, a story card
     with a drop-cap, framed video embeds for the promo + full
     show, a snap-scroll mobile gallery (preserving the existing
     pinch-zoom lightbox JS), and a back-to-archive CTA.

     The Arabic copy on the page is preserved verbatim. The
     section dividers, gradient text, eyebrow style, IO-driven
     stagger reveal, and `.arch-*` token language are intentionally
     consistent with the archive-list, homepage, and footer so
     all public surfaces feel like one product.

     Fields used (from the Archive model):
       - title, description, year, poster_path
       - video_url        (YouTube)
       - facebook_reel    (Meta iframe URL)
       - images[].image_path (gallery)
============================================================ --}}

<style>
/* ============================================================
   Tokens scoped to this page so we don't bleed into other views. */
[data-arch-show] {
    --asd-radius:        1.5rem;
    --asd-radius-lg:     2rem;
    --asd-border:        rgba(255,255,255,0.10);
    --asd-border-strong: rgba(255,255,255,0.18);
    --asd-amber:         #fbbf24;
    --asd-rose:          #f87171;
    --asd-text:          #f1f5fb;
    --asd-text-2:        rgba(229,231,235,0.85);
    --asd-text-3:        rgba(229,231,235,0.55);
    --asd-ease:          cubic-bezier(.2,.7,.2,1);
}

/* ============================================================
   HERO
   ------------------------------------------------------------
   A full-bleed poster image sitting in front of a heavily
   blurred + saturated copy of itself. The blurred layer reads
   as "stage lighting that bleeds off the canvas", and works
   particularly well on tall iPhone screens where the front
   poster is letterboxed inside the frame. */
.asd-hero {
    position: relative;
    overflow: hidden;
    border-radius: var(--asd-radius-lg);
    border: 1px solid var(--asd-border);
    background: #020617;
    isolation: isolate;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.04),
        0 30px 60px -30px rgba(0,0,0,0.9);
}

.asd-hero-bg,
.asd-hero-bg::after {
    position: absolute;
    inset: 0;
    z-index: 0;
}
.asd-hero-bg {
    background-size: cover;
    background-position: center;
    filter: blur(38px) saturate(160%);
    transform: scale(1.25);
    opacity: .55;
    /* Slow, low-amplitude pan so the backdrop "breathes". */
    animation: asdBgPan 22s ease-in-out infinite alternate;
}
.asd-hero-bg::after {
    content: "";
    background:
        radial-gradient(ellipse at center, transparent 0%, rgba(2,6,23,.55) 60%, rgba(2,6,23,.95) 100%);
}
@keyframes asdBgPan {
    0%   { transform: scale(1.25) translate3d(0, 0, 0); }
    100% { transform: scale(1.32) translate3d(2%, -2%, 0); }
}

.asd-hero-fg {
    position: relative;
    z-index: 1;
    display: grid;
    place-items: center;
    padding: clamp(1rem, 4vw, 2.5rem) clamp(1rem, 4vw, 2rem) 0;
    min-height: min(58vh, 520px);
}

/* Front poster image — keep object-contain so original artwork
   isn't cropped, but cap height so on landscape phones it stays
   inside the viewport. */
.asd-hero-poster {
    width: auto;
    max-width: 100%;
    max-height: 56vh;
    border-radius: 1.25rem;
    box-shadow:
        0 30px 60px -20px rgba(0,0,0,0.9),
        0 0 60px rgba(250,204,21,0.18),
        0 0 0 1px rgba(255,255,255,0.06);
}
@media (min-width: 768px) {
    .asd-hero-poster { max-height: 64vh; }
}

/* Title strip layered at the bottom of the hero. Uses a
   gradient scrim rather than a solid block so the poster shows
   through. */
.asd-hero-strip {
    position: relative;
    z-index: 1;
    padding: 1.25rem 1.25rem 1.5rem;
    background: linear-gradient(180deg,
        rgba(2,6,23,0)   0%,
        rgba(2,6,23,.6) 40%,
        rgba(2,6,23,.95) 100%);
    text-align: center;
}
@media (min-width: 768px) {
    .asd-hero-strip {
        padding: 1.75rem 2rem 2rem;
        text-align: right;
    }
}

.asd-hero-eyebrow {
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

/* Ticket-stub-style year chip with a notched edge. */
.asd-hero-year {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .35rem .9rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(250,204,21,.18), rgba(245,158,11,.18));
    border: 1px solid rgba(252,211,77,.55);
    color: #fef3c7;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .12em;
}

.asd-hero-title {
    font-size: clamp(1.7rem, 6vw, 3rem);
    line-height: 1.18;
    font-weight: 800;
    letter-spacing: -.01em;
    color: var(--asd-text);
    text-shadow:
        0 2px 18px rgba(0,0,0,.6),
        0 0 28px rgba(250,204,21,.18);
}
.asd-hero-title-grad {
    background: linear-gradient(135deg, #fde68a 0%, #fbbf24 50%, #f87171 100%);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
}

/* ============================================================
   THEATER-CURTAIN HAIRLINE — section divider used between
   every major block. Echoes the new minimal footer. */
.asd-divider {
    position: relative;
    height: 1px;
    width: 100%;
    margin: clamp(1.5rem, 4vw, 2.5rem) 0;
    overflow: visible;
}
.asd-divider::before,
.asd-divider::after {
    content: "";
    position: absolute;
    inset: 0;
}
.asd-divider::before {
    background: linear-gradient(to left, transparent, rgba(250,204,21,.3), transparent);
}
.asd-divider::after {
    background: linear-gradient(to left, transparent, rgba(248,113,113,.2), transparent);
    filter: blur(1px);
}

/* ============================================================
   SECTION HEADER — eyebrow + gradient title. */
.asd-section-eyebrow {
    font-size: 11px;
    letter-spacing: .24em;
    text-transform: uppercase;
    color: rgba(252,211,77,.75);
    font-weight: 600;
}
.asd-section-title {
    font-size: clamp(1.2rem, 3.5vw, 1.6rem);
    font-weight: 800;
    letter-spacing: -.005em;
    background: linear-gradient(135deg, #fde68a 0%, #fbbf24 60%, #f87171 100%);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
}

/* ============================================================
   STORY (description) — drop-cap-style first letter on the
   first paragraph, theater-frame border, soft amber/rose glow. */
.asd-story {
    position: relative;
    border-radius: var(--asd-radius);
    padding: clamp(1.25rem, 3.5vw, 1.75rem);
    background:
        radial-gradient(ellipse at top, rgba(250,204,21,.05), transparent 60%),
        linear-gradient(180deg, rgba(15,23,42,.72), rgba(2,6,23,.85));
    border: 1px solid var(--asd-border);
    overflow: hidden;
}
.asd-story::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: inherit;
    padding: 1px;
    background: linear-gradient(135deg,
        rgba(250,204,21,.18),
        rgba(248,113,113,.10) 50%,
        rgba(255,255,255,.04));
    -webkit-mask:
        linear-gradient(#000 0 0) content-box,
        linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
            mask-composite: exclude;
    pointer-events: none;
}
.asd-story-body {
    color: var(--asd-text-2);
    font-size: clamp(.95rem, 2.5vw, 1.05rem);
    line-height: 1.85;
    /* Preserve operator-entered newlines in the description. */
    white-space: pre-line;
}
/* RTL drop-cap on the first letter of the first paragraph.
   `direction: rtl` is inherited from <html dir="rtl">, and
   `::first-letter` honours RTL. The amber tint matches the
   surrounding chrome. */
.asd-story-body::first-letter {
    font-size: 2.6em;
    font-weight: 800;
    line-height: 1;
    float: right;
    margin-inline-start: .35rem;
    margin-block-start: .15rem;
    background: linear-gradient(135deg, #fde68a, #fbbf24 50%, #f87171);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
}

/* ============================================================
   MEDIA FRAME — used for both the Facebook reel and the
   YouTube embed. Subtle amber/rose tinted border, a faint
   inner highlight, and a slow pulsing glow under the frame
   that reads as a "spotlight". */
.asd-media {
    position: relative;
    border-radius: var(--asd-radius);
    padding: 1rem;
    background: linear-gradient(180deg, rgba(15,23,42,.65), rgba(2,6,23,.85));
    border: 1px solid var(--asd-border);
    overflow: hidden;
}
.asd-media::after {
    content: "";
    position: absolute;
    inset: -20% -10% auto -10%;
    height: 50%;
    background: radial-gradient(ellipse at top, rgba(250,204,21,.18), transparent 70%);
    z-index: 0;
    pointer-events: none;
    filter: blur(20px);
    opacity: .85;
    animation: asdSpot 7s ease-in-out infinite alternate;
}
@keyframes asdSpot {
    0%   { opacity: .55; }
    100% { opacity: .95; }
}
.asd-media-inner {
    position: relative;
    z-index: 1;
    border-radius: 1rem;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.08);
    aspect-ratio: 16 / 9;
    background: #000;
}
.asd-media-inner iframe {
    width: 100% !important;
    height: 100% !important;
    border: 0;
    display: block;
}

/* ============================================================
   GALLERY — mobile-first horizontal scroll-snap reel with a
   live dot indicator. Same pattern as the archive list mobile
   reel so the two surfaces feel coherent. */
.asd-gallery-wrap {
    position: relative;
    border-radius: var(--asd-radius);
    background: linear-gradient(180deg, rgba(15,23,42,.65), rgba(2,6,23,.85));
    border: 1px solid var(--asd-border);
    padding: 1rem;
    overflow: hidden;
}

.asd-reel {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    overflow-y: hidden;
    scroll-snap-type: x mandatory;
    scroll-snap-stop: always;
    -webkit-overflow-scrolling: touch;
    scroll-padding: 8px;
    padding-bottom: 8px;
    scrollbar-width: none;
}
.asd-reel::-webkit-scrollbar { display: none; }

.asd-reel-item {
    flex: 0 0 auto;
    width: 80%;
    max-width: 22rem;
    aspect-ratio: 4 / 5;
    scroll-snap-align: center;
    border-radius: 1.1rem;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    background: #0b0f1a;
    border: 1px solid var(--asd-border);
    transition: transform .5s var(--asd-ease),
                box-shadow .5s ease;
}
@media (min-width: 640px) {
    .asd-reel-item { width: 46%; }
}
@media (min-width: 768px) {
    .asd-reel-item { width: 30%; }
}
.asd-reel-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 6s linear;
    will-change: transform;
}
.asd-reel-item:hover,
.asd-reel-item:focus-visible {
    transform: translateY(-3px);
    box-shadow:
        0 18px 36px -18px rgba(250,204,21,.35),
        0 0 0 1px rgba(250,204,21,.30);
}
.asd-reel-item:hover img,
.asd-reel-item:focus-visible img {
    transform: scale(1.06);
}
/* Numeric badge bottom-left so the operator (or the audience)
   can count where they are in the reel. */
.asd-reel-item::after {
    content: attr(data-idx);
    position: absolute;
    bottom: 8px;
    left: 8px;
    padding: 2px 8px;
    border-radius: 9999px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .12em;
    color: #f1f5fb;
    background: rgba(2,6,23,.65);
    border: 1px solid rgba(255,255,255,.18);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}

/* Dot row under the gallery reel. JS toggles `.is-active`
   based on the centered item. */
.asd-dots {
    margin-top: 12px;
    display: flex;
    justify-content: center;
    gap: 6px;
}
.asd-dots span {
    width: 6px;
    height: 6px;
    border-radius: 9999px;
    background: rgba(255,255,255,.18);
    transition: width .3s var(--asd-ease),
                background .3s ease;
}
.asd-dots span.is-active {
    background: rgba(252,211,77,.9);
    width: 22px;
    box-shadow: 0 0 12px rgba(250,204,21,.65);
}

.asd-gallery-hint {
    margin-top: 10px;
    text-align: center;
    font-size: 11px;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: var(--asd-text-3);
}

/* ============================================================
   BACK-TO-ARCHIVE CTA at the bottom of the page. */
.asd-back {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .8rem 1.2rem;
    min-height: 44px;
    border-radius: 9999px;
    font-weight: 700;
    font-size: .9rem;
    color: #f1f5fb;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.18);
    transition: background .25s, border-color .25s, transform .25s;
}
.asd-back:hover {
    background: rgba(255,255,255,.08);
    border-color: rgba(252,211,77,.55);
    transform: translateY(-2px);
}

/* ============================================================
   STAGGER REVEAL — sections fade in as they scroll into view. */
.asd-reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity .8s ease,
                transform .8s var(--asd-ease);
}
.asd-reveal.is-in {
    opacity: 1;
    transform: translateY(0);
}

/* ============================================================
   LIGHTBOX VIEWER chrome — the JS that drives it is preserved
   verbatim; we just dress the controls more cleanly. */
#viewer {
    position: fixed;
    inset: 0;
    z-index: 9999;
    min-height: 100dvh;
    background: rgba(0,0,0,.96);
    backdrop-filter: blur(16px) saturate(140%);
    -webkit-backdrop-filter: blur(16px) saturate(140%);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity .3s ease;
}
.asd-viewer-btn {
    position: absolute;
    z-index: 50;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 9999px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.18);
    color: #f1f5fb;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    touch-action: manipulation;
    transition: background .2s ease, border-color .2s ease, transform .2s ease;
    -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
}
.asd-viewer-btn:hover {
    background: rgba(255,255,255,.12);
    border-color: rgba(250,204,21,.5);
    transform: scale(1.05);
}
.asd-viewer-counter {
    position: absolute;
    z-index: 50;
    top: max(1rem, env(safe-area-inset-top));
    left: 50%;
    transform: translateX(-50%);
    padding: 6px 14px;
    border-radius: 9999px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.14);
    color: var(--asd-text-2);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .12em;
    -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
}

/* ============================================================
   prefers-reduced-motion — disable every decorative animation
   but keep functional transitions (lightbox open/close). */
@media (prefers-reduced-motion: reduce) {
    .asd-hero-bg,
    .asd-media::after,
    .asd-reel-item img,
    .asd-reveal,
    .asd-reel-item,
    .asd-reel-item:hover {
        animation: none !important;
        transition: none !important;
        transform: none !important;
    }
    .asd-reveal { opacity: 1; }
}
</style>


<section data-arch-show class="space-y-0 max-w-5xl mx-auto">

    {{-- ================= HERO ================= --}}
    <header class="asd-hero asd-reveal" data-stagger="0">
        @if($archive->poster_path)
            <div class="asd-hero-bg"
                 style="background-image: url('{{ $archive->poster_path }}');"
                 aria-hidden="true"></div>
        @endif

        <div class="asd-hero-fg">
            @if($archive->poster_path)
                <img src="{{ $archive->poster_path }}"
                     alt="{{ $archive->title }}"
                     class="asd-hero-poster"
                     loading="eager"
                     decoding="async">
            @else
                <div class="w-full max-w-md aspect-[2/3] rounded-2xl
                            bg-gradient-to-br from-slate-800 to-slate-950
                            flex items-center justify-center text-5xl"
                     aria-hidden="true">🎭</div>
            @endif
        </div>

        <div class="asd-hero-strip">
            <div class="flex items-center justify-center md:justify-start gap-3 flex-wrap">
                <span class="asd-hero-eyebrow">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-300 animate-pulse"
                          aria-hidden="true"></span>
                    Archive · من الأرشيف
                </span>
                @if($archive->year)
                    <span class="asd-hero-year">
                        <span aria-hidden="true">🎟️</span>
                        {{ $archive->year }}
                    </span>
                @endif
            </div>

            <h1 class="asd-hero-title mt-3">
                <span class="asd-hero-title-grad">{{ $archive->title }}</span>
            </h1>
        </div>
    </header>


    {{-- ================= STORY ================= --}}
    @if($archive->description)
        <div class="asd-divider" aria-hidden="true"></div>

        <section class="space-y-3 asd-reveal" data-stagger="80"
                 aria-labelledby="asd-story-h">
            <p class="asd-section-eyebrow text-center md:text-right">
                story · القصة
            </p>
            <h2 id="asd-story-h" class="asd-section-title">📖 وصف العرض</h2>

            <div class="asd-story">
                <div class="asd-story-body">{{ $archive->description }}</div>
            </div>
        </section>
    @endif


    {{-- ================= PROMO (Facebook reel) ================= --}}
    @if($archive->facebook_reel)
        <div class="asd-divider" aria-hidden="true"></div>

        <section class="space-y-3 asd-reveal" data-stagger="120"
                 aria-labelledby="asd-promo-h">
            <p class="asd-section-eyebrow text-center md:text-right">
                promo · البرومو
            </p>
            <h2 id="asd-promo-h" class="asd-section-title">🎬 برومو العرض</h2>

            <div class="asd-media">
                <div class="asd-media-inner">
                    <iframe
                        src="{{ $archive->facebook_reel }}"
                        loading="lazy"
                        allowfullscreen
                        allow="autoplay; clipboard-write; encrypted-media; picture-in-picture"
                        title="برومو {{ $archive->title }}"></iframe>
                </div>
            </div>
        </section>
    @endif


    {{-- ================= FULL SHOW (YouTube) ================= --}}
    @php
        // Resolve a YouTube ID from common URL shapes. Kept inline so
        // we don't ship a new helper class just for this one view.
        $yt = null;
        if (!empty($archive->video_url) &&
            preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/))([^&?#]+)~',
                       $archive->video_url, $m)) {
            $yt = $m[1];
        }
    @endphp

    @if($yt)
        <div class="asd-divider" aria-hidden="true"></div>

        <section class="space-y-3 asd-reveal" data-stagger="160"
                 aria-labelledby="asd-full-h">
            <p class="asd-section-eyebrow text-center md:text-right">
                full show · العرض الكامل
            </p>
            <h2 id="asd-full-h" class="asd-section-title">🎥 مشاهدة العرض</h2>

            <div class="asd-media">
                <div class="asd-media-inner">
                    <iframe
                        src="https://www.youtube.com/embed/{{ $yt }}"
                        loading="lazy"
                        allowfullscreen
                        title="{{ $archive->title }} — العرض الكامل"></iframe>
                </div>
            </div>
        </section>
    @endif


    {{-- ================= GALLERY ================= --}}
    @if($archive->images && $archive->images->count())
        <div class="asd-divider" aria-hidden="true"></div>

        <section class="space-y-3 asd-reveal" data-stagger="200"
                 aria-labelledby="asd-gallery-h">
            <p class="asd-section-eyebrow text-center md:text-right">
                gallery · من الكواليس
            </p>
            <h2 id="asd-gallery-h" class="asd-section-title">📸 صور من العرض</h2>

            <div class="asd-gallery-wrap">
                <div class="asd-reel" id="asd-reel" role="region"
                     aria-label="معرض صور العرض">
                    @foreach($archive->images as $i => $img)
                        <button type="button"
                                class="asd-reel-item"
                                data-idx="{{ $i + 1 }}/{{ $archive->images->count() }}"
                                onclick="openViewer({{ $i }})"
                                aria-label="عرض الصورة {{ $i + 1 }} من {{ $archive->images->count() }}">
                            <img src="{{ $img->image_path }}"
                                 alt="صورة من عرض {{ $archive->title }}"
                                 loading="lazy"
                                 decoding="async">
                        </button>
                    @endforeach
                </div>

                @if($archive->images->count() > 1)
                    <div class="asd-dots" id="asd-dots" aria-hidden="true">
                        @foreach($archive->images as $i => $_)
                            <span class="{{ $i === 0 ? 'is-active' : '' }}"></span>
                        @endforeach
                    </div>
                @endif

                <p class="asd-gallery-hint">
                    اسحب أو اضغط للتكبير
                </p>
            </div>
        </section>
    @endif


    {{-- ================= BACK TO ARCHIVE ================= --}}
    <div class="asd-divider" aria-hidden="true"></div>
    <div class="text-center pb-4">
        <a href="{{ route('archive') }}" class="asd-back">
            <span aria-hidden="true">→</span>
            <span>العودة إلى الأرشيف</span>
        </a>
    </div>

</section>


{{-- ================= LIGHTBOX VIEWER =================
     The chrome (CSS .asd-viewer-* classes + counter) is new,
     but the touch / pinch / wheel / keyboard / inertia
     handlers below are the production-tested implementation
     from the previous version of this page — preserved verbatim
     because they already handle iOS Safari edge cases (double-tap
     zoom, pinch ranges, swipe inertia, momentum throw). The only
     additions are: (1) updating `#asd-counter` on every change,
     (2) closing the viewer if the user taps the backdrop. --}}
<div id="viewer" class="hidden opacity-0"
     role="dialog"
     aria-modal="true"
     aria-label="معرض الصور">

    <span id="asd-counter" class="asd-viewer-counter">1 / 1</span>

    <button type="button"
            onclick="closeViewer()"
            class="asd-viewer-btn"
            style="top: max(1rem, env(safe-area-inset-top)); inset-inline-end: 1rem;"
            aria-label="إغلاق">✕</button>

    <button type="button"
            onclick="prevImg()"
            class="asd-viewer-btn"
            style="left: 1rem; top: 50%; transform: translateY(-50%);"
            aria-label="السابقة">‹</button>

    <button type="button"
            onclick="nextImg()"
            class="asd-viewer-btn"
            style="right: 1rem; top: 50%; transform: translateY(-50%);"
            aria-label="التالية">›</button>

    <img id="viewer-img"
         alt=""
         class="max-w-[95vw] max-h-[90dvh]
                transition-all duration-300 ease-in-out will-change-transform">
</div>

<script>
/* ------------------------------------------------------------
   Stagger reveal — mirrors the homepage + archive-list pattern.
   IO toggles `.is-in` once a section scrolls into view; falls
   back to instant visibility when IO is missing or
   prefers-reduced-motion is on. ------------------------------ */
(function () {
    var prefersReduced =
        window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var els = document.querySelectorAll('[data-arch-show] .asd-reveal');
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

/* ------------------------------------------------------------
   Gallery dot indicator — uses IO to track which reel item is
   centered, then highlights the matching dot. */
(function () {
    var reel = document.getElementById('asd-reel');
    var dotsRow = document.getElementById('asd-dots');
    if (!reel || !dotsRow || !('IntersectionObserver' in window)) return;

    var items = reel.querySelectorAll('.asd-reel-item');
    var dots  = dotsRow.querySelectorAll('span');
    if (!items.length || !dots.length) return;

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var idx = Array.prototype.indexOf.call(items, entry.target);
            if (idx < 0) return;
            dots.forEach(function (d, i) {
                d.classList.toggle('is-active', i === idx);
            });
        });
    }, { root: reel, threshold: 0.6 });

    items.forEach(function (it) { io.observe(it); });
})();
</script>

@if($archive->images && $archive->images->count())
<script>
/* ============================================================
   LIGHTBOX
   ------------------------------------------------------------
   Pinch-zoom + swipe-with-inertia + double-tap zoom + wheel
   zoom + keyboard nav. This is the previous version's
   implementation, kept verbatim because it already handles
   iOS Safari edge cases that are easy to regress. Additions
   over the old version are minimal and clearly marked. */
const images   = @json($archive->images->pluck('image_path'));
const viewer   = document.getElementById('viewer');
const img      = document.getElementById('viewer-img');
const counter  = document.getElementById('asd-counter');

let current = 0;
let scale = 1;
let startX = 0;
let currentX = 0;
let velocity = 0;
let isDragging = false;
let initialDistance = 0;
let isPinching = false;

// 🔥 preload
images.forEach(function (src) {
    const i = new Image();
    i.src = src;
});

function setCounter () {
    if (counter) {
        counter.textContent = (current + 1) + ' / ' + images.length;
    }
}

function openViewer (index) {
    current = index;
    scale = 1;
    currentX = 0;

    img.src = images[current];
    img.style.transform = 'translateX(0px) scale(1)';
    setCounter();

    viewer.classList.remove('hidden');
    setTimeout(function () { viewer.classList.remove('opacity-0'); }, 10);

    document.body.style.overflow = 'hidden';
}

function closeViewer () {
    viewer.classList.add('opacity-0');
    setTimeout(function () { viewer.classList.add('hidden'); }, 300);
    document.body.style.overflow = '';
}

function changeImage (newIndex) {
    current = (newIndex + images.length) % images.length;
    scale = 1;
    currentX = 0;

    img.style.transition = 'none';
    img.style.opacity = 0;

    setTimeout(function () {
        img.src = images[current];
        img.style.transition = 'all 0.3s ease';
        img.style.opacity = 1;
        img.style.transform = 'translateX(0px) scale(1)';
        setCounter();
    }, 80);
}

function nextImg () {
    if (scale > 1) return;
    changeImage(current + 1);
}
function prevImg () {
    if (scale > 1) return;
    changeImage(current - 1);
}

/* ---- pinch helpers ---- */
function getDistance (touches) {
    var dx = touches[0].clientX - touches[1].clientX;
    var dy = touches[0].clientY - touches[1].clientY;
    return Math.sqrt(dx * dx + dy * dy);
}

/* ---- touch ---- */
viewer.addEventListener('touchstart', function (e) {
    if (e.touches.length === 2) {
        isPinching = true;
        initialDistance = getDistance(e.touches);
        return;
    }
    isDragging = true;
    startX = e.touches[0].clientX;
    velocity = 0;
});

viewer.addEventListener('touchmove', function (e) {
    if (isPinching && e.touches.length === 2) {
        var newDistance = getDistance(e.touches);
        var zoomFactor = newDistance / initialDistance;
        scale = Math.min(Math.max(1, scale * zoomFactor), 4);
        img.style.transform = 'translateX(' + currentX + 'px) scale(' + scale + ')';
        initialDistance = newDistance;
        return;
    }

    if (!isDragging) return;

    var dx = e.touches[0].clientX - startX;
    velocity = dx - currentX;
    currentX = dx;
    img.style.transform = 'translateX(' + dx + 'px) scale(' + (scale > 1 ? scale : 0.98) + ')';
});

viewer.addEventListener('touchend', function () {
    if (isPinching) { isPinching = false; return; }
    if (!isDragging) return;

    var momentum = velocity * 3;
    currentX += momentum;
    img.style.transition = 'transform 0.3s ease-out';

    if (scale === 1 && Math.abs(currentX) > 100) {
        currentX > 0 ? prevImg() : nextImg();
    } else {
        currentX = 0;
        img.style.transform = 'translateX(0px) scale(' + scale + ')';
    }

    setTimeout(function () { img.style.transition = ''; }, 300);
    isDragging = false;
});

/* ---- double-tap zoom ---- */
let lastTap = 0;
img.addEventListener('touchend', function () {
    var now = new Date().getTime();
    if (now - lastTap < 250) {
        scale = scale === 1 ? 2.5 : 1;
        img.style.transform = 'translateX(0px) scale(' + scale + ')';
    }
    lastTap = now;
});

/* ---- wheel ---- */
img.addEventListener('wheel', function (e) {
    e.preventDefault();
    scale += e.deltaY * -0.001;
    scale = Math.min(Math.max(1, scale), 4);
    img.style.transform = 'translateX(' + currentX + 'px) scale(' + scale + ')';
}, { passive: false });

/* ---- keyboard ---- */
document.addEventListener('keydown', function (e) {
    if (viewer.classList.contains('hidden')) return;
    if (e.key === 'ArrowRight') nextImg();
    if (e.key === 'ArrowLeft')  prevImg();
    if (e.key === 'Escape')     closeViewer();
});

/* ---- backdrop-click dismiss (NEW)
   The previous version required tapping the ✕ button explicitly.
   On mobile a tap outside the image is a natural dismiss gesture
   so we wire it up here without changing any of the touch
   handlers above (those only fire when there's a touchstart). */
viewer.addEventListener('click', function (e) {
    if (e.target === viewer) closeViewer();
});
</script>
@endif

@endsection
