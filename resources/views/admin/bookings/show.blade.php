@extends('layouts.app')

@section('title', 'تفاصيل الحجز #' . $booking->id)

@section('content')
{{--
    Admin booking detail page — "theatre stage" creative direction.

    The previous version was a clean mobile dashboard, but it read as
    flat and admin-heavy. This rewrite leans into the team's identity
    (فريق الصرخة المسرحي) with a stage / cinema vocabulary:

      * The hero is a "marquee" — the show poster is used as
        ambient backlight (blurred, tinted), the booking ID is
        rendered as a giant ticket-style numeral, and the status
        pill sits inside an animated spotlight.
      * Each attendee is a TICKET STUB with a perforated edge,
        monospace code, and an LED-style delivery indicator.
      * The payment proof is a POLAROID card with a soft tilt and
        a paper shadow — physical proof, pinned to the page.
      * Primary actions live in a floating glass DOCK at the
        bottom that stays in view while the operator scrolls
        through tickets or zooms into the screenshot.

    Functional bits preserved from the previous version
    ---------------------------------------------------
      * Approve / Reject / Delete / Resend forms (same routes).
      * Full-screen processing overlay (#adm-processing) so the
        operator gets feedback during the 10-30s approve pipeline.
      * Two-step inline delete confirm — never the native
        confirm() dialog.
      * Single-submit guard on every form.
      * Mobile-zoom safety: no inputs below 16px, no sticky-hover
        scale traps, layout-level dvh / overscroll fixes inherited
        from layouts.app.

    Floating dock vs. data-sticky-action
    ------------------------------------
    We do NOT use `data-sticky-action` here. The action dock is
    PERMANENTLY visible (it's the page's anchor), not a clone that
    fades in when the natural action scrolls out of view. We add
    our own bottom padding so the dock never covers the last
    section.
--}}

<style>
/* =========================================================
   AURORA BACKDROP
   ---------------------------------------------------------
   Soft amber/red gradient blobs anchored to the viewport,
   not the page, so they keep painting as the operator
   scrolls. Pure CSS, no extra DOM, fixed position keeps
   them out of the document flow.
   ========================================================= */
.adm2-aurora {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    overflow: hidden;
}
.adm2-aurora::before,
.adm2-aurora::after {
    content: "";
    position: absolute;
    width: 60vmax;
    height: 60vmax;
    border-radius: 50%;
    filter: blur(80px);
    opacity: .35;
}
.adm2-aurora::before {
    top: -20vmax;
    inset-inline-start: -10vmax;
    background: radial-gradient(circle, rgba(245,158,11,0.55), transparent 60%);
}
.adm2-aurora::after {
    bottom: -25vmax;
    inset-inline-end: -10vmax;
    background: radial-gradient(circle, rgba(220,38,38,0.45), transparent 60%);
}

/* Content sits above the aurora. */
.adm2-stage {
    position: relative;
    z-index: 1;
    padding-bottom: calc(140px + env(safe-area-inset-bottom));
}

/* =========================================================
   GLASS PANEL — the layered card primitive used everywhere
   ---------------------------------------------------------
   Subtle backdrop-blur + saturation, translucent border, soft
   shadow lift. Glass panels feel like layers floating over
   the aurora rather than boxes fighting for attention.
   ========================================================= */
.adm2-glass {
    position: relative;
    background:
        linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)),
        rgba(15,23,42,0.55);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 1.5rem;
    backdrop-filter: blur(20px) saturate(160%);
    -webkit-backdrop-filter: blur(20px) saturate(160%);
    box-shadow:
        0 24px 60px -30px rgba(0,0,0,0.7),
        inset 0 1px 0 rgba(255,255,255,0.05);
}
.adm2-glass-soft {
    background:
        linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0)),
        rgba(15,23,42,0.4);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 1.25rem;
    backdrop-filter: blur(14px) saturate(140%);
    -webkit-backdrop-filter: blur(14px) saturate(140%);
}

