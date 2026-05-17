@extends('layouts.app')

@section('title', 'Scanner')

@section('content')
{{--
    Premium gate scanner — ported from the Joseph Nabil scanner engine
    (PR #69 → PR #74) with the BookingSeat-specific features removed
    because Elsar5a.Team does not have a BookingSeat / Seat model.

    The page is structured around three layers, all scoped under
    [data-scanner-root] so the rest of the app's CSS is unaffected:

      1. Camera frame (`#qr-reader`)         — html5-qrcode mounts the
         <video> here. Wrapped in a tall, full-bleed cinematic shell.
      2. Status pill                          — top-of-camera state
         feedback (Ready / Scanning / OK / Used / Invalid).
      3. Premium result sheet (`#scan-sheet`) — slides up from the
         bottom after every scan with attendee + booking details. The
         scanner is paused while the sheet is open and resumes the
         instant the operator taps Done / outside / Esc.

    Backend contract (POST /admin/scanner/check) returns:
      { status: 'ok'|'used'|'error',
        name, phone, show_title, date, time,
        tickets_count, reference, scanned_at }

    Detection engines, in priority order:
      1. The browser's NATIVE BarcodeDetector API
         (Android Chrome / Edge — uses platform VisionKit / ML Kit
          for hardware-accelerated decoding).
      2. ZXing-js — same engine many professional event-entry
         scanners use, dramatically more tolerant of tilt /
         distance / partial framing / low-light than jsQR.
      3. html5-qrcode (jsQR) — last-resort fallback if neither of
         the above is available.

      Both bundles are SELF-HOSTED out of public/vendor/.
--}}

<section data-scanner-root class="prism-fade-up">

    {{-- HEADER --}}
    <div class="scanner-header">
        <div class="space-y-1">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                <span>Gate Scanner</span>
            </span>
            <h1 class="prism-headline text-base">
                <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    🎫 Gate Scanner
                </span>
            </h1>
        </div>

        @auth
            <a href="{{ route('admin.dashboard') }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                <span>رجوع</span>
            </a>
        @else
            <a href="{{ url('/') }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                <span>رجوع</span>
            </a>
        @endauth
    </div>

    {{-- SCANNER CHROME --}}
    <div class="scanner-stage" data-scanner-stage>

        {{-- Camera mount. html5-qrcode injects its <video> here. --}}
        <div id="qr-reader" class="scanner-video"></div>

        {{-- Live zoom indicator. Only shows when zoom > 1.0× via the
             is-visible class. Sits outside #qr-reader so it survives
             the innerHTML wipe Path A / Path B do at mount time. --}}
        <div id="scan-zoom-chip" class="scan-zoom-chip" aria-hidden="true">
            <span data-zoom-text>1.0×</span>
        </div>

        {{-- Reticle frame + corner brackets + scan line. Pointer-events:
             none so taps fall through to the camera. --}}
        <div class="scanner-overlay" aria-hidden="true">
            <div class="scanner-reticle">
                <span class="reticle-corner tl"></span>
                <span class="reticle-corner tr"></span>
                <span class="reticle-corner bl"></span>
                <span class="reticle-corner br"></span>
                <span class="reticle-line"></span>
            </div>
        </div>

        {{-- Live status pill. --}}
        <div id="status"
             class="scanner-status state-ready"
             role="status"
             aria-live="polite">
            جاهز للفحص
        </div>

        {{-- Loading state shown until the camera produces its first
             frame. Replaced as soon as html5-qrcode resolves. --}}
        <div id="scanner-loading" class="scanner-loading">
            <div class="prism-spinner" aria-hidden="true"></div>
            <div class="text-xs">
                جاري تشغيل الكاميرا…
            </div>
        </div>
    </div>

    {{-- CONTROLS --}}
    <div class="scanner-controls">
        <button id="flashBtn" class="prism-btn-ghost text-xs py-3" type="button">
            🔦 Flash
        </button>
        <button id="restartBtn" class="prism-btn-ghost text-xs py-3" type="button">
            🔄 Restart
        </button>
    </div>

</section>

{{-- PREMIUM SCAN-RESULT SHEET --}}
<div id="scan-sheet"
     class="scan-sheet"
     data-state="hidden"
     role="dialog"
     aria-modal="false"
     aria-live="polite"
     aria-labelledby="scan-sheet-title">

    <div class="scan-sheet-card" data-scan-card>

        {{-- Status badge — color reflects ok / used / error. --}}
        <div class="scan-sheet-badge" data-scan-badge>
            <span class="scan-sheet-badge-icon" data-scan-icon>✓</span>
            <span class="scan-sheet-badge-text" data-scan-badge-text>
                دخول مسموح
            </span>
        </div>

        {{-- Attendee — large, prominent. --}}
        <div class="scan-sheet-name" id="scan-sheet-title" data-scan-name>—</div>
        <div class="scan-sheet-ref" data-scan-ref></div>

        {{-- Show / showtime --}}
        <div class="scan-sheet-row">
            <span class="scan-sheet-row-icon" aria-hidden="true">🎭</span>
            <span class="scan-sheet-row-text" data-scan-show>—</span>
        </div>
        <div class="scan-sheet-row">
            <span class="scan-sheet-row-icon" aria-hidden="true">🕒</span>
            <span class="scan-sheet-row-text" data-scan-when>—</span>
        </div>

        {{-- Already-scanned note --}}
        <div class="scan-sheet-used-note" data-scan-used-note hidden>
            <span aria-hidden="true">⚠️</span>
            <span>هذه التذكرة تم استخدامها سابقًا</span>
            <strong data-scan-used-time></strong>
        </div>

        {{-- Footer — operator dismisses manually. The scanner stays
             paused while the sheet is open and resumes the instant
             the operator taps Done / outside / Esc. --}}
        <div class="scan-sheet-foot">
            <button type="button"
                    class="prism-btn-gold text-sm scan-sheet-done"
                    data-scan-dismiss>
                تم — التالي
            </button>
            <span class="scan-sheet-hint">
                اضغط للإغلاق ومتابعة المسح
            </span>
        </div>
    </div>
</div>

{{-- Self-hosted decode engines. Self-hosting drops first-scan time
     noticeably and removes the "camera area stays black for ages on
     a fresh page load" complaint. --}}
<script src="{{ asset('vendor/zxing/library-0.21.3.min.js') }}"></script>
<script src="{{ asset('vendor/html5-qrcode/html5-qrcode-2.3.8.min.js') }}"></script>

<style>
/* =========================================================
   PRISM TOKENS + UTILITIES
   Inlined here so the scanner page is self-contained — the
   rest of Elsar5a.Team's layout doesn't ship the full Prism
   design system, so we keep these scoped to the scanner so
   nothing else on the site has to change.
   ========================================================= */
[data-scanner-root] {
    --prism-text:        #f1f5fb;
    --prism-text-2:      #c2cad8;
    --prism-text-3:      #8590a6;
    --prism-border:        rgba(255, 255, 255, 0.08);
    --prism-border-strong: rgba(255, 255, 255, 0.14);
    --prism-neon: linear-gradient(135deg, #22d3ee 0%, #818cf8 50%, #c084fc 100%);
    --prism-ease: cubic-bezier(.2,.7,.2,1);
}

[data-scanner-root] .prism-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--prism-border);
    font-size: 11px;
    color: var(--prism-text-2);
}
[data-scanner-root] .prism-pill-neon {
    background: linear-gradient(135deg, rgba(34,211,238,0.12), rgba(192,132,252,0.12));
    border-color: rgba(129,140,248,0.45);
    color: #e0e7ff;
    box-shadow: 0 0 14px rgba(129,140,248,0.18);
}

[data-scanner-root] .prism-dot { width: 8px; height: 8px; border-radius: 999px; display: inline-block; }
[data-scanner-root] .prism-dot-emerald { background: #34d399; box-shadow: 0 0 10px rgba(52,211,153,0.7); }

[data-scanner-root] .prism-headline {
    font-family: "Space Grotesk", "IBM Plex Sans Arabic", system-ui, sans-serif;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: var(--prism-text);
}

.prism-btn-ghost {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 999px;
    font-weight: 500;
    font-size: 13px;
    color: var(--prism-text-2, #c2cad8);
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--prism-border, rgba(255,255,255,0.08));
    transition: all .2s cubic-bezier(.2,.7,.2,1);
    min-height: 44px;
    cursor: pointer;
}
.prism-btn-ghost:hover {
    background: rgba(255,255,255,0.07);
    border-color: var(--prism-border-strong, rgba(255,255,255,0.14));
    color: var(--prism-text, #f1f5fb);
}

.prism-btn-gold {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 13px;
    color: #1b1208;
    background: linear-gradient(180deg, #fde68a, #f59e0b);
    border: 1px solid rgba(255,255,255,0.5);
    box-shadow: 0 8px 22px -6px rgba(245,158,11,0.55), inset 0 1px 0 rgba(255,255,255,0.55);
    transition: all .2s cubic-bezier(.2,.7,.2,1);
    min-height: 44px;
    cursor: pointer;
}
.prism-btn-gold:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px -6px rgba(245,158,11,0.7), 0 0 22px rgba(251,191,36,0.4), inset 0 1px 0 rgba(255,255,255,0.55);
}

/* Decorative back-arrow: in RTL the original glyph already points
   the right way; in LTR it gets flipped horizontally so it still
   reads as "back". */
.pt-arrow-rtl { display: inline-block; }
:root[dir="ltr"] .pt-arrow-rtl { transform: scaleX(-1); }

/* Subtle entrance animation so the scanner UI fades in instead of
   popping. Matches the Joseph Nabil cinematic feel. */
@keyframes prismFadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.prism-fade-up { animation: prismFadeUp .55s cubic-bezier(.2,.7,.2,1) both; }

/* =========================================================
   GATE SCANNER — premium chrome
   Scoped to [data-scanner-root] so the rest of the app's
   layout is untouched.
   ========================================================= */

[data-scanner-root] {
    --scan-shell-radius: 26px;
    --scan-ok:    #34d399;
    --scan-used:  #fbbf24;
    --scan-error: #fb7185;
    max-width: 28rem;
    margin: 0 auto;
    padding: 12px 12px 24px;
    padding-bottom: max(24px, env(safe-area-inset-bottom));
    display: grid;
    gap: 14px;
}

.scanner-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px;
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(20,24,38,0.62), rgba(8,10,20,0.72));
    border: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(18px) saturate(140%);
    -webkit-backdrop-filter: blur(18px) saturate(140%);
}

.scanner-stage {
    position: relative;
    overflow: hidden;
    border-radius: var(--scan-shell-radius);
    background: rgba(8,10,20,0.92);
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.04),
        0 24px 48px -22px rgba(0,0,0,0.85);
    aspect-ratio: 3 / 4;
    isolation: isolate;
}
@media (min-width: 480px) {
    .scanner-stage { aspect-ratio: 4 / 5; }
}

.scanner-video,
.scanner-video > video,
#qr-reader > video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    background: rgba(8,10,20,0.92);
    border-radius: var(--scan-shell-radius);
}
#qr-reader { width: 100%; height: 100%; }
/* Hide the default html5-qrcode UI (we render our own). */
#qr-reader__dashboard,
#qr-reader__header_message,
#qr-reader__camera_selection,
#qr-reader__scan_region img { display: none !important; }

