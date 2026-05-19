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
   that reads as a "spotlight".

   The inner frame uses BOTH `aspect-ratio: 16/9` (modern) AND
   a `padding-bottom: 56.25%` shim (iOS < 15 Safari) so the
   iframe always fills the box. The iframe is absolutely
   positioned over the padding-shim so the layout doesn't
   collapse when `aspect-ratio` isn't supported. */
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
    background: #000;
    /* iOS Safari < 15 fallback */
    padding-bottom: 56.25%;
    height: 0;
}
/* Modern Safari + everything else honour aspect-ratio; this
   neutralises the padding-bottom shim so we don't double-size. */
@supports (aspect-ratio: 16 / 9) {
    .asd-media-inner {
        aspect-ratio: 16 / 9;
        padding-bottom: 0;
        height: auto;
    }
}
.asd-media-inner iframe,
.asd-media-inner > .asd-fb-placeholder,
.asd-media-inner > .asd-yt-lite {
    position: absolute;
    inset: 0;
    width: 100% !important;
    height: 100% !important;
    border: 0;
    display: block;
}
@supports (aspect-ratio: 16 / 9) {
    .asd-media-inner iframe,
    .asd-media-inner > .asd-fb-placeholder,
    .asd-media-inner > .asd-yt-lite {
        position: relative;
    }
}

/* ------------------------------------------------------------
   YOUTUBE LITE EMBED — a click-to-load wrapper so the page
   doesn't ship the full ~600KB YouTube iframe on first load.
   On click we swap the wrapper for the real iframe with
   autoplay. This works reliably across Safari iOS where the
   eager-loaded iframe sometimes fails to mount. */
.asd-yt-lite {
    cursor: pointer;
    background-size: cover;
    background-position: center;
    background-color: #000;
    display: grid;
    place-items: center;
    overflow: hidden;
    -webkit-tap-highlight-color: transparent;
}
.asd-yt-lite::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at center, rgba(0,0,0,.15) 0%, rgba(0,0,0,.55) 80%);
    pointer-events: none;
    transition: background .3s ease;
}
.asd-yt-lite:hover::before,
.asd-yt-lite:focus-visible::before {
    background:
        radial-gradient(ellipse at center, rgba(0,0,0,.0) 0%, rgba(0,0,0,.35) 80%);
}
.asd-yt-lite .asd-play-btn {
    position: relative;
    z-index: 1;
    width: 72px;
    height: 50px;
    border-radius: 14px;
    background: rgba(0,0,0,.78);
    border: 1px solid rgba(255,255,255,.18);
    display: grid;
    place-items: center;
    transition: transform .3s var(--asd-ease), background .3s ease;
}
.asd-yt-lite:hover .asd-play-btn,
.asd-yt-lite:focus-visible .asd-play-btn {
    transform: scale(1.08);
    background: #e53e3e;
    border-color: rgba(255,255,255,.4);
}
.asd-yt-lite .asd-play-btn::before {
    content: "";
    width: 0;
    height: 0;
    margin-left: 4px;
    border-style: solid;
    border-width: 11px 0 11px 18px;
    border-color: transparent transparent transparent #fff;
}
.asd-yt-lite .asd-play-meta {
    position: absolute;
    bottom: 12px;
    left: 12px;
    right: 12px;
    z-index: 1;
    color: rgba(255,255,255,.85);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .12em;
    text-transform: uppercase;
    text-shadow: 0 1px 4px rgba(0,0,0,.85);
    pointer-events: none;
}

/* ------------------------------------------------------------
   FACEBOOK PLACEHOLDER — Facebook's video plugin sometimes
   silently fails (third-party cookies blocked, the user's
   browser is logged out, network is slow, etc.). This
   placeholder gives the visitor a one-tap "open on Facebook"
   fallback CTA even if the iframe loads as an empty box. */