/* Gentle stagger-in for the major sections. CSS-only, no JS. */
@keyframes adm2In {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.adm2-in {
    animation: adm2In .55s cubic-bezier(.2,.7,.2,1) both;
}
.adm2-in:nth-of-type(2) { animation-delay: .06s; }
.adm2-in:nth-of-type(3) { animation-delay: .12s; }
.adm2-in:nth-of-type(4) { animation-delay: .18s; }
.adm2-in:nth-of-type(5) { animation-delay: .24s; }
@media (prefers-reduced-motion: reduce) {
    .adm2-in { animation: none; }
}

/* =========================================================
   HERO MARQUEE
   ---------------------------------------------------------
   The show poster is used as ambient backlight: blurred,
   tinted, anchored to the top of the card so it never
   competes with the foreground text. The booking ID is the
   visual anchor — huge tabular numerals, ticket-style.
   ========================================================= */
.adm2-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.75rem;
    border: 1px solid rgba(255,255,255,0.08);
    padding: 22px 22px 24px;
    background:
        linear-gradient(180deg, rgba(15,23,42,0.6) 0%, rgba(2,6,23,0.85) 80%),
        rgba(15,23,42,0.6);
    backdrop-filter: blur(20px) saturate(160%);
    -webkit-backdrop-filter: blur(20px) saturate(160%);
    box-shadow: 0 30px 60px -30px rgba(0,0,0,0.8);
}
.adm2-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image: var(--adm2-poster, none);
    background-size: cover;
    background-position: center;
    opacity: .28;
    filter: blur(28px) saturate(140%);
    transform: scale(1.2);
    z-index: 0;
}
.adm2-hero::after {
    /* Theatrical curtain glow — amber on the start side,
       crimson on the end. */
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 0% 0%, rgba(252,211,77,0.18), transparent 50%),
        radial-gradient(circle at 100% 100%, rgba(248,113,113,0.18), transparent 50%);
    z-index: 0;
    pointer-events: none;
}
.adm2-hero > * { position: relative; z-index: 1; }

/* Giant booking number — feels like a printed ticket stub. */
.adm2-bk-num {
    font-feature-settings: "tnum", "ss01";
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, "Courier New", monospace;
    font-size: clamp(48px, 14vw, 84px);
    line-height: 1;
    font-weight: 800;
    letter-spacing: -0.04em;
    background: linear-gradient(180deg, #fde68a 0%, #f59e0b 60%, #b45309 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    text-shadow: 0 12px 40px rgba(245,158,11,0.25);
}
.adm2-bk-label {
    font-size: 10px;
    letter-spacing: 0.32em;
    text-transform: uppercase;
    color: rgba(252,211,77,0.7);
    font-weight: 700;
}

/* =========================================================
   SPOTLIGHT STATUS PILL
   ---------------------------------------------------------
   A pulsing ring around the status pill — like the spotlight
   on a stage. Different colour per tone. Animation pauses on
   reduced motion.
   ========================================================= */
.adm2-status {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 12.5px;
    font-weight: 700;
    letter-spacing: 0.02em;
    border: 1px solid transparent;
    isolation: isolate;
}
.adm2-status .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    box-shadow: 0 0 12px currentColor;
}
.adm2-status[data-tone="approved"] {
    color: rgb(110,231,183);
    background: rgba(16,185,129,0.12);
    border-color: rgba(16,185,129,0.45);
}
.adm2-status[data-tone="rejected"] {
    color: rgb(252,165,165);
    background: rgba(239,68,68,0.12);
    border-color: rgba(239,68,68,0.45);
}
.adm2-status[data-tone="pending"] {
    color: rgb(125,211,252);
    background: rgba(14,165,233,0.12);
    border-color: rgba(14,165,233,0.45);
}
.adm2-status::before {
    content: "";
    position: absolute;
    inset: -6px;
    border-radius: 999px;
    border: 1px solid currentColor;
    opacity: .25;
    z-index: -1;
}
.adm2-status[data-tone="pending"]::before {
    animation: adm2Ring 2.4s ease-in-out infinite;
}
@keyframes adm2Ring {
    0%, 100% { opacity: .25; transform: scale(1); }
    50%      { opacity: 0;   transform: scale(1.18); }
}
@media (prefers-reduced-motion: reduce) {
    .adm2-status[data-tone="pending"]::before { animation: none; }
}

/* =========================================================
   QUICK STATS — 3-up with subtle vertical dividers
   ---------------------------------------------------------
   No box per stat — just text grouped on a glass panel.
   ========================================================= */
.adm2-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 0;
    padding: 14px 8px;
    border-radius: 1.25rem;
}
.adm2-stats > div {
    padding: 4px 12px;
    text-align: center;
    border-inline-end: 1px solid rgba(255,255,255,0.07);
}
.adm2-stats > div:last-child { border-inline-end: 0; }
.adm2-stats .num {
    font-size: 22px;
    font-weight: 800;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.01em;
}
.adm2-stats .lbl {
    font-size: 10.5px;
    color: rgb(156,163,175);
    margin-top: 6px;
    letter-spacing: 0.04em;
}

/* =========================================================
   SECTION HEADING — title + thin animated underline
   ========================================================= */