.scanner-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 2;
}

.scanner-reticle {
    position: relative;
    width: min(72%, 280px);
    aspect-ratio: 1 / 1;
    border-radius: 22px;
}

.reticle-corner {
    --c-len: 28px;
    --c-thick: 3px;
    position: absolute;
    width: var(--c-len);
    height: var(--c-len);
    border-color: rgba(34,211,238,0.85);
    box-shadow: 0 0 18px rgba(34,211,238,0.4);
}
.reticle-corner.tl { top: 0; left: 0;  border-top:    var(--c-thick) solid; border-left:  var(--c-thick) solid; border-top-left-radius:  20px; }
.reticle-corner.tr { top: 0; right: 0; border-top:    var(--c-thick) solid; border-right: var(--c-thick) solid; border-top-right-radius: 20px; }
.reticle-corner.bl { bottom: 0; left: 0;  border-bottom: var(--c-thick) solid; border-left:  var(--c-thick) solid; border-bottom-left-radius:  20px; }
.reticle-corner.br { bottom: 0; right: 0; border-bottom: var(--c-thick) solid; border-right: var(--c-thick) solid; border-bottom-right-radius: 20px; }

.reticle-line {
    position: absolute;
    left: 6%;
    right: 6%;
    height: 2px;
    background: linear-gradient(90deg,
        transparent, #22d3ee 30%, #818cf8 50%, #c084fc 70%, transparent);
    box-shadow: 0 0 14px rgba(34,211,238,0.7);
    border-radius: 999px;
    animation: scanLine 1.6s cubic-bezier(.2,.7,.2,1) infinite;
}
@keyframes scanLine {
    0%,100% { top: 8%;  opacity: .55; }
    50%     { top: 88%; opacity: 1;   }
}

.scanner-status {
    position: absolute;
    top: 14px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 4;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    border: 1px solid rgba(255,255,255,0.14);
    background: rgba(8,10,20,0.85);
    color: var(--prism-text, #f1f5fb);
    backdrop-filter: blur(14px) saturate(140%);
    -webkit-backdrop-filter: blur(14px) saturate(140%);
    transition: background .2s, color .2s, transform .2s;
    box-shadow: 0 12px 28px -14px rgba(0,0,0,0.85);
}
.scanner-status.state-ready { color: #c2cad8; }
.scanner-status.state-scanning {
    background: rgba(34,211,238,0.92);
    color: #051923;
    border-color: rgba(165,243,252,0.7);
    box-shadow: 0 0 22px rgba(34,211,238,0.45);
}
.scanner-status.state-ok {
    background: rgba(16,185,129,0.92);
    color: #022c22;
    border-color: rgba(110,231,183,0.7);
    box-shadow: 0 0 22px rgba(52,211,153,0.55);
}
.scanner-status.state-used {
    background: rgba(251,191,36,0.92);
    color: #1b1208;
    border-color: rgba(254,240,138,0.7);
    box-shadow: 0 0 22px rgba(251,191,36,0.55);
}
.scanner-status.state-error {
    background: rgba(244,63,94,0.92);
    color: #fff1f2;
    border-color: rgba(253,164,175,0.7);
    box-shadow: 0 0 22px rgba(251,113,133,0.55);
}
.scanner-status.is-pop {
    transform: translateX(-50%) scale(1.08);
}

/* Stage edge glow per state — instantly visible from a distance. */
.scanner-stage[data-state="ok"]    { box-shadow: 0 0 0 2px rgba(52,211,153,0.55), 0 24px 48px -22px rgba(0,0,0,0.85); }
.scanner-stage[data-state="used"]  { box-shadow: 0 0 0 2px rgba(251,191,36,0.55), 0 24px 48px -22px rgba(0,0,0,0.85); }
.scanner-stage[data-state="error"] { box-shadow: 0 0 0 2px rgba(251,113,133,0.55), 0 24px 48px -22px rgba(0,0,0,0.85); animation: stageShake .35s cubic-bezier(.36,.07,.19,.97); }
@keyframes stageShake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px);  }
    30%, 50%, 70% { transform: translateX(-4px); }
    40%, 60% { transform: translateX(4px);  }
}