.asd-fb-placeholder {
    background:
        linear-gradient(180deg, #0b132b 0%, #1c2541 100%);
    display: grid;
    place-items: center;
    padding: 1.5rem;
    text-align: center;
    color: var(--asd-text-2);
    text-decoration: none;
    transition: background .3s ease;
}
.asd-fb-placeholder:hover {
    background:
        linear-gradient(180deg, #14213d 0%, #2c3e80 100%);
}
.asd-fb-placeholder .asd-fb-logo {
    width: 56px;
    height: 56px;
    border-radius: 9999px;
    background: #1877f2;
    color: #fff;
    display: grid;
    place-items: center;
    font-size: 28px;
    font-weight: 900;
    margin-bottom: .75rem;
}
.asd-fb-placeholder p {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
}
.asd-fb-placeholder small {
    margin-top: .3rem;
    font-size: 11px;
    color: var(--asd-text-3);
}

/* ============================================================
   GALLERY — mobile-first horizontal scroll-snap reel with a
   live dot indicator + clickable navigation. Same pattern as
   the archive list mobile reel so the two surfaces feel
   coherent.

   Carousel UX improvements over the previous version:
     - `scroll-snap-stop: normal` instead of `always` so iOS
       Safari doesn't fight inertia.
     - `touch-action: pan-x` so vertical page scroll isn't
       hijacked by the horizontal reel.
     - Single-card-per-view on mobile (`width: 88%`) — feels
       more cinematic than the previous 3-up cramming.
     - Prev/next arrow buttons (visible on >=md), hidden on
       very small screens where the swipe IS the navigation.
     - Dot row forced to `dir: ltr` so the dots line up with
       the source order (index 0 on the left, last image on
       the right) regardless of the parent RTL document. */
.asd-gallery-wrap {
    position: relative;
    border-radius: var(--asd-radius);
    background: linear-gradient(180deg, rgba(15,23,42,.65), rgba(2,6,23,.85));
    border: 1px solid var(--asd-border);
    padding: 1rem;
    overflow: hidden;
}

.asd-reel-viewport {
    position: relative;
}

.asd-reel {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    overflow-y: hidden;
    scroll-snap-type: x mandatory;
    scroll-snap-stop: normal;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
    scroll-padding-inline: 1rem;
    padding: 2px 2px 8px;
    scrollbar-width: none;
    touch-action: pan-x pan-y;
}
.asd-reel::-webkit-scrollbar { display: none; }

/* Pointer-drag swipe on desktop (see JS at the bottom of this
   view). We hint the affordance with a `grab` cursor on devices
   with a real pointer, swap to `grabbing` while a drag is in
   progress, and disable smooth-scroll during the drag so the
   reel tracks the cursor 1:1 instead of easing behind it. */
@media (hover: hover) and (pointer: fine) {
    .asd-reel { cursor: grab; }
}
.asd-reel.is-dragging {
    cursor: grabbing;
    scroll-behavior: auto;
    scroll-snap-type: none;
    user-select: none;
}
.asd-reel.is-dragging .asd-reel-item,
.asd-reel.is-dragging .asd-reel-item:hover {
    cursor: grabbing;
}
.asd-reel.is-dragging img {
    pointer-events: none;
}

.asd-reel-item {
    flex: 0 0 auto;
    width: 88%;
    max-width: 22rem;
    aspect-ratio: 4 / 5;
    scroll-snap-align: center;
    border-radius: 1.1rem;
    overflow: hidden;
    position: relative;
    cursor: zoom-in;
    background: #0b0f1a;
    border: 1px solid var(--asd-border);
    -webkit-tap-highlight-color: transparent;
    transition: transform .5s var(--asd-ease),
                box-shadow .5s ease,
                border-color .3s ease;
}
@media (min-width: 640px) {
    .asd-reel-item { width: 60%; }
}
@media (min-width: 768px) {
    .asd-reel-item { width: 42%; max-width: 24rem; }
}
@media (min-width: 1024px) {
    .asd-reel-item { width: 32%; }
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
    border-color: rgba(250,204,21,.45);
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
/* Subtle "tap to zoom" hint icon on hover/focus. */
.asd-reel-item .asd-zoom-hint {
    position: absolute;
    top: 8px;
    inset-inline-end: 8px;
    width: 30px;
    height: 30px;
    border-radius: 9999px;
    background: rgba(2,6,23,.65);
    border: 1px solid rgba(255,255,255,.18);
    color: #fef3c7;
    font-size: 14px;
    display: grid;
    place-items: center;
    opacity: 0;
    transform: scale(.92);
    transition: opacity .25s ease, transform .25s var(--asd-ease);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    pointer-events: none;
}
.asd-reel-item:hover .asd-zoom-hint,
.asd-reel-item:focus-visible .asd-zoom-hint {
    opacity: 1;
    transform: scale(1);
}

/* Prev/next arrow buttons. Hidden on small screens where
   swipe is the natural gesture; visible from `sm`+ as an
   accessible nav fallback. Positioned over the viewport so
   they don't shift the layout. */
.asd-reel-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 9999px;
    background: rgba(2,6,23,.72);
    border: 1px solid rgba(255,255,255,.18);
    color: #f1f5fb;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 3;
    -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
    transition: background .25s ease, border-color .25s ease,
                transform .2s ease, opacity .2s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}
.asd-reel-nav:hover,
.asd-reel-nav:focus-visible {
    background: rgba(2,6,23,.88);
    border-color: rgba(252,211,77,.55);
    transform: translateY(-50%) scale(1.06);
}
.asd-reel-nav:disabled {
    opacity: .35;
    cursor: default;
    pointer-events: none;
}
.asd-reel-nav.is-prev { inset-inline-start: -6px; }
.asd-reel-nav.is-next { inset-inline-end: -6px; }
@media (min-width: 640px) {
    .asd-reel-nav { display: inline-flex; }
    .asd-reel-nav.is-prev { inset-inline-start: 6px; }
    .asd-reel-nav.is-next { inset-inline-end: 6px; }
}

/* Edge fades on the reel so cut-off cards feel intentional
   instead of clipped. Pure CSS, no extra DOM. */
.asd-reel-viewport::before,
.asd-reel-viewport::after {
    content: "";
    position: absolute;
    top: 0;
    bottom: 8px;
    width: 28px;
    pointer-events: none;
    z-index: 2;
    transition: opacity .3s ease;
}
.asd-reel-viewport::before {
    inset-inline-start: 0;
    background: linear-gradient(to right, rgba(2,6,23,.85), transparent);
}
.asd-reel-viewport::after {
    inset-inline-end: 0;
    background: linear-gradient(to left, rgba(2,6,23,.85), transparent);
}
.asd-reel-viewport.at-start::before { opacity: 0; }
.asd-reel-viewport.at-end::after { opacity: 0; }

/* Dot row under the gallery reel. JS toggles `.is-active`
   based on the centered item. Forced LTR so the dots line up
   with the source-order images, not the RTL visual flow.

   Dots are now interactive (the parent is a button) so they
   can drive the reel forward/backward — small but high-value
   on tablets where prev/next is the natural gesture. */
.asd-dots {
    margin-top: 14px;
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 6px;
    direction: ltr;
    /* On long galleries cap the row so it doesn't take five
       lines of vertical space. */
    max-width: 100%;
}
.asd-dot {
    width: 8px;
    height: 8px;
    padding: 0;
    border: 0;
    border-radius: 9999px;
    background: rgba(255,255,255,.22);
    cursor: pointer;
    transition: width .3s var(--asd-ease),
                background .3s ease,
                transform .25s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}
.asd-dot:hover,
.asd-dot:focus-visible {
    background: rgba(252,211,77,.55);
    transform: scale(1.2);
}
.asd-dot.is-active {
    background: rgba(252,211,77,.95);
    width: 24px;
    box-shadow: 0 0 12px rgba(250,204,21,.65);
}

.asd-gallery-meta {
    margin-top: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.asd-gallery-hint {
    font-size: 11px;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: var(--asd-text-3);
    margin: 0;
}
.asd-gallery-count {
    font-size: 12px;
    font-weight: 700;
    color: var(--asd-text-2);
    background: rgba(255,255,255,.04);
    border: 1px solid var(--asd-border);
    border-radius: 9999px;
    padding: 4px 10px;
    direction: ltr;
}
.asd-gallery-count strong {
    color: #fde68a;
    font-weight: 800;
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


    {{-- ================= PROMO (Facebook reel) =================
         Facebook embeds are notoriously fragile (third-party cookies,
         login walls, region blocks). We normalize the stored URL into
         the canonical `plugins/video.php` form server-side, then wrap
         the iframe in a placeholder that doubles as a fallback CTA so
         the visitor can always reach the video on facebook.com even if
         the iframe never paints. --}}
    @php
        // Normalize the stored URL to the canonical Facebook plugin
        // form. Accepts (a) a raw page URL (`/elsar5ateam/videos/123/`),
        // (b) a `share/v/` URL, or (c) an already-built plugin URL.
        //
        // We ALWAYS unwrap and re-clean the underlying watch URL —
        // including when the stored value is already a plugin URL —
        // because the inner `href=` query param is often copied from
        // a share link with tracking params (`fs`, `mibextid`,
        // `rdid`, `__cft__`, …) plus a trailing `#`. The Facebook
        // video plugin silently fails to play when those params are
        // present in the underlying watch URL.
        $rawFb   = trim((string) $archive->facebook_reel);
        $fbEmbed = null;
        $fbWatch = null;
        if ($rawFb !== '') {
            $candidate = $rawFb;

            // 1) If we were handed a plugin URL, unwrap it back to
            //    the underlying watch URL so we can clean it.
            if (str_contains($candidate, 'facebook.com/plugins/')
                && preg_match('~[?&]href=([^&]+)~', $candidate, $m)) {
                $candidate = urldecode($m[1]);
            }

            // 2) Strip tracking params + trailing fragment. The
            //    `[?&]` swallows the leading delimiter together with
            //    the param, which leaves a clean URL when (as is
            //    typical) every share param is in the strip list.
            $clean = preg_replace(
                '~[?&](mibextid|fs|rdid|rdc|fb_ref|fbclid|__cft__|__tn__|set)=[^&#]*~',
                '', $candidate
            );
            $clean = preg_replace('~#.*$~', '', $clean);
            // If stripping the first param left an orphaned `&`,
            // turn it back into the URL's `?` separator.
            $clean = preg_replace('~([^?])&(?=[^=&]+=)~', '$1?', $clean, 1);

            $fbWatch = $clean;
            $fbEmbed = 'https://www.facebook.com/plugins/video.php'
                     . '?href=' . urlencode($clean)
                     . '&show_text=false'
                     . '&autoplay=false';
        }
    @endphp

    @if($fbEmbed)
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
                        src="{{ $fbEmbed }}"
                        loading="lazy"
                        allowfullscreen
                        allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin"
                        title="برومو {{ $archive->title }}"
                        scrolling="no"
                        frameborder="0"></iframe>
                </div>
                @if($fbWatch)
                    <div class="mt-3 text-center md:text-right">
                        <a href="{{ $fbWatch }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 text-xs
                                  font-semibold tracking-[.18em] uppercase
                                  text-amber-200/85 hover:text-amber-300
                                  underline-offset-4 hover:underline">
                            <span aria-hidden="true">↗</span>
                            <span>افتح على فيسبوك</span>
                        </a>
                    </div>
                @endif
            </div>
        </section>
    @endif


    {{-- ================= FULL SHOW (YouTube) ================= --}}
    @php
        // Resolve a YouTube ID from common URL shapes:
        //   - youtu.be/{id}
        //   - youtube.com/watch?v={id}
        //   - youtube.com/embed/{id}
        //   - youtube.com/shorts/{id}
        //   - m.youtube.com/...
        //   - music.youtube.com/...
        //   - youtube-nocookie.com/embed/{id}
        $yt = null;
        if (!empty($archive->video_url)) {
            $urlStr = (string) $archive->video_url;
            if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|m\.youtube\.com/watch\?(?:.*&)?v=|music\.youtube\.com/watch\?(?:.*&)?v=)([A-Za-z0-9_-]{6,})~i',
                           $urlStr, $m)) {
                $yt = $m[1];
            }
        }
        // YouTube thumbnail URL — `hqdefault` is the most-cached size
        // and is guaranteed to exist for every public video.
        $ytThumb = $yt ? "https://i.ytimg.com/vi/{$yt}/hqdefault.jpg" : null;
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
                    {{-- "Lite YouTube" — click-to-load wrapper.
                         Renders the official iframe only after the user
                         taps Play, so the page doesn't pull ~600KB of
                         YouTube JS up front, and so iOS Safari (which
                         occasionally fails to paint a lazy iframe) has
                         a guaranteed-visible fallback. --}}
                    <button type="button"
                            class="asd-yt-lite"
                            data-yt-id="{{ $yt }}"
                            style="background-image: url('{{ $ytThumb }}');"
                            aria-label="تشغيل: {{ $archive->title }} — العرض الكامل">
                        <span class="asd-play-btn" aria-hidden="true"></span>
                        <span class="asd-play-meta">Watch on YouTube</span>
                    </button>
                </div>
                {{-- Permanent fallback CTA — YouTube occasionally
                     shows its anti-bot "Sign in to confirm you're
                     not a bot" wall inside the embed, especially on
                     mobile data networks or shared IPs. This link
                     guarantees the visitor can always open the
                     video on YouTube directly, exactly like the
                     Facebook fallback above. --}}
                <div class="mt-3 text-center md:text-right">
                    <a href="https://www.youtube.com/watch?v={{ $yt }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 text-xs
                              font-semibold tracking-[.18em] uppercase
                              text-amber-200/85 hover:text-amber-300
                              underline-offset-4 hover:underline">
                        <span aria-hidden="true">↗</span>
                        <span>افتح على يوتيوب</span>
                    </a>
                </div>
            </div>
        </section>
    @endif


    {{-- ================= GALLERY ================= --}}
    @if($archive->images && $archive->images->count())
        @php $imgCount = $archive->images->count(); @endphp
        <div class="asd-divider" aria-hidden="true"></div>

        <section class="space-y-3 asd-reveal" data-stagger="200"
                 aria-labelledby="asd-gallery-h">
            <p class="asd-section-eyebrow text-center md:text-right">
                gallery · من الكواليس
            </p>
            <h2 id="asd-gallery-h" class="asd-section-title">📸 صور من العرض</h2>

            <div class="asd-gallery-wrap">
                <div class="asd-reel-viewport at-start" id="asd-reel-viewport">
                    <div class="asd-reel" id="asd-reel" role="region"
                         aria-label="معرض صور العرض"
                         aria-roledescription="carousel"
                         tabindex="0">
                        @foreach($archive->images as $i => $img)
                            <button type="button"
                                    class="asd-reel-item"
                                    data-idx="{{ $i + 1 }}/{{ $imgCount }}"
                                    data-index="{{ $i }}"
                                    onclick="openViewer({{ $i }})"
                                    aria-label="عرض الصورة {{ $i + 1 }} من {{ $imgCount }}">
                                <img src="{{ $img->image_path }}"
                                     alt="صورة من عرض {{ $archive->title }}"
                                     loading="lazy"
                                     decoding="async"
                                     draggable="false">
                                <span class="asd-zoom-hint" aria-hidden="true">⤢</span>
                            </button>
                        @endforeach
                    </div>

                    @if($imgCount > 1)
                        {{-- Prev/next buttons. `prev` jumps one card to
                             the left (regardless of RTL), `next` to the
                             right; JS handles the scroll math so the
                             behaviour matches what the user sees. --}}
                        <button type="button"
                                id="asd-reel-prev"
                                class="asd-reel-nav is-prev"
                                aria-label="الصورة السابقة">‹</button>
                        <button type="button"
                                id="asd-reel-next"
                                class="asd-reel-nav is-next"
                                aria-label="الصورة التالية">›</button>
                    @endif
                </div>

                @if($imgCount > 1)
                    <div class="asd-dots" id="asd-dots"
                         role="tablist"
                         aria-label="انتقال إلى صورة بعينها">
                        @foreach($archive->images as $i => $_)
                            <button type="button"
                                    class="asd-dot {{ $i === 0 ? 'is-active' : '' }}"
                                    role="tab"
                                    aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                                    aria-label="الصورة {{ $i + 1 }}"
                                    data-target="{{ $i }}"></button>
                        @endforeach
                    </div>
                @endif

                <div class="asd-gallery-meta">
                    <p class="asd-gallery-hint">
                        اسحب · اضغط للتكبير
                    </p>
                    @if($imgCount > 1)
                        <span class="asd-gallery-count" aria-live="polite">
                            <strong id="asd-current-idx">1</strong>
                            <span aria-hidden="true">/</span>
                            <span>{{ $imgCount }}</span>
                        </span>
                    @endif
                </div>
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

    {{-- `dir="ltr"` keeps the "3 / 19" counter from being
         flipped to "19 / 3" by the page-level RTL direction. --}}
    <span id="asd-counter" class="asd-viewer-counter" dir="ltr">1 / 1</span>

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
   GALLERY CAROUSEL
   ------------------------------------------------------------
   The reel is a `scroll-snap` flex row. We layer on:
     - Scroll-position-based active-item tracking (rAF
       throttled), which is more reliable than IO on iOS Safari
       than the previous IO-threshold approach.
     - Clickable dots that smoothly scroll to the target card.
     - Prev/next buttons that snap one card at a time, with the
       direction flipped under RTL so the user-visible "next"
       arrow actually advances forward through the images.
     - Keyboard nav (← → Home End) when the reel itself is
       focused.
     - Edge-fade hide/show via `at-start` / `at-end` classes
       so cut-off cards feel intentional, not clipped. */
(function () {
    var viewport = document.getElementById('asd-reel-viewport');
    var reel     = document.getElementById('asd-reel');
    if (!viewport || !reel) return;

    var items = Array.prototype.slice.call(reel.querySelectorAll('.asd-reel-item'));
    if (!items.length) return;

    var dotsRow  = document.getElementById('asd-dots');
    var dots     = dotsRow ? Array.prototype.slice.call(dotsRow.querySelectorAll('.asd-dot')) : [];
    var prevBtn  = document.getElementById('asd-reel-prev');
    var nextBtn  = document.getElementById('asd-reel-next');
    var counter  = document.getElementById('asd-current-idx');

    // RTL detection — the prev/next arrow buttons need to move
    // physical pixels, but the user-visible "next" should mean
    // forward through the index. Under RTL, scrollLeft is
    // negative on standards-mode browsers (Chrome/Safari/Firefox
    // since 2019) so "next" = increase index = scroll towards
    // more negative scrollLeft.
    var isRTL = getComputedStyle(reel).direction === 'rtl';

    function isReducedMotion () {
        return window.matchMedia &&
               window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function scrollToIndex (idx, smooth) {
        if (idx < 0) idx = 0;
        if (idx > items.length - 1) idx = items.length - 1;
        var target = items[idx];
        if (!target) return;
        // Use the item's center, not start, so RTL/LTR behave
        // identically.
        var reelRect = reel.getBoundingClientRect();
        var itemRect = target.getBoundingClientRect();
        var offset   = (itemRect.left + itemRect.width / 2) -
                       (reelRect.left + reelRect.width / 2);
        reel.scrollTo({
            left: reel.scrollLeft + offset,
            behavior: (smooth && !isReducedMotion()) ? 'smooth' : 'auto'
        });
    }

    function currentIndex () {
        // Find the item whose center is closest to the reel's
        // viewport center. This is more reliable than IO
        // threshold + intersection ratio under scroll-snap.
        var reelRect = reel.getBoundingClientRect();
        var reelCenterX = reelRect.left + reelRect.width / 2;
        var closest = 0;
        var closestDist = Infinity;
        for (var i = 0; i < items.length; i++) {
            var r = items[i].getBoundingClientRect();
            var c = r.left + r.width / 2;
            var d = Math.abs(c - reelCenterX);
            if (d < closestDist) {
                closestDist = d;
                closest = i;
            }
        }
        return closest;
    }

    function updateActive () {
        var idx = currentIndex();

        // Dots
        for (var i = 0; i < dots.length; i++) {
            var on = (i === idx);
            dots[i].classList.toggle('is-active', on);
            dots[i].setAttribute('aria-selected', on ? 'true' : 'false');
        }

        // Counter
        if (counter) counter.textContent = String(idx + 1);

        // Edge fade visibility — based on actual scrollability,
        // not index, so it stays accurate even with weird padding.
        // `Math.abs` because RTL gives negative scrollLeft on some
        // engines.
        var sl     = Math.abs(reel.scrollLeft);
        var maxSL  = reel.scrollWidth - reel.clientWidth;
        var atStart = sl <= 2;
        var atEnd   = sl >= maxSL - 2;
        viewport.classList.toggle('at-start', atStart);
        viewport.classList.toggle('at-end',   atEnd);

        // Prev/next disabled states. Note: when isRTL, the
        // user-visible "prev" arrow (which points to ‹ in the
        // markup) still maps to "go to earlier index", so we
        // disable it on idx === 0 regardless of RTL.
        if (prevBtn) prevBtn.disabled = (idx === 0);
        if (nextBtn) nextBtn.disabled = (idx === items.length - 1);
    }

    // rAF-throttle the scroll handler so we don't burn the
    // main thread on a 60+ FPS swipe.
    var rafPending = false;
    function onScroll () {
        if (rafPending) return;
        rafPending = true;
        requestAnimationFrame(function () {
            rafPending = false;
            updateActive();
        });
    }

    reel.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });

    // Dot click — jump to that image.
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            var idx = parseInt(dot.getAttribute('data-target') || '0', 10);
            scrollToIndex(idx, true);
        });
    });

    // Prev/next — visually "‹" goes to lower index, "›" goes
    // higher. This is independent of RTL: the arrow icon makes
    // the intent clear, and matching the visual direction is
    // what users expect on a horizontal carousel.
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            scrollToIndex(currentIndex() - 1, true);
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            scrollToIndex(currentIndex() + 1, true);
        });
    }

    // Keyboard nav when the reel itself is focused.
    reel.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            scrollToIndex(currentIndex() + (isRTL ? -1 : 1), true);
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            scrollToIndex(currentIndex() + (isRTL ? 1 : -1), true);
        } else if (e.key === 'Home') {
            e.preventDefault();
            scrollToIndex(0, true);
        } else if (e.key === 'End') {
            e.preventDefault();
            scrollToIndex(items.length - 1, true);
        }
    });

    // Initial sync — and run again on the next animation frame
    // because Safari sometimes reports `scrollWidth === clientWidth`
    // on first paint before the images have laid out.
    updateActive();
    requestAnimationFrame(updateActive);
    setTimeout(updateActive, 250);
})();