.adm2-h {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 0 4px 12px;
}
.adm2-h h2 {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgb(252,211,77);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.adm2-h h2::before {
    content: "";
    display: inline-block;
    width: 22px;
    height: 1.5px;
    background: linear-gradient(90deg, rgb(252,211,77), transparent);
    border-radius: 999px;
}

/* =========================================================
   TICKET STUB
   ---------------------------------------------------------
   A real ticket vibe: perforated edge, monospaced code on
   the right, attendee on the left. LED-style indicator for
   delivery status. Approved actions appear as quiet ghost
   buttons inside the stub.
   ========================================================= */
.adm2-stub {
    position: relative;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 14px;
    padding: 14px 18px;
    border-radius: 16px;
    background:
        linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)),
        rgba(15,23,42,0.5);
    border: 1px solid rgba(255,255,255,0.07);
    overflow: hidden;
    transition: border-color .2s, transform .2s, box-shadow .2s;
}
@media (hover: hover) {
    .adm2-stub:hover {
        border-color: rgba(252,211,77,0.25);
        transform: translateY(-1px);
        box-shadow: 0 12px 30px -12px rgba(0,0,0,0.6);
    }
}
.adm2-stub + .adm2-stub { margin-top: 10px; }

/* Perforation line — a column of tiny circles where the
   "tear-here" would be on a real ticket stub. */
.adm2-stub::before,
.adm2-stub::after {
    content: "";
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 14px;
    height: 14px;
    background: #020617;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.07);
}
.adm2-stub::before { inset-inline-start: -7px; }
.adm2-stub::after  { inset-inline-end: -7px; }

.adm2-stub .who {
    min-width: 0;
}
.adm2-stub .who .name {
    font-size: 15px;
    font-weight: 700;
    color: white;
    line-height: 1.25;
}
.adm2-stub .who .phone {
    font-size: 12px;
    color: rgb(148,163,184);
    margin-top: 2px;
    font-variant-numeric: tabular-nums;
}
.adm2-stub .code {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 11px;
    letter-spacing: 0.04em;
    color: rgb(252,211,77);
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 6px;
    padding: 2px 7px;
    margin-top: 6px;
    display: inline-block;
}

/* The "right side" of the stub: stacked LED + small caption
   so the operator can scan delivery state at a glance. */
.adm2-stub .led {
    text-align: center;
    align-self: center;
    padding-inline-start: 12px;
    border-inline-start: 1px dashed rgba(255,255,255,0.12);
    min-width: 64px;
}
.adm2-stub .led .bulb {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin: 0 auto 6px;
}
.adm2-stub .led .bulb[data-on="true"] {
    background: rgb(74,222,128);
    box-shadow: 0 0 14px rgba(74,222,128,0.7);
}
.adm2-stub .led .bulb[data-on="false"] {
    background: rgb(248,113,113);
    box-shadow: 0 0 12px rgba(248,113,113,0.55);
}
.adm2-stub .led .cap {
    font-size: 10px;
    letter-spacing: 0.04em;
}
.adm2-stub .led .cap[data-on="true"]  { color: rgb(134,239,172); }
.adm2-stub .led .cap[data-on="false"] { color: rgb(252,165,165); }