.scanner-loading {
    position: absolute;
    inset: 0;
    z-index: 3;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: var(--prism-text-2, #c2cad8);
    background: rgba(8,10,20,0.88);
    transition: opacity .2s ease;
}
.scanner-loading.is-hidden { opacity: 0; pointer-events: none; }
.prism-spinner {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 3px solid rgba(129,140,248,0.25);
    border-top-color: rgba(129,140,248,0.95);
    animation: spin 0.85s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.scanner-controls {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.scanner-controls .prism-btn-ghost.is-on {
    background: linear-gradient(135deg, rgba(251,191,36,0.18), rgba(251,191,36,0.04));
    border-color: rgba(253,224,71,0.45);
    color: #fef3c7;
}

/* Low-light flash-suggest pulse: only fires when the watchdog sees
   prolonged low ambient luminance AND the device exposes a torch
   AND the operator hasn't already turned the flash on. We never
   auto-toggle the torch — the visual suggest respects operator
   preference. */
.scanner-controls .prism-btn-ghost.is-suggest:not(.is-on) {
    animation: scanner-flash-suggest 1.4s ease-in-out infinite;
    border-color: rgba(253,224,71,0.55);
    color: #fef3c7;
}
@keyframes scanner-flash-suggest {
    0%, 100% { box-shadow: 0 0 0 0 rgba(253,224,71,0.45); }
    50%      { box-shadow: 0 0 0 6px rgba(253,224,71,0.00); }
}

/* Live zoom indicator chip, used by both pinch-to-zoom and the
   auto-zoom recovery logic so the operator knows the camera is
   currently zoomed in. */
.scan-zoom-chip {
    position: absolute;
    top: 12px;
    inset-inline-start: 12px;
    z-index: 5;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(2,4,12,0.55);
    -webkit-backdrop-filter: blur(6px);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.18);
    color: #fef3c7;
    font: 600 11px/1 ui-sans-serif, system-ui, sans-serif;
    letter-spacing: 0.02em;
    opacity: 0;
    pointer-events: none;
    transform: translateY(-4px);
    transition: opacity .18s ease, transform .18s ease;
}
.scan-zoom-chip.is-visible {
    opacity: 1;
    transform: translateY(0);
}

/* =========================================================
   PREMIUM RESULT SHEET (cinematic popup)
   ========================================================= */

.scan-sheet {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 16px;
    padding-bottom: max(16px, env(safe-area-inset-bottom));
    background: rgba(2,4,12,0.55);
    backdrop-filter: blur(8px) saturate(140%);
    -webkit-backdrop-filter: blur(8px) saturate(140%);
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s cubic-bezier(.2,.7,.2,1);
}
.scan-sheet[data-state="visible"] {
    opacity: 1;
    pointer-events: auto;
}
.scan-sheet-card {
    width: 100%;
    max-width: 28rem;
    padding: 18px;
    border-radius: 26px;
    background: linear-gradient(180deg, rgba(20,24,38,0.96), rgba(8,10,20,0.96));
    border: 1px solid rgba(129,140,248,0.32);
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.06),
        0 24px 48px -22px rgba(0,0,0,0.85),
        0 0 32px rgba(34,211,238,0.18);
    display: grid;
    gap: 10px;
    transform: translateY(28px) scale(.97);
    transition: transform .28s cubic-bezier(.2,.7,.2,1);
}
.scan-sheet[data-state="visible"] .scan-sheet-card {
    transform: translateY(0) scale(1);
}
.scan-sheet[data-result="ok"]    .scan-sheet-card { border-color: rgba(110,231,183,0.55); box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 24px 48px -22px rgba(0,0,0,0.85), 0 0 36px rgba(52,211,153,0.30); }
.scan-sheet[data-result="used"]  .scan-sheet-card { border-color: rgba(254,240,138,0.55); box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 24px 48px -22px rgba(0,0,0,0.85), 0 0 36px rgba(251,191,36,0.30); }
.scan-sheet[data-result="error"] .scan-sheet-card { border-color: rgba(253,164,175,0.55); box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 24px 48px -22px rgba(0,0,0,0.85), 0 0 36px rgba(251,113,133,0.30); }

.scan-sheet-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px 6px 8px;
    align-self: flex-start;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.08);
    font-weight: 800;
    font-size: 12px;
    letter-spacing: .06em;
    color: var(--prism-text, #f1f5fb);
}
.scan-sheet-badge-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    font-weight: 800;
    font-size: 14px;
}
.scan-sheet[data-result="ok"]    .scan-sheet-badge       { background: rgba(16,185,129,0.18); border-color: rgba(110,231,183,0.45); color: #d1fae5; }
.scan-sheet[data-result="ok"]    .scan-sheet-badge-icon  { background: rgba(52,211,153,0.32); color: #022c22; animation: badgePop .35s cubic-bezier(.2,.7,.2,1); }
.scan-sheet[data-result="used"]  .scan-sheet-badge       { background: rgba(251,191,36,0.18); border-color: rgba(254,240,138,0.45); color: #fef3c7; }
.scan-sheet[data-result="used"]  .scan-sheet-badge-icon  { background: rgba(251,191,36,0.32); color: #1b1208; }
.scan-sheet[data-result="error"] .scan-sheet-badge       { background: rgba(244,63,94,0.18); border-color: rgba(253,164,175,0.45); color: #ffe4e6; }
.scan-sheet[data-result="error"] .scan-sheet-badge-icon  { background: rgba(244,63,94,0.32); color: #fff1f2; }
@keyframes badgePop {
    0%   { transform: scale(.5); opacity: 0; }
    60%  { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(1); }
}

.scan-sheet-name {
    font-size: 18px;
    font-weight: 800;
    color: var(--prism-text, #f1f5fb);
    letter-spacing: .01em;
    line-height: 1.25;
}
.scan-sheet-ref {
    font-size: 11px;
    color: var(--prism-text-3, #8590a6);
    letter-spacing: .12em;
    text-transform: uppercase;
}
.scan-sheet-ref:empty { display: none; }

.scan-sheet-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 14px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    color: var(--prism-text-2, #c2cad8);
    font-size: 13px;
}
.scan-sheet-row-icon { font-size: 14px; opacity: .9; }
.scan-sheet-row-text { font-weight: 600; }

.scan-sheet-used-note {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 12px;
    background: rgba(251,191,36,0.10);
    border: 1px solid rgba(251,191,36,0.40);
    color: #fef3c7;
    font-size: 12px;
}

.scan-sheet-foot {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 6px;
    padding-top: 4px;
}
.scan-sheet-done {
    width: 100%;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: .04em;
}
.scan-sheet-hint {
    font-size: 11px;
    color: var(--prism-text-3, #8590a6);
    letter-spacing: .12em;
    text-transform: uppercase;
    text-align: center;
}

/* First-scan success is intentionally calm: no warning chrome, no
   instructional dismiss-hint. The big green ✓ badge + name + show
   is the whole story. Belt-and-braces so an OK first-scan can
   NEVER surface duplicate-scan chrome. */
.scan-sheet[data-result="ok"] .scan-sheet-used-note { display: none !important; }
.scan-sheet[data-result="ok"] .scan-sheet-hint      { display: none !important; }

@media (prefers-reduced-motion: reduce) {
    .reticle-line,
    .scan-sheet-card,
    .scanner-status { animation: none !important; transition: none !important; }
    .scanner-stage[data-state="error"] { animation: none !important; }
}
</style>

<script>
(() => {
    'use strict';

    /* ============================================================
       Scanner config
       ============================================================ */
    // Cooldown = how long before the SAME code can be re-scanned
    // (a small guard so a slowly-moving QR isn't pinged twice in a
    // row). The sheet-open gate plus the busy gate already prevent
    // double-pings, so 700ms is enough to dedupe a slowly-moving QR
    // without making the same operator wait on a deliberate re-scan.
    const COOLDOWN_MS = 700;

    let busy = false;          // mid-flight backend round-trip
    let lastCode = null;
    let lastScanTime = 0;
    let sheetOpen = false;     // pause scans while showing a result
    let qrInstance = null;     // assigned once a decode path boots

    /* ============================================================
       Audio + haptic feedback

       Two-stage feedback pattern that modern scanners (iOS Camera,
       Google Lens, professional event-entry scanners) use:

         Stage 1 — pre-confirmation. Fires the INSTANT a QR decodes
         locally, BEFORE the backend POST resolves. A short 40ms
         buzz + a soft, quiet 'tick' so the operator gets physical
         proof that a QR was seen. This is what makes the scanner
         FEEL instant — the 100–400ms backend round-trip becomes
         invisible because the operator already got physical
         confirmation.

         Stage 2 — final confirmation. Fires when the server
         responds with ok / used / error. The long buzz + the loud
         tonal beep + the result-sheet slide-up.

       AudioContext prewarm: iOS Safari requires a user gesture
       before audio works. We resume() the context on the first
       touch/click anywhere on the page, so the first scan's beep
       isn't silent.
       ============================================================ */
    let audioCtx = null;
    let audioPrimed = false;

    function ensureAudio() {
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended' && typeof audioCtx.resume === 'function') {
                audioCtx.resume().catch(() => {});
            }
        } catch (_) {}
    }

    function primeAudio() {
        if (audioPrimed) return;
        audioPrimed = true;
        ensureAudio();
    }
    ['touchstart', 'pointerdown', 'click', 'keydown'].forEach((ev) => {
        document.addEventListener(ev, primeAudio, { once: true, passive: true });
    });

    function beep(type) {
        try {
            ensureAudio();
            if (!audioCtx) return;
            const osc  = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.frequency.value = type === 'ok' ? 950 : type === 'used' ? 500 : 250;
            gain.gain.value = 0.22;
            osc.start();
            setTimeout(() => osc.stop(), 150);
        } catch (_) {}
    }

    // Soft 'tick' — quieter and shorter than the result beep.
    // Played at decode-time as Stage 1 confirmation. Sharp attack +
    // sharp release so it reads as a click, not a tone.
    function softTick() {
        try {
            ensureAudio();
            if (!audioCtx) return;
            const osc  = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type            = 'square';
            osc.frequency.value = 1500;
            const now = audioCtx.currentTime;
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.10, now + 0.005);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.045);
            osc.start(now);
            osc.stop(now + 0.05);
        } catch (_) {}
    }

    function vibrate(type) {
        if (!('vibrate' in navigator)) return;
        if      (type === 'precheck') navigator.vibrate(40);
        else if (type === 'ok')       navigator.vibrate(120);
        else if (type === 'used')     navigator.vibrate([100, 50, 100]);
        else                          navigator.vibrate(220);
    }

    /* ============================================================
       Status pill
       ============================================================ */
    const $status  = document.getElementById('status');
    const $stage   = document.querySelector('[data-scanner-stage]');
    const $loading = document.getElementById('scanner-loading');
    function setStatus(text, type) {
        $status.textContent = text;
        $status.classList.remove('state-ready', 'state-scanning', 'state-ok', 'state-used', 'state-error', 'is-pop');
        $status.classList.add('state-' + (type || 'ready'), 'is-pop');
        setTimeout(() => $status.classList.remove('is-pop'), 200);
        if (type === 'ok' || type === 'used' || type === 'error') {
            $stage.dataset.state = type;
        } else {
            delete $stage.dataset.state;
        }
    }

    /* ============================================================
       Result sheet (cinematic popup modal)
       ============================================================ */
    const $sheet         = document.getElementById('scan-sheet');
    const $sheetBadge    = $sheet.querySelector('[data-scan-badge-text]');
    const $sheetIcon     = $sheet.querySelector('[data-scan-icon]');
    const $sheetName     = $sheet.querySelector('[data-scan-name]');
    const $sheetRef      = $sheet.querySelector('[data-scan-ref]');
    const $sheetShow     = $sheet.querySelector('[data-scan-show]');
    const $sheetWhen     = $sheet.querySelector('[data-scan-when]');
    const $sheetUsedNote = $sheet.querySelector('[data-scan-used-note]');
    const $sheetUsedTime = $sheet.querySelector('[data-scan-used-time]');
    const $sheetDismiss  = $sheet.querySelector('[data-scan-dismiss]');

    function showSheet(result, payload) {
        $sheet.dataset.result = result; // ok | used | error
        $sheet.dataset.state  = 'visible';
        sheetOpen = true;

        // Badge text + icon — strip any leading emoji so the badge
        // box (which already has an icon slot) doesn't double-stamp it.
        const badgeText =
            result === 'ok'   ? '✅ دخول مسموح' :
            result === 'used' ? '⚠️ مستخدمة'   :
                                '❌ غير صالح';
        $sheetBadge.textContent = badgeText.replace(/^[^\p{L}\p{N}]+\s*/u, '');
        $sheetIcon.textContent  = result === 'ok' ? '✓' : result === 'used' ? '!' : '✕';

        const p = payload || {};

        // Attendee
        $sheetName.textContent = p.name || '—';
        $sheetRef.textContent  = p.reference ? '#' + p.reference : '';

        // Show + when
        $sheetShow.textContent = p.show_title || '—';
        const dateStr = p.date || '';
        const timeStr = p.time || '';
        $sheetWhen.textContent = [dateStr, timeStr].filter(Boolean).join(' · ') || '—';

        // Used note
        if (result === 'used' && p.scanned_at) {
            $sheetUsedNote.hidden = false;
            $sheetUsedTime.textContent = ' · ' + p.scanned_at;
        } else {
            $sheetUsedNote.hidden = true;
            $sheetUsedTime.textContent = '';
        }

        // Pause scanning while the sheet is up so we don't waste
        // CPU re-decoding the same QR the operator is reviewing.
        if (qrInstance) {
            try { qrInstance.pause(true); } catch (_) {}
        }
    }

    function hideSheet() {
        $sheet.dataset.state = 'hidden';
        sheetOpen = false;
        delete $stage.dataset.state;
        setStatus('جاهز للفحص', 'ready');
        // Clear the lastCode lock so scanning the same QR again
        // (e.g. an already-used ticket the operator wants to re-check)
        // doesn't get silently swallowed by the cooldown.
        lastCode = null;
        lastScanTime = 0;
        if (qrInstance) {
            try { qrInstance.resume(); } catch (_) {}
        }
    }
    $sheetDismiss.addEventListener('click', hideSheet);
    $sheet.addEventListener('click', (e) => {
        if (e.target === $sheet) hideSheet();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sheetOpen) hideSheet();
    });

    /* ============================================================
       Backend round-trip
       ============================================================ */
    function check(code) {
        fetch('{{ route('admin.scanner.check') }}', {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept':       'application/json',
            },
            body: JSON.stringify({ code }),
        })
        .then((r) => r.json())
        .then((d) => {
            if (d.status === 'ok') {
                setStatus('✅ دخول مسموح', 'ok');
                vibrate('ok');  beep('ok');
                showSheet('ok', d);
            } else if (d.status === 'used') {
                setStatus('⚠️ مستخدمة', 'used');
                vibrate('used'); beep('used');
                showSheet('used', d);
            } else {
                setStatus('❌ غير صالح', 'error');
                vibrate('error'); beep('error');
                showSheet('error', d || {});
            }
        })
        .catch(() => {
            setStatus('⚠️ تعذّر الاتصال', 'error');
            vibrate('error');
        })
        .finally(() => {
            setTimeout(() => { busy = false; }, 250);
        });
    }

    /* ============================================================
       Shared scan-success funnel

       Every decode engine (Path A native BarcodeDetector,
       Path B ZXing, Path C html5-qrcode) calls this when it
       finds a QR. Gates dedupe / sheet-open / busy state and
       triggers Stage-1 pre-confirmation feedback (tick + short
       buzz) before kicking the backend POST.
       ============================================================ */
    function onScanSuccess(text) {
        if (sheetOpen) return;
        const now = Date.now();
        if (text === lastCode && now - lastScanTime < COOLDOWN_MS) return;
        if (busy) return;
        busy = true;
        lastCode = text;
        lastScanTime = now;

        // Stage 1 — pre-confirmation. INSTANT, local-only feedback
        // so the operator gets physical proof that a QR was seen
        // before the backend has had time to respond.
        vibrate('precheck');
        softTick();

        setStatus('⏳ جارٍ التحقق', 'scanning');
        check(text);
    }

    /* ============================================================
       Capability cache + zoom / torch / pinch
       ============================================================ */
    let activeTrack = null;
    const capability = {
        torch:    false,
        zoom:     false,
        zoomMin:  1,
        zoomMax:  1,
        zoomStep: 0.1,
    };
    let zoomCurrent     = 1;
    let zoomBaseAtTouch = 1;
    let pinchStartDist  = 0;
    let lastLuminance   = 255;
    let lowLightTimer   = null;
    let pinchAttachedTo = null;

    const $zoomChip      = document.getElementById('scan-zoom-chip');
    const $zoomChipText  = $zoomChip ? $zoomChip.querySelector('[data-zoom-text]') : null;

    function setZoomChip(level) {
        if (!$zoomChip || !$zoomChipText) return;
        if (level > 1.05) {
            $zoomChipText.textContent = level.toFixed(1) + '×';
            $zoomChip.classList.add('is-visible');
        } else {
            $zoomChip.classList.remove('is-visible');
        }
    }

    function updateCapabilities(track) {
        try {
            if (!track || typeof track.getCapabilities !== 'function') return;
            const caps = track.getCapabilities();
            capability.torch = !!('torch' in caps);
            if (caps.zoom) {
                if (typeof caps.zoom === 'object') {
                    capability.zoom     = true;
                    capability.zoomMin  = caps.zoom.min  || 1;
                    capability.zoomMax  = caps.zoom.max  || 1;
                    capability.zoomStep = caps.zoom.step || 0.1;
                } else if (typeof caps.zoom === 'number') {
                    capability.zoom    = true;
                    capability.zoomMin = 1;
                    capability.zoomMax = caps.zoom;
                }
            }
            if (capability.zoomMax <= capability.zoomMin) {
                capability.zoom = false;
            }
        } catch (_) {}
    }

    async function setZoom(level) {
        if (!capability.zoom || !activeTrack ||
            typeof activeTrack.applyConstraints !== 'function') {
            return false;
        }
        const clamped = Math.max(
            capability.zoomMin,
            Math.min(capability.zoomMax, level || 1)
        );
        try {
            await activeTrack.applyConstraints({ advanced: [{ zoom: clamped }] });
            zoomCurrent = clamped;
            setZoomChip(clamped);
            return true;
        } catch (_) {
            return false;
        }
    }

    function touchDistance(touches) {
        if (!touches || touches.length < 2) return 0;
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.hypot(dx, dy);
    }

    function attachPinchZoom(host) {
        if (!host || !capability.zoom) return;
        if (pinchAttachedTo === host) return;
        pinchAttachedTo = host;

        host.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                pinchStartDist  = touchDistance(e.touches);
                zoomBaseAtTouch = zoomCurrent;
            }
        }, { passive: true });

        host.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2 && pinchStartDist > 0) {
                try { e.preventDefault(); } catch (_) {}
                const newDist = touchDistance(e.touches);
                if (newDist > 0) {
                    const scale = newDist / pinchStartDist;
                    setZoom(zoomBaseAtTouch * scale);
                }
            }
        }, { passive: false });

        host.addEventListener('touchend', () => {
            pinchStartDist  = 0;
            zoomBaseAtTouch = zoomCurrent;
        }, { passive: true });

        host.addEventListener('touchcancel', () => {
            pinchStartDist  = 0;
            zoomBaseAtTouch = zoomCurrent;
        }, { passive: true });
    }

    // Low-light watchdog. Pulses the flash button when ambient
    // luminance has been dim for a while AND the device has a
    // torch AND the operator hasn't already turned it on. We
    // NEVER auto-toggle the torch — silently flipping a strobe
    // on the operator is exactly the wrong UX.
    function startLowLightWatchdog() {
        if (lowLightTimer) return;
        lowLightTimer = setInterval(() => {
            try {
                const $btn = document.getElementById('flashBtn');
                if (!$btn) return;
                if (!capability.torch || flashOn) {
                    $btn.classList.remove('is-suggest');
                    return;
                }
                if (lastLuminance < 60) {
                    $btn.classList.add('is-suggest');
                } else {
                    $btn.classList.remove('is-suggest');
                }
            } catch (_) {}
        }, 1000);
    }

    function setupCapabilityFeatures(track, hostEl) {
        if (!track) return;
        updateCapabilities(track);
        if (capability.zoom && hostEl) attachPinchZoom(hostEl);
        startLowLightWatchdog();
    }

    /* ============================================================
       Luminance + image-preprocessing helpers
       Shared by Path B's main decode pass and the miss-recovery
       passes (adaptive threshold / invert / center-ROI 2× upscale).
       ============================================================ */
    function packLuminance(rgbaData) {
        const len = rgbaData.length >>> 2;
        const out = new Uint8ClampedArray(len);
        for (let i = 0, j = 0; i < rgbaData.length; i += 4, j++) {
            out[j] = (
                rgbaData[i]     * 0.299 +
                rgbaData[i + 1] * 0.587 +
                rgbaData[i + 2] * 0.114
            ) | 0;
        }
        return out;
    }

    function meanLuminance(lum) {
        if (!lum || lum.length === 0) return 255;
        let sum = 0, count = 0;
        for (let i = 0; i < lum.length; i += 16) {
            sum += lum[i];
            count++;
        }
        return count > 0 ? (sum / count) | 0 : 255;
    }

    function thresholdLuminance(lum, mean) {
        const t = mean;
        const out = new Uint8ClampedArray(lum.length);
        for (let i = 0; i < lum.length; i++) {
            out[i] = lum[i] >= t ? 255 : 0;
        }
        return out;
    }

    function invertLuminance(lum) {
        const out = new Uint8ClampedArray(lum.length);
        for (let i = 0; i < lum.length; i++) {
            out[i] = 255 - lum[i];
        }
        return out;
    }

    function centerRoiUpscale(lum, w, h) {
        const x0 = w >> 2;
        const y0 = h >> 2;
        const rw = w >> 1;
        const rh = h >> 1;
        const ow = w;
        const oh = h;
        const out = new Uint8ClampedArray(ow * oh);
        const sx = rw / ow;
        const sy = rh / oh;
        const xLast = x0 + rw - 1;
        const yLast = y0 + rh - 1;
        for (let y = 0; y < oh; y++) {
            const fy = y * sy + y0;
            const iy = fy | 0;
            const ay = fy - iy;
            const iyn = iy + 1 < yLast ? iy + 1 : yLast;
            const rowA = iy  * w;
            const rowB = iyn * w;
            const outRow = y * ow;
            for (let x = 0; x < ow; x++) {
                const fx = x * sx + x0;
                const ix = fx | 0;
                const ax = fx - ix;
                const ixn = ix + 1 < xLast ? ix + 1 : xLast;
                const p00 = lum[rowA + ix ];
                const p10 = lum[rowA + ixn];
                const p01 = lum[rowB + ix ];
                const p11 = lum[rowB + ixn];
                const top = p00 + (p10 - p00) * ax;
                const bot = p01 + (p11 - p01) * ax;
                out[outRow + x] = (top + (bot - top) * ay) | 0;
            }
        }
        return out;
    }

    function tryZXingDecode(lum, w, h, mfr, hints) {
        if (!lum || !mfr) return null;
        let lumSource;
        try {
            lumSource = new ZXing.RGBLuminanceSource(lum, w, h);
        } catch (_) {
            try { lumSource = new ZXing.RGBLuminanceSource(w, h, lum); }
            catch (__) { return null; }
        }
        let result = null;
        try {
            const binarizer = new ZXing.HybridBinarizer(lumSource);
            const bitmap    = new ZXing.BinaryBitmap(binarizer);
            result = mfr.decode(bitmap, hints || undefined);
        } catch (_) {
            result = null;
        } finally {
            try { mfr.reset(); } catch (_) {}
        }
        return result;
    }

    /**
     * Stop every track on a stream and null out activeTrack if it
     * matches. Used by every null-return path so we never leak a
     * live camera handle into the next bootstrap path.
     */
    function releaseStream(stream) {
        if (!stream) return;
        try {
            stream.getTracks().forEach((t) => {
                try { t.stop(); } catch (_) {}
                if (activeTrack === t) activeTrack = null;
            });
        } catch (_) {}
    }

    /* ============================================================
       Path A — Direct BarcodeDetector (Google-Lens-tier reliability)

       When the browser exposes the native BarcodeDetector API
       (Android Chrome + Edge, some Chromium-based mobile browsers,
       and recent iOS Safari builds), we bypass html5-qrcode entirely
       and run a tight requestVideoFrameCallback loop straight on the
       <video> element.
       ============================================================ */
    async function startNativeBarcodeDetector() {
        if (!('BarcodeDetector' in window)) return null;
        let supported;
        try { supported = await BarcodeDetector.getSupportedFormats(); }
        catch (_) { return null; }
        if (!supported || !supported.includes('qr_code')) return null;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return null;

        let stream = null;
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width:     { ideal: 1920 },
                    height:    { ideal: 1080 },
                    frameRate: { ideal: 60 },
                },
                audio: false,
            });

            const track = stream.getVideoTracks()[0];
            if (!track) {
                releaseStream(stream);
                return null;
            }
            activeTrack = track;

            const reader = document.getElementById('qr-reader');
            reader.innerHTML = '';
            const video = document.createElement('video');
            video.setAttribute('playsinline', 'true');
            video.setAttribute('webkit-playsinline', 'true');
            video.setAttribute('autoplay', 'true');
            video.setAttribute('muted', 'true');
            video.muted    = true;
            video.autoplay = true;
            Object.assign(video.style, {
                width:     '100%',
                height:    '100%',
                objectFit: 'cover',
                display:   'block',
            });
            reader.appendChild(video);
            video.srcObject = stream;
            try { await video.play(); } catch (_) {}

            try {
                await track.applyConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                        { focusMode: 'continuous-picture' },
                        { focusDistance: { ideal: 0.05 } },
                        { exposureMode: 'continuous' },
                        { whiteBalanceMode: 'continuous' },
                    ],
                });
            } catch (_) {}

            try { setupCapabilityFeatures(track, video); } catch (_) {}

            let detector;
            try {
                detector = new BarcodeDetector({ formats: ['qr_code'] });
            } catch (_) {
                releaseStream(stream);
                return null;
            }

            let stopped = false;
            let paused  = false;

            const schedule = (fn) => {
                if (typeof video.requestVideoFrameCallback === 'function') {
                    video.requestVideoFrameCallback(() => fn());
                } else {
                    requestAnimationFrame(fn);
                }
            };

            const tick = async () => {
                if (stopped) return;
                if (paused || sheetOpen || video.readyState < 2) {
                    schedule(tick);
                    return;
                }
                try {
                    const codes = await detector.detect(video);
                    if (codes && codes.length) {
                        let best = codes[0];
                        if (codes.length > 1) {
                            const area = (b) => (b && b.width && b.height) ? b.width * b.height : 0;
                            for (const c of codes) {
                                if (area(c.boundingBox) > area(best.boundingBox)) best = c;
                            }
                        }
                        if (best && best.rawValue) onScanSuccess(best.rawValue);
                    }
                } catch (_) { /* per-frame errors are transient */ }
                schedule(tick);
            };
            schedule(tick);

            return {
                pause:  () => { paused  = true; },
                resume: () => { paused  = false; },
                stop:   () => {
                    stopped = true;
                    releaseStream(stream);
                },
                track,
            };
        } catch (_) {
            releaseStream(stream);
            return null;
        }
    }

    /* ============================================================
       Path B — ZXing-js driven by a MANUAL frame loop
       (the iPhone-Safari path)

       iOS Safari does not expose BarcodeDetector in production
       builds. We own the <video> mount (same DOM-attach-before-
       srcObject sequence as Path A so Safari doesn't drop the
       stream), draw each frame to a canvas, and hand it to
       ZXing.MultiFormatReader.decode() with TRY_HARDER +
       ALSO_INVERTED hints.

       Because we never await media event states, the hang seen
       in early ZXing integrations is structurally impossible.
       ============================================================ */
    async function startZXing() {
        if (typeof ZXing === 'undefined') return null;
        if (!ZXing.MultiFormatReader || !ZXing.BinaryBitmap ||
            !ZXing.HybridBinarizer || !ZXing.RGBLuminanceSource) return null;
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return null;

        let stream = null;

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width:     { ideal: 1920 },
                    height:    { ideal: 1080 },
                    frameRate: { ideal: 30 },
                },
                audio: false,
            });

            const track = stream.getVideoTracks()[0];
            if (!track) {
                releaseStream(stream);
                return null;
            }
            activeTrack = track;

            const reader = document.getElementById('qr-reader');
            reader.innerHTML = '';
            const video = document.createElement('video');
            video.setAttribute('playsinline', 'true');
            video.setAttribute('webkit-playsinline', 'true');
            video.setAttribute('autoplay', 'true');
            video.setAttribute('muted', 'true');
            video.muted    = true;
            video.autoplay = true;
            Object.assign(video.style, {
                width:     '100%',
                height:    '100%',
                objectFit: 'cover',
                display:   'block',
            });
            reader.appendChild(video);
            video.srcObject = stream;
            try { await video.play(); } catch (_) {}

            try {
                await track.applyConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                        { focusMode: 'continuous-picture' },
                        { focusDistance: { ideal: 0.05 } },
                        { exposureMode: 'continuous' },
                        { whiteBalanceMode: 'continuous' },
                    ],
                });
            } catch (_) {}

            let hints = null;
            try {
                hints = new Map();
                if (ZXing.DecodeHintType && ZXing.BarcodeFormat) {
                    hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [ZXing.BarcodeFormat.QR_CODE]);
                    hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
                    if ('ALSO_INVERTED' in ZXing.DecodeHintType) {
                        hints.set(ZXing.DecodeHintType.ALSO_INVERTED, true);
                    }
                }
            } catch (_) { hints = null; }

            let mfr;
            try {
                mfr = new ZXing.MultiFormatReader();
                if (hints) mfr.setHints(hints);
            } catch (_) {
                releaseStream(stream);
                return null;
            }

            const canvas = document.createElement('canvas');
            const ctx    = canvas.getContext('2d', { willReadFrequently: true });

            let stopped = false;
            let paused  = false;

            const schedule = (fn) => {
                if (typeof video.requestVideoFrameCallback === 'function') {
                    video.requestVideoFrameCallback(() => fn());
                } else {
                    requestAnimationFrame(fn);
                }
            };

            try { setupCapabilityFeatures(track, video); } catch (_) {}

            let lastSuccessAt   = Date.now();
            let lastLumSampleAt = 0;
            let autoZoomActive  = false;

            const tick = () => {
                if (stopped) return;
                if (paused || sheetOpen || video.readyState < 2) {
                    schedule(tick);
                    return;
                }

                try {
                    const vw = video.videoWidth | 0;
                    const vh = video.videoHeight | 0;
                    if (vw > 0 && vh > 0) {
                        const cap = 1280;
                        let cw = vw, ch = vh;
                        if (Math.max(vw, vh) > cap) {
                            const scale = cap / Math.max(vw, vh);
                            cw = Math.round(vw * scale);
                            ch = Math.round(vh * scale);
                        }
                        if (canvas.width !== cw || canvas.height !== ch) {
                            canvas.width  = cw;
                            canvas.height = ch;
                        }
                        ctx.drawImage(video, 0, 0, cw, ch);
                        const img = ctx.getImageData(0, 0, cw, ch);
                        const luminances = packLuminance(img.data);

                        const now    = Date.now();
                        const missMs = now - lastSuccessAt;

                        // Pass 1 — fast normal decode.
                        let result = tryZXingDecode(luminances, cw, ch, mfr, hints);

                        // Sample mean luminance once every ~750ms for
                        // the low-light watchdog.
                        if (now - lastLumSampleAt > 750) {
                            lastLuminance   = meanLuminance(luminances);
                            lastLumSampleAt = now;
                        }

                        // Pass 2 — adaptive threshold (after >=250ms misses).
                        if (!result && missMs > 250) {
                            const mean = meanLuminance(luminances);
                            const thr  = thresholdLuminance(luminances, mean);
                            result = tryZXingDecode(thr, cw, ch, mfr, hints);
                        }

                        // Pass 3 — inverted (after >=400ms misses).
                        if (!result && missMs > 400) {
                            const inv = invertLuminance(luminances);
                            result = tryZXingDecode(inv, cw, ch, mfr, hints);
                        }

                        // Pass 4 — center ROI 2× upscale (after >=600ms misses).
                        if (!result && missMs > 600) {
                            const up = centerRoiUpscale(luminances, cw, ch);
                            result = tryZXingDecode(up, cw, ch, mfr, hints);
                        }

                        // Auto-zoom recovery — after >=1000ms misses AND
                        // track exposes a usable zoom capability AND
                        // we're not already mid-bump, try a 2× zoom for
                        // ~1.6s, then revert if still no decode.
                        if (!result && capability.zoom && !autoZoomActive &&
                            missMs > 1000 && Math.abs(zoomCurrent - 1) < 0.05) {
                            autoZoomActive = true;
                            const target = Math.min(2, capability.zoomMax);
                            setZoom(target).then((ok) => {
                                if (!ok) {
                                    autoZoomActive = false;
                                    return;
                                }
                                setTimeout(() => {
                                    autoZoomActive = false;
                                    if (Date.now() - lastSuccessAt > 800) {
                                        setZoom(capability.zoomMin);
                                    }
                                }, 1600);
                            });
                        }

                        if (result) {
                            let text = '';
                            try { text = (typeof result.getText === 'function') ? result.getText() : (result.text || ''); }
                            catch (_) { text = ''; }
                            if (text) {
                                lastSuccessAt = now;
                                onScanSuccess(text);
                            }
                        }
                    }
                } catch (_) { /* per-frame errors are transient */ }

                schedule(tick);
            };
            schedule(tick);

            return {
                pause:  () => { paused  = true; },
                resume: () => { paused  = false; },
                stop:   () => {
                    stopped = true;
                    releaseStream(stream);
                },
                track,
            };
        } catch (_) {
            releaseStream(stream);
            return null;
        }
    }

    /* ============================================================
       Path C — html5-qrcode last-resort fallback (jsQR)
       ============================================================ */
    function startHtml5QrcodeFallback() {
        const qr = new Html5Qrcode('qr-reader', {
            verbose: false,
            experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            formatsToSupport: (window.Html5QrcodeSupportedFormats && [
                Html5QrcodeSupportedFormats.QR_CODE,
            ]) || undefined,
        });

        const qrbox = (vw, vh) => {
            const side = Math.floor(Math.min(vw, vh) * 0.95);
            return {
                width:  Math.max(240, Math.min(side, 600)),
                height: Math.max(240, Math.min(side, 600)),
            };
        };

        const config = {
            fps: 30,
            qrbox: qrbox,
            aspectRatio: 1.0,
            disableFlip: false,
            rememberLastUsedCamera: true,
            videoConstraints: {
                facingMode: { ideal: 'environment' },
                width:      { ideal: 1920 },
                height:     { ideal: 1080 },
                frameRate:  { ideal: 30 },
                focusMode:        'continuous',
                exposureMode:     'continuous',
                whiteBalanceMode: 'continuous',
                advanced: [
                    { focusMode: 'continuous' },
                    { focusMode: 'continuous-picture' },
                    { focusDistance: { ideal: 0.05 } },
                    { exposureMode: 'continuous' },
                    { whiteBalanceMode: 'continuous' },
                ],
            },
        };

        return qr.start(
            { facingMode: 'environment' },
            config,
            onScanSuccess,
            /* onScanFailure */ () => {}
        ).then(() => {
            qrInstance = qr;
            $loading.classList.add('is-hidden');
            try {
                const v = document.querySelector('#qr-reader video');
                if (v) {
                    v.setAttribute('playsinline', 'true');
                    v.setAttribute('webkit-playsinline', 'true');
                    v.muted = true;
                    try {
                        const s = v.srcObject;
                        if (s && s.getVideoTracks) {
                            activeTrack = s.getVideoTracks()[0] || null;
                        }
                    } catch (_) {}
                    try { setupCapabilityFeatures(activeTrack, v); } catch (_) {}
                }
            } catch (_) {}
            try {
                qr.applyVideoConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                        { focusMode: 'continuous-picture' },
                        { exposureMode: 'continuous' },
                        { whiteBalanceMode: 'continuous' },
                    ],
                }).catch(() => {});
            } catch (_) {}
            return qr;
        });
    }

    /* ============================================================
       Bootstrap — Path A → Path B → Path C
       Each path returns either a controls object (`{pause, resume,
       stop, track}`) or null. The bootstrap walks them in priority
       order and keeps the first non-null result. A 6s timeout
       force-dismisses the loading overlay if NO path called us
       back, so the operator never sees a stuck spinner.
       ============================================================ */
    let scannerStarted = false;

    function markScannerReady() {
        scannerStarted = true;
        $loading.classList.add('is-hidden');
        setStatus('جاهز للفحص', 'ready');
    }

    function markScannerFailed() {
        if (scannerStarted) return;
        scannerStarted = true;
        $loading.classList.add('is-hidden');
        setStatus('⚠️ تعذّر تشغيل الكاميرا', 'error');
    }

    const BOOTSTRAP_TIMEOUT_MS = 6000;
    const bootstrapTimeoutId = setTimeout(markScannerFailed, BOOTSTRAP_TIMEOUT_MS);

    window.addEventListener('error', markScannerFailed);
    window.addEventListener('unhandledrejection', markScannerFailed);

    (async () => {
        try {
            const native = await startNativeBarcodeDetector();
            if (native) {
                qrInstance = native;
                clearTimeout(bootstrapTimeoutId);
                markScannerReady();
                return;
            }
        } catch (_) { /* fall through */ }

        try {
            const zx = await startZXing();
            if (zx) {
                qrInstance = zx;
                clearTimeout(bootstrapTimeoutId);
                markScannerReady();
                return;
            }
        } catch (_) { /* fall through */ }

        try {
            await startHtml5QrcodeFallback();
            clearTimeout(bootstrapTimeoutId);
            scannerStarted = true;
        } catch (_) {
            clearTimeout(bootstrapTimeoutId);
            markScannerFailed();
        }
    })();

    /* ============================================================
       Flash / restart controls
       ============================================================ */
    let flashOn = false;
    const $flashBtn = document.getElementById('flashBtn');
    $flashBtn.addEventListener('click', async () => {
        try {
            if (!activeTrack || !activeTrack.applyConstraints) {
                alert('الفلاش غير مدعوم');
                return;
            }
            const caps = activeTrack.getCapabilities ? activeTrack.getCapabilities() : {};
            if (!('torch' in caps)) {
                alert('الفلاش غير مدعوم');
                return;
            }
            flashOn = !flashOn;
            await activeTrack.applyConstraints({ advanced: [{ torch: flashOn }] });
            $flashBtn.classList.toggle('is-on', flashOn);
            $flashBtn.classList.remove('is-suggest');
        } catch (_) {
            alert('الفلاش غير مدعوم');
            flashOn = false;
            $flashBtn.classList.remove('is-on');
            $flashBtn.classList.remove('is-suggest');
        }
    });
    document.getElementById('restartBtn').addEventListener('click', () => location.reload());
})();
</script>
@endsection