/* ------------------------------------------------------------
   YouTube "Lite Embed" — swap the placeholder for the real
   iframe on first user click. We use the privacy-friendly
   `youtube-nocookie.com` host and request autoplay so the user
   doesn't have to click play a second time. The `allow`
   attribute carries every permission YouTube needs to render
   the full player (PiP, encrypted-media for premium / HD
   streams, fullscreen, web-share).

   Defensive: if YouTube is blocked by the browser / network,
   the user can long-press the thumbnail to open the video on
   YouTube directly via the link in the play-meta label. */
(function () {
    var lite = document.querySelectorAll('.asd-yt-lite');
    if (!lite.length) return;

    lite.forEach(function (el) {
        el.addEventListener('click', function () {
            var id = el.getAttribute('data-yt-id');
            if (!id) return;

            // Use `www.youtube.com` (not `youtube-nocookie.com`) on
            // the swap. The nocookie host has noticeably stricter
            // anti-bot heuristics and from some IP ranges it shows
            // the "Sign in to confirm you're not a bot" wall
            // instead of the player. The standard host is more
            // tolerant and falls back gracefully.
            var iframe = document.createElement('iframe');
            iframe.setAttribute('src',
                'https://www.youtube.com/embed/' + id +
                '?autoplay=1&rel=0&modestbranding=1&playsinline=1');
            iframe.setAttribute('title', el.getAttribute('aria-label') || 'YouTube video');
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allowfullscreen', '');
            iframe.setAttribute('allow',
                'accelerometer; autoplay; clipboard-write; ' +
                'encrypted-media; gyroscope; picture-in-picture; web-share');
            iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            iframe.setAttribute('loading', 'eager');
            // Hand off to the iframe and remove the placeholder.
            el.replaceWith(iframe);
        }, { once: true });
    });
})();