/* Inline ghost actions inside an approved stub. */
.adm2-stub-acts {
    grid-column: 1 / -1;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed rgba(255,255,255,0.08);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
@media (max-width: 359px) {
    .adm2-stub-acts { grid-template-columns: 1fr; }
}
.adm2-stub-acts > * {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-radius: 12px;
    font-size: 12.5px;
    font-weight: 600;
    transition: background .15s, transform .1s, opacity .15s;
}
.adm2-stub-acts > *:active { transform: scale(.98); }
.adm2-stub-acts .a-view {
    background: rgba(255,255,255,0.06);
    color: white;
    border: 1px solid rgba(255,255,255,0.08);
}
.adm2-stub-acts .a-view:hover { background: rgba(255,255,255,0.1); }
.adm2-stub-acts .a-na {
    background: rgba(255,255,255,0.03);
    color: rgb(148,163,184);
    opacity: .6;
    border: 1px dashed rgba(255,255,255,0.08);
}
.adm2-stub-acts .a-resend {
    background: linear-gradient(180deg, rgba(56,189,248,0.18), rgba(14,165,233,0.18));
    color: rgb(125,211,252);
    border: 1px solid rgba(56,189,248,0.35);
}
.adm2-stub-acts .a-resend:hover { background: rgba(56,189,248,0.25); }

/* =========================================================
   POLAROID PAYMENT PROOF
   ---------------------------------------------------------
   The transfer screenshot is the operator's single most
   important visual evidence. Pin it to the page like a
   physical polaroid: paper border, soft shadow, slight tilt.
   On hover (desktop only) it straightens — gives the
   "examining proof" affordance.
   ========================================================= */
.adm2-polaroid {
    position: relative;
    padding: 12px 12px 16px;
    background: #f8fafc;
    border-radius: 4px;
    box-shadow:
        0 1px 0 rgba(0,0,0,0.4),
        0 22px 50px -20px rgba(0,0,0,0.7);
    transform: rotate(-1.2deg);
    transition: transform .35s cubic-bezier(.2,.7,.2,1);
    max-width: 100%;
}
@media (hover: hover) {
    .adm2-polaroid:hover {
        transform: rotate(0) translateY(-2px);
    }
}
.adm2-polaroid img {
    width: 100%;
    display: block;
    border-radius: 2px;
    background: #e2e8f0;
}
.adm2-polaroid .cap {
    margin-top: 10px;
    text-align: center;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 11px;
    letter-spacing: 0.04em;
    color: rgb(71,85,105);
}

/* Tape strip across the top of the polaroid — subtle but
   sells the "pinned" metaphor. */
.adm2-polaroid::before {
    content: "";
    position: absolute;
    top: -10px;
    inset-inline-start: 50%;
    transform: translateX(-50%) rotate(-2deg);
    width: 70px;
    height: 18px;
    background: rgba(252,211,77,0.55);
    border: 1px solid rgba(252,211,77,0.7);
    box-shadow: 0 2px 6px rgba(0,0,0,0.25);
}

/* =========================================================
   FLOATING ACTION DOCK
   ---------------------------------------------------------
   The page's primary CTA cluster. Pill-shaped, bottom
   anchored, backdrop-blurred, always visible.

   On pending: [Reject ghost] [Approve gradient + glow]
   On approved: [Delete (two-step)]  (no other actions)
   On rejected: dock is hidden (no actions available)

   The dock is permanent — we do NOT use data-sticky-action
   here. Bottom padding on .adm2-stage keeps content from
   ever sitting under the dock.
   ========================================================= */
.adm2-dock {
    position: fixed;
    inset-inline: 0;
    bottom: 0;
    z-index: 60;
    padding: 0 14px max(14px, env(safe-area-inset-bottom));
    pointer-events: none;
}
.adm2-dock-inner {
    pointer-events: auto;
    max-width: 36rem;
    margin: 0 auto;
    display: flex;
    gap: 10px;
    padding: 10px;
    background: rgba(2,6,23,0.55);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 999px;
    backdrop-filter: blur(20px) saturate(160%);
    -webkit-backdrop-filter: blur(20px) saturate(160%);
    box-shadow:
        0 30px 60px -20px rgba(0,0,0,0.7),
        inset 0 1px 0 rgba(255,255,255,0.06);
    animation: adm2DockIn .45s cubic-bezier(.2,.7,.2,1) both;
}
@keyframes adm2DockIn {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
@media (prefers-reduced-motion: reduce) {
    .adm2-dock-inner { animation: none; }
}

.adm2-dock-btn {
    flex: 1;
    min-height: 52px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.02em;
    transition: transform .15s, box-shadow .2s, opacity .15s;
    border: 1px solid transparent;
}
.adm2-dock-btn:active:not([disabled]) { transform: scale(.98); }
.adm2-dock-btn[disabled] {
    opacity: .65;
    cursor: progress;
}
.adm2-dock-approve {
    background: linear-gradient(180deg, #fde68a, #f59e0b 70%, #b45309);
    color: rgb(20,20,20);
    box-shadow:
        0 10px 28px -8px rgba(245,158,11,0.6),
        inset 0 1px 0 rgba(255,255,255,0.6);
}
.adm2-dock-approve:hover { box-shadow: 0 14px 32px -8px rgba(245,158,11,0.75); }
.adm2-dock-reject {
    background: rgba(239,68,68,0.12);
    color: rgb(252,165,165);
    border-color: rgba(239,68,68,0.4);
}
.adm2-dock-reject:hover { background: rgba(239,68,68,0.2); }
.adm2-dock-delete {
    background: rgba(220,38,38,0.12);
    color: rgb(252,165,165);
    border-color: rgba(220,38,38,0.45);
}
.adm2-dock-delete:hover { background: rgba(220,38,38,0.22); }
.adm2-dock-confirm {
    background: rgb(220,38,38);
    color: white;
    box-shadow: 0 10px 24px -8px rgba(220,38,38,0.6);
}
.adm2-dock-cancel {
    flex: 0 0 auto;
    min-width: 90px;
    background: rgba(255,255,255,0.06);
    color: rgb(229,231,235);
    border-color: rgba(255,255,255,0.1);
}

/* Button spinner state — same hook as the global layout
   (`is-loading`) so the existing single-submit guard works. */
.adm2-dock-btn.is-loading::before {
    content: "";
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid currentColor;
    border-top-color: transparent;
    animation: btnSpin .7s linear infinite;
    margin-inline-end: 6px;
}
.adm2-stub-acts .a-resend.is-loading::before {
    content: "";
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid currentColor;
    border-top-color: transparent;
    animation: btnSpin .7s linear infinite;
    margin-inline-end: 4px;
}

/* Two-step destructive confirm — same data-attribute API as
   before, but the markup lives inside the floating dock. */
[data-confirm] .adm2-confirm-armed { display: none; }
[data-confirm][data-armed="true"] .adm2-confirm-trigger { display: none; }
[data-confirm][data-armed="true"] .adm2-confirm-armed {
    display: flex;
    gap: 10px;
    width: 100%;
}

/* =========================================================
   PROCESSING OVERLAY
   ---------------------------------------------------------
   Same component as the previous redesign. Mounted once
   per page; forms with `data-processing-message` set it on
   submit and reveal it. The overlay closes naturally when
   the server's redirect replaces the page.
   ========================================================= */
.adm2-processing {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(2,6,23,0.78);
    backdrop-filter: blur(10px) saturate(140%);
    -webkit-backdrop-filter: blur(10px) saturate(140%);
    animation: adm2OverlayFade .18s ease-out both;
}
.adm2-processing[data-open="true"] { display: flex; }
@keyframes adm2OverlayFade { from { opacity: 0; } to { opacity: 1; } }
.adm2-processing .panel {
    width: min(380px, 100%);
    background:
        linear-gradient(180deg, rgba(245,158,11,0.08), rgba(0,0,0,0)),
        rgba(15,23,42,0.96);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 1.25rem;
    padding: 28px 24px 24px;
    text-align: center;
    box-shadow: 0 28px 80px -20px rgba(0,0,0,0.6);
    animation: adm2PanelIn .25s cubic-bezier(.2,.7,.2,1) both;
}
@keyframes adm2PanelIn {
    from { opacity: 0; transform: translateY(8px) scale(.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.adm2-processing .ring {
    width: 56px;
    height: 56px;
    margin: 0 auto 14px;
    border-radius: 50%;
    border: 3px solid rgba(245,158,11,0.15);
    border-top-color: rgb(252,211,77);
    animation: btnSpin .9s linear infinite;
}
.adm2-processing .title {
    font-size: 16px;
    font-weight: 700;
    color: rgb(254,243,199);
    margin: 4px 0 4px;
}
.adm2-processing .subtitle {
    font-size: 12.5px;
    color: rgb(156,163,175);
    line-height: 1.6;
}
.adm2-processing .steps {
    margin-top: 14px;
    text-align: start;
    font-size: 12px;
    color: rgb(203,213,225);
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 12px 14px;
}
.adm2-processing .steps li {
    list-style: none;
    padding: 4px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.adm2-processing .steps li::before {
    content: "•";
    color: rgba(252,211,77,0.8);
    font-weight: 700;
}
.adm2-processing .hint {
    margin-top: 12px;
    font-size: 11px;
    color: rgb(248,113,113);
    font-weight: 600;
}
</style>

<div class="adm2-aurora" aria-hidden="true"></div>

@php
    $show      = $booking->showTime?->show;
    $showTime  = $booking->showTime;
    $posterCss = $show?->poster_path ? "url('" . e($show->poster_path) . "')" : null;
    $sentCount  = $booking->tickets->where('whatsapp_sent', true)->count();
    $totalCount = $booking->tickets->count();
@endphp

<section class="adm2-stage space-y-5 max-w-3xl mx-auto">

    {{-- Inline back affordance — quiet so the hero owns the eye. --}}
    <div class="flex items-center justify-between pt-1">
        <a href="{{ route('admin.bookings.index') }}"
           class="inline-flex items-center gap-2 text-[12px] px-3 py-2 rounded-full
                  bg-white/5 hover:bg-white/10 border border-white/10
                  text-gray-300 transition">
            <span aria-hidden="true">→</span> رجوع للحجوزات
        </a>

        @if(session('status'))
            <div role="status"
                 class="text-[12px] px-3 py-1.5 rounded-full
                        bg-emerald-500/15 text-emerald-200
                        border border-emerald-500/40">
                {{ session('status') }}
            </div>
        @endif
    </div>

    {{-- ------------------------------------------------------
         🎭 HERO MARQUEE
         ------------------------------------------------------ --}}
    <div class="adm2-hero adm2-in"
         @if($posterCss) style="--adm2-poster: {{ $posterCss }};" @endif>

        <div class="flex items-center justify-between gap-3">
            <div class="adm2-bk-label">BOOKING / حجز رقم</div>

            @if($booking->status === 'approved')
                <span class="adm2-status" data-tone="approved">
                    <span class="dot" aria-hidden="true"></span> مقبول
                </span>
            @elseif($booking->status === 'rejected')
                <span class="adm2-status" data-tone="rejected">
                    <span class="dot" aria-hidden="true"></span> مرفوض
                </span>
            @else
                <span class="adm2-status" data-tone="pending">
                    <span class="dot" aria-hidden="true"></span> قيد المراجعة
                </span>
            @endif
        </div>

        <div class="adm2-bk-num tabular-nums mt-1">#{{ $booking->id }}</div>

        <div class="mt-3 text-[13.5px] text-gray-300 leading-relaxed">
            <span class="text-amber-300">🎭</span>
            <span class="text-white font-semibold">{{ $show?->title ?? '—' }}</span>
            @if($showTime)
                <span class="text-gray-500 mx-1">·</span>
                <span class="tabular-nums" dir="ltr">
                    {{ $showTime->date->format('d/m/Y') }}
                    · {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}
                </span>
            @endif
        </div>

        {{-- Booker — name + phone + WhatsApp deep link --}}
        <div class="mt-5 pt-5 border-t border-white/10
                    flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] uppercase tracking-[0.18em] text-gray-500">
                    booked by
                </p>
                <p class="text-[15px] font-semibold text-white mt-1 truncate">
                    {{ $booking->full_name }}
                </p>
                <p class="text-[12px] text-gray-400 mt-0.5 tabular-nums" dir="ltr">
                    {{ $booking->phone }}
                </p>
            </div>

            @if($booking->phone)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->phone) }}"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 text-[12px] px-3 py-2 rounded-full
                          bg-emerald-500/15 hover:bg-emerald-500/25
                          border border-emerald-500/40 text-emerald-200
                          transition shrink-0">
                    💬 واتساب
                </a>
            @endif
        </div>

        {{-- Lifecycle micro-timeline --}}
        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1.5 text-[11px] text-gray-500" dir="ltr">
            @if($booking->reference_code)
                <span class="font-mono">REF · {{ $booking->reference_code }}</span>
            @endif
            @if($booking->created_at)
                <span>Created · {{ $booking->created_at->format('d/m g:i A') }}</span>
            @endif
            @if($booking->approved_at)
                <span class="text-emerald-300/80">
                    Approved · {{ $booking->approved_at->format('d/m g:i A') }}
                </span>
            @endif
        </div>
    </div>

    {{-- ------------------------------------------------------
         QUICK STATS (3-up)
         ------------------------------------------------------ --}}
    <div class="adm2-glass-soft adm2-stats adm2-in">
        <div>
            <div class="num text-white">{{ $booking->tickets_count }}</div>
            <div class="lbl">تذاكر</div>
        </div>
        <div>
            <div class="num text-amber-300">
                {{ number_format((int) $booking->total_price) }}
                <span class="text-[12px] text-amber-300/70 font-normal">جنيه</span>
            </div>
            <div class="lbl">الإجمالي</div>
        </div>
        <div>
            <div class="num {{ $totalCount && $sentCount === $totalCount ? 'text-emerald-300' : 'text-white' }}">
                {{ $sentCount }}<span class="text-gray-500 text-[14px]">/{{ $totalCount }}</span>
            </div>
            <div class="lbl">واتساب</div>
        </div>
    </div>

    {{-- ------------------------------------------------------
         🎟️ TICKET STUBS
         ------------------------------------------------------ --}}
    <div class="adm2-in">
        <div class="adm2-h">
            <h2>🎟️ التذاكر</h2>
            <span class="text-[11px] text-gray-500 tabular-nums">
                {{ $totalCount }} {{ $totalCount === 1 ? 'تذكرة' : 'تذاكر' }}
            </span>
        </div>

        <div class="space-y-2.5 max-h-[58vh] overflow-auto -mx-1 px-1">
            @forelse($booking->tickets as $ticket)
                <div class="adm2-stub">
                    <div class="who">
                        <p class="name truncate">{{ $ticket->name }}</p>
                        <p class="phone truncate" dir="ltr">{{ $ticket->phone }}</p>
                        @if($ticket->ticket_code)
                            <span class="code" dir="ltr">{{ $ticket->ticket_code }}</span>
                        @endif
                    </div>

                    <div class="led">
                        <div class="bulb" data-on="{{ $ticket->whatsapp_sent ? 'true' : 'false' }}"
                             aria-hidden="true"></div>
                        <div class="cap" data-on="{{ $ticket->whatsapp_sent ? 'true' : 'false' }}">
                            {{ $ticket->whatsapp_sent ? 'تم الاستلام' : 'لم يستلم' }}
                        </div>
                    </div>

                    @if($booking->status === 'approved')
                        <div class="adm2-stub-acts">
                            @if($ticket->qr_image_path)
                                <a href="{{ $ticket->qr_image_path }}"
                                   target="_blank" rel="noopener"
                                   class="a-view">
                                    🎫 عرض التذكرة
                                </a>
                            @else
                                <span class="a-na">— التذكرة غير جاهزة</span>
                            @endif

                            <form action="{{ route('admin.resend.ticket', $ticket->id) }}"
                                  method="POST"
                                  class="contents"
                                  data-processing-message="جاري إعادة إرسال التذكرة على واتساب…"
                                  data-processing-steps='["الاتصال بـ WhatsApp Cloud API","إرسال صورة التذكرة"]'>
                                @csrf
                                <button type="submit" class="a-resend">
                                    ↪ إعادة إرسال
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="adm2-glass-soft text-center py-10 text-[12.5px] text-gray-400">
                    لا توجد تذاكر في هذا الحجز.
                </div>
            @endforelse
        </div>
    </div>

    {{-- ------------------------------------------------------
         📸 PAYMENT PROOF (polaroid)
         ------------------------------------------------------ --}}
    @if($booking->transfer_screenshot_path)
        <div class="adm2-in">
            <div class="adm2-h">
                <h2>📸 إثبات الدفع</h2>
                <a href="{{ $booking->transfer_screenshot_path }}"
                   target="_blank" rel="noopener"
                   class="text-[11px] px-2.5 py-1 rounded-full
                          bg-white/5 hover:bg-white/10 border border-white/10
                          text-gray-300 transition">
                    فتح بالحجم الكامل ↗
                </a>
            </div>

            <a href="{{ $booking->transfer_screenshot_path }}"
               target="_blank" rel="noopener"
               class="block mx-auto"
               style="max-width: 520px;">
                <div class="adm2-polaroid">
                    <img src="{{ $booking->transfer_screenshot_path }}"
                         loading="lazy" decoding="async"
                         alt="لقطة شاشة التحويل">
                    <p class="cap">TRANSFER · #{{ $booking->id }}</p>
                </div>
            </a>
        </div>
    @endif

</section>

{{-- ------------------------------------------------------------
     FLOATING ACTION DOCK
     ------------------------------------------------------------
     Permanently visible at the bottom of the viewport — the
     page's primary anchor for whatever action is available in
     the booking's current state. --}}
@if($booking->status === 'pending')
    <div class="adm2-dock" role="region" aria-label="إجراءات الحجز">
        <div class="adm2-dock-inner">
            <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST"
                  class="flex-1"
                  data-processing-message="جاري رفض الحجز…"
                  data-processing-steps='["تحديث حالة الحجز"]'>
                @csrf
                <button type="submit" class="adm2-dock-btn adm2-dock-reject w-full">
                    ✖ رفض
                </button>
            </form>

            <form action="{{ route('admin.bookings.approve', $booking) }}" method="POST"
                  class="flex-1"
                  data-processing-message="جاري اعتماد الحجز وإرسال التذاكر…"
                  data-processing-steps='["إنشاء صور التذاكر","رفع التذاكر إلى Cloudinary","إرسال إشعار الواتساب"]'
                  data-processing-hint="قد يستغرق حتى 30 ثانية. من فضلك لا تغلق الصفحة.">
                @csrf
                <button type="submit" class="adm2-dock-btn adm2-dock-approve w-full">
                    ✔ اعتماد
                </button>
            </form>
        </div>
    </div>
@elseif($booking->status === 'approved')
    <div class="adm2-dock" role="region" aria-label="إجراءات الحجز">
        <div class="adm2-dock-inner" data-confirm>
            <button type="button" class="adm2-dock-btn adm2-dock-delete adm2-confirm-trigger w-full">
                🗑️ حذف الحجز
            </button>

            <div class="adm2-confirm-armed">
                <button type="button"
                        class="adm2-dock-btn adm2-dock-cancel adm2-confirm-cancel">
                    إلغاء
                </button>
                <form action="{{ route('admin.booking.delete', $booking->id) }}" method="POST"
                      class="flex-1"
                      data-processing-message="جاري حذف الحجز وكل التذاكر المرتبطة به…"
                      data-processing-steps='["حذف التذاكر","حذف الحجز"]'>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="adm2-dock-btn adm2-dock-confirm w-full">
                        تأكيد الحذف نهائيًا
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- ------------------------------------------------------------
     PROCESSING OVERLAY (full-screen modal)
     ------------------------------------------------------------ --}}
<div id="adm2-processing"
     class="adm2-processing"
     role="dialog" aria-modal="true" aria-live="polite"
     aria-labelledby="adm2-processing-title">
    <div class="panel">
        <div class="ring" aria-hidden="true"></div>
        <h2 id="adm2-processing-title" class="title">جاري المعالجة…</h2>
        <p class="subtitle" data-role="subtitle">برجاء الانتظار حتى يكتمل الطلب.</p>
        <ul class="steps" data-role="steps" hidden></ul>
        <p class="hint" data-role="hint" hidden></p>
    </div>
</div>

<script>
/* ----------------------------------------------------------
   Two-step destructive confirm.
   ----------------------------------------------------------
   The dock contains a [data-confirm] wrapper. Tap the
   trigger to arm it (reveals confirm + cancel buttons);
   auto-disarms after 6 seconds.
   ---------------------------------------------------------- */
(function () {
    document.querySelectorAll('[data-confirm]').forEach(function (wrap) {
        var trigger = wrap.querySelector('.adm2-confirm-trigger');
        var cancel  = wrap.querySelector('.adm2-confirm-cancel');
        var timer   = null;

        function disarm() {
            wrap.removeAttribute('data-armed');
            if (timer) { clearTimeout(timer); timer = null; }
        }
        if (trigger) {
            trigger.addEventListener('click', function () {
                wrap.setAttribute('data-armed', 'true');
                if (timer) clearTimeout(timer);
                timer = setTimeout(disarm, 6000);
            });
        }
        if (cancel) cancel.addEventListener('click', disarm);
    });
})();

/* ----------------------------------------------------------
   Processing overlay + single-submit guard.
   ----------------------------------------------------------
   For every form on this page:
     1. Disable the submit button on submit so a double-tap
        doesn't fire the POST twice.
     2. If the form opted into the processing overlay via
        `data-processing-message`, populate and reveal it.
     3. Arm a `beforeunload` warning so the operator can't
        accidentally close the tab mid-request.
   ---------------------------------------------------------- */
(function () {
    var overlay  = document.getElementById('adm2-processing');
    var titleEl  = overlay && document.getElementById('adm2-processing-title');
    var subEl    = overlay && overlay.querySelector('[data-role="subtitle"]');
    var stepsEl  = overlay && overlay.querySelector('[data-role="steps"]');
    var hintEl   = overlay && overlay.querySelector('[data-role="hint"]');

    var inFlight = false;

    function showOverlay(form) {
        if (!overlay) return;
        var msg   = form.getAttribute('data-processing-message');
        var steps = form.getAttribute('data-processing-steps');
        var hint  = form.getAttribute('data-processing-hint');

        if (msg && titleEl) titleEl.textContent = msg;
        if (subEl) subEl.textContent = 'برجاء الانتظار حتى يكتمل الطلب.';
        if (stepsEl) {
            stepsEl.innerHTML = '';
            stepsEl.hidden = true;
            if (steps) {
                try {
                    var list = JSON.parse(steps);
                    if (Array.isArray(list) && list.length) {
                        list.forEach(function (s) {
                            var li = document.createElement('li');
                            li.textContent = String(s);
                            stepsEl.appendChild(li);
                        });
                        stepsEl.hidden = false;
                    }
                } catch (_) { /* ignore malformed JSON */ }
            }
        }
        if (hintEl) {
            if (hint) { hintEl.textContent = hint; hintEl.hidden = false; }
            else      { hintEl.textContent = '';   hintEl.hidden = true; }
        }
        overlay.setAttribute('data-open', 'true');
    }

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            requestAnimationFrame(function () {
                form.querySelectorAll('button[type=submit], input[type=submit]').forEach(function (b) {
                    if (b.disabled) return;
                    b.disabled = true;
                    b.classList.add('is-loading');
                });
            });
            if (form.hasAttribute('data-processing-message')) {
                inFlight = true;
                showOverlay(form);
            }
        });
    });

    window.addEventListener('beforeunload', function (e) {
        if (!inFlight) return;
        e.preventDefault();
        e.returnValue = '';
    });
})();
</script>
@endsection