/* ------------------------------------------------------------
   GALLERY POINTER-DRAG SWIPE — desktop only.
   Touch devices already swipe natively via `overflow-x: auto`
   + scroll-snap, but mouse/pen input doesn't drag-scroll a
   scroller by default. We wire up Pointer Events so a user on
   desktop can click-and-drag the reel exactly like they would
   swipe on a phone. We skip `pointerType === 'touch'` so we
   don't fight the browser's native touch-scroll, and we
   swallow the click that follows a real drag so the gallery
   item doesn't open the lightbox at the end of a swipe. */
(function () {
    var reel = document.getElementById('asd-reel');
    if (!reel) return;

    var isDown   = false;
    var startX   = 0;
    var startSL  = 0;
    var moved    = false;
    var DRAG_THR = 6; // px before we count it as a drag

    reel.addEventListener('pointerdown', function (e) {
        // Native touch-scroll is better than anything we can fake.
        if (e.pointerType === 'touch') return;
        // Primary button only.
        if (e.button !== 0) return;
        isDown  = true;
        moved   = false;
        startX  = e.clientX;
        startSL = reel.scrollLeft;
        reel.classList.add('is-dragging');
        // Capture so we keep receiving move/up even if the
        // pointer leaves the reel mid-drag.
        try { reel.setPointerCapture(e.pointerId); } catch (_) {}
    });

    reel.addEventListener('pointermove', function (e) {
        if (!isDown) return;
        var dx = e.clientX - startX;
        if (!moved && Math.abs(dx) > DRAG_THR) moved = true;
        if (moved) {
            reel.scrollLeft = startSL - dx;
            // Stops text-selection / image-drag from kicking in.
            e.preventDefault();
        }
    });

    function endDrag (e) {
        if (!isDown) return;
        isDown = false;
        reel.classList.remove('is-dragging');
        if (e && e.pointerId !== undefined) {
            try { reel.releasePointerCapture(e.pointerId); } catch (_) {}
        }
        // Scroll-snap re-snaps to the nearest card on its own.
    }

    reel.addEventListener('pointerup',     endDrag);
    reel.addEventListener('pointercancel', endDrag);

    // If the user actually dragged, swallow the click so the
    // gallery item doesn't open the lightbox at the end of a
    // swipe. We listen in the capture phase so we beat the
    // inline `onclick="openViewer(...)"` on the buttons.
    reel.addEventListener('click', function (e) {
        if (moved) {
            e.preventDefault();
            e.stopPropagation();
            moved = false;
        }
    }, true);
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
