@extends('layouts.app')

@section('title', 'حجز تذاكر - ' . $showTime->show->title)

@section('content')
{{--
    Public booking flow — mobile-first, cinematic refresh.

    JS / submission contract (PR #9) preserved verbatim:
      - Form id="bookingForm", novalidate, multipart/form-data.
      - Hidden inputs name="idempotency_token" (id=idempotencyToken)
        and name="tickets_count" (id=tickets_count) keep their
        exact names + ids — the server validates them, the client
        regenerates the idempotency token per page-load via
        sessionStorage["booking_token_show_{id}"].
      - Counter UI: ids #ticketsCount, #maxHint, #totalPriceHint
        and window.changeCount(±1) (called inline from the +/−
        buttons) are unchanged.
      - Names/phones inputs are rendered into #namesContainer by
        the inline script — input attrs (name=names[]|phones[],
        required, autocomplete, inputmode, pattern) are unchanged
        so the server validator still matches.
      - Screenshot dropzone: ids #screenshot, #screenshotDropzone,
        #screenshotEmptyState, #screenshotPreviewWrap,
        #screenshotPreview, #screenshotFileName, #screenshotError
        with the same hidden/show toggling.
      - Submit: id="submitBtn" (with the original gray→amber
        class swap classes preserved so recomputeSubmitState()
        still flips colours), id="submitLabel", id="submitHint".
        The submit button itself sits inside a single
        [data-sticky-action] wrapper so the layout's sticky CTA
        bootstrapper clones it into the floating footer — the
        hint stays OUTSIDE that wrapper so it never duplicates.
      - Tap-to-copy on [data-copy] payment buttons with
        .copy-hint child — unchanged.
      - bfcache reset on pageshow — unchanged.
      - Server-side: BookingController::store uses Cache::add
        on both the idempotency token AND a per-showtime lock,
        with FOR UPDATE + aggregate capacity recheck inside the
        transaction (PR #9). No controller / route change.

    What's new (presentation only):
      - Step rail at the top (Transfer · Fill · Confirm).
      - Cinematic aside card with gradient show title and a
        polished step-1 transfer panel.
      - Ticket counter with a subtle "stage glow" pulse on +/−.
      - Person cards with index pill + soft amber accent.
      - Screenshot dropzone with a clearer drag-and-tap CTA.
      - Trust strip under the form (secure / fast / QR).
      - iOS keyboard: html `scroll-padding-bottom` so the sticky
        CTA never covers the focused input.
      - prefers-reduced-motion honoured throughout.
--}}

<style>
[data-booking] {
    --bk-radius:   1.5rem;
    --bk-radius-lg:2rem;
    --bk-border:   rgba(255,255,255,0.10);
    --bk-text:     #f1f5fb;
    --bk-text-2:   rgba(229,231,235,0.82);
    --bk-text-3:   rgba(229,231,235,0.55);
    --bk-amber:    #fbbf24;
    --bk-ease:     cubic-bezier(.2,.7,.2,1);
}

/* iOS Safari: when an input is focused the OS keyboard slides
   up. Without this, the sticky CTA can cover the focused field. */
html { scroll-padding-bottom: 140px; }

/* ============================================================
   PROGRESS RAIL — three steps at the top of the page so the
   user immediately understands the flow shape. */
.bk-rail {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: .55rem;
    margin-bottom: 1.1rem;
}
.bk-step {
    position: relative;
    padding: .65rem .7rem;
    border-radius: 1rem;
    background: rgba(15,23,42,.55);
    border: 1px solid var(--bk-border);
    text-align: center;
    transition: border-color .3s, background .3s;
}
.bk-step.is-active {
    border-color: rgba(250,204,21,.55);
    background:
        linear-gradient(180deg, rgba(250,204,21,.10), rgba(250,204,21,.02));
    box-shadow: 0 0 24px -8px rgba(250,204,21,.4);
}
.bk-step-no {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 800;
    background: rgba(255,255,255,.06);
    color: var(--bk-text-2);
    margin-bottom: .25rem;
}
.bk-step.is-active .bk-step-no {
    background: linear-gradient(180deg, #fde68a, #f59e0b);
    color: #1b1208;
}
.bk-step-label {
    display: block;
    font-size: 11px;
    line-height: 1.2;
    color: var(--bk-text-2);
    letter-spacing: .02em;
}
.bk-step.is-active .bk-step-label {
    color: #fde68a;
    font-weight: 700;
}

/* ============================================================
   ASIDE — premium "ticket-stub" card. */
.bk-aside {
    position: relative;
    background:
        linear-gradient(180deg, rgba(2,6,23,.7), rgba(2,6,23,.85));
    border: 1px solid rgba(250,204,21,.32);
    border-radius: var(--bk-radius-lg);
    padding: 1.25rem;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.04),
        0 30px 60px -28px rgba(250,204,21,.18);
    isolation: isolate;
}
.bk-aside::before {
    content: "";
    position: absolute;
    inset: -1px;
    border-radius: inherit;
    padding: 1px;
    background: linear-gradient(180deg, rgba(250,204,21,.45), transparent 65%);
    -webkit-mask:
        linear-gradient(#fff 0 0) content-box,
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
            mask-composite: exclude;
    pointer-events: none;
    z-index: -1;
}
.bk-aside-eyebrow {
    font-size: 11px;
    letter-spacing: .22em;
    text-transform: uppercase;
    color: rgba(252,211,77,.85);
    font-weight: 700;
}
.bk-aside-title {
    font-size: clamp(1.05rem, 3.6vw, 1.2rem);
    font-weight: 800;
    line-height: 1.3;
    margin-top: .35rem;
    background: linear-gradient(135deg, #fde68a 0%, #fbbf24 50%, #f87171 100%);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
}
.bk-aside-meta {
    margin-top: .85rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .55rem;
}
.bk-meta-cell {
    background: rgba(255,255,255,.04);
    border: 1px solid var(--bk-border);
    border-radius: .85rem;
    padding: .55rem .65rem;
}
.bk-meta-cell-label {
    font-size: 10px;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--bk-text-3);
}
.bk-meta-cell-value {
    margin-top: .1rem;
    font-size: 13px;
    color: var(--bk-text);
    font-weight: 700;
}
.bk-meta-cell.is-price .bk-meta-cell-value {
    color: #fde68a;
}

.bk-step-panel {
    margin-top: 1rem;
    background:
        linear-gradient(180deg, rgba(2,6,23,.55), rgba(2,6,23,.7));
    border: 1px solid rgba(250,204,21,.22);
    border-radius: 1.25rem;
    padding: 1rem;
}
.bk-step-panel h3 {
    display: flex;
    align-items: center;
    gap: .45rem;
    font-size: 12px;
    color: #fde68a;
    font-weight: 800;
    letter-spacing: .04em;
}
.bk-step-panel h3 .pip {
    width: 18px;
    height: 18px;
    border-radius: 9999px;
    background: linear-gradient(180deg, #fde68a, #f59e0b);
    color: #1b1208;
    font-size: 10px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.bk-step-panel-desc {
    margin-top: .45rem;
    color: var(--bk-text-2);
    font-size: 12px;
    line-height: 1.65;
}

.bk-pay {
    width: 100%;
    text-align: right;
    background: rgba(255,255,255,.04);
    border: 1px solid var(--bk-border);
    border-radius: .9rem;
    padding: .65rem .7rem;
    margin-top: .55rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    transition: background .25s, border-color .25s, transform .2s;
    min-height: 56px;
}
.bk-pay:hover,
.bk-pay:focus-visible {
    background: rgba(255,255,255,.08);
    border-color: rgba(250,204,21,.55);
}
.bk-pay:active { transform: scale(.985); }
.bk-pay-label {
    font-size: 10px;
    letter-spacing: .14em;
    color: var(--bk-text-3);
    text-transform: uppercase;
}
.bk-pay-value {
    margin-top: .1rem;
    font-size: 14px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -.005em;
}
.bk-pay .copy-hint {
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 700;
    color: #fde68a;
    padding: .25rem .55rem;
    border-radius: 9999px;
    background: rgba(250,204,21,.10);
    border: 1px solid rgba(250,204,21,.4);
    transition: color .25s, background .25s;
}

/* ============================================================
   FORM COLUMN — large card with a subtle inner glow. */
.bk-form {
    background:
        linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.78));
    border: 1px solid var(--bk-border);
    border-radius: var(--bk-radius-lg);
    padding: 1.25rem;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.04),
        0 30px 60px -28px rgba(0,0,0,.8);
}
@media (min-width: 640px) {
    .bk-form { padding: 1.5rem; }
}
.bk-form-h {
    display: flex;
    align-items: center;
    gap: .55rem;
}
.bk-form-h .pip {
    width: 22px;
    height: 22px;
    border-radius: 9999px;
    background: linear-gradient(180deg, #fde68a, #f59e0b);
    color: #1b1208;
    font-size: 11px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.bk-form-h h2 {
    font-size: 13px;
    font-weight: 800;
    color: #fde68a;
    letter-spacing: .04em;
}

.bk-error {
    margin-top: 1rem;
    padding: .9rem 1rem;
    border-radius: 1rem;
    background:
        radial-gradient(120% 100% at 0% 0%, rgba(248,113,113,.18), transparent 50%),
        rgba(127,29,29,.18);
    border: 1px solid rgba(248,113,113,.45);
    color: #fecaca;
    font-size: 13px;
    line-height: 1.65;
}
.bk-error ul { margin: 0; padding: 0; list-style: none; }
.bk-error li { display: flex; gap: .35rem; }
.bk-error li::before { content: "•"; color: #fca5a5; }

/* Generic field card */
.bk-field {
    background: rgba(255,255,255,.04);
    border: 1px solid var(--bk-border);
    border-radius: 1.25rem;
    padding: 1rem;
    transition: border-color .3s, background .3s, box-shadow .3s;
}
.bk-field:focus-within {
    border-color: rgba(250,204,21,.55);
    background: rgba(250,204,21,.04);
    box-shadow: 0 0 0 4px rgba(250,204,21,.06);
}
.bk-field-h {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .55rem;
}
.bk-field-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--bk-text);
}

/* Tickets counter visuals */
.bk-counter {
    display: flex;
    align-items: center;
    gap: .9rem;
    margin-top: .85rem;
}
.bk-counter-btn {
    width: 48px;
    height: 48px;
    border-radius: 1rem;
    background: rgba(255,255,255,.06);
    border: 1px solid var(--bk-border);
    color: #fff;
    font-size: 22px;
    font-weight: 800;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background .2s, transform .15s;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}
.bk-counter-btn:hover { background: rgba(255,255,255,.12); }
.bk-counter-btn:active { transform: scale(.94); }
.bk-counter-num {
    flex: 1;
    text-align: center;
    font-size: 36px;
    font-weight: 900;
    color: #fff;
    font-variant-numeric: tabular-nums;
    background: linear-gradient(180deg, #fff, #d1d5db);
    -webkit-background-clip: text;
            background-clip: text;
    color: transparent;
    line-height: 1.1;
}
.bk-counter-total {
    margin-top: .65rem;
    text-align: center;
    font-size: 13px;
    font-weight: 800;
    color: #fde68a;
    letter-spacing: .01em;
}

/* Person cards (template strings in JS still produce
   "space-y-2 bg-black/40 border border-white/10 rounded-2xl p-3"
   wrappers — those Tailwind classes already exist in the
   compiled CSS so we keep them as-is. We layer additional
   visual treatment via the parent .bk-people-list when the
   JS injects them.) */
.bk-people-list > div {
    position: relative;
    padding-top: 2.4rem !important;
}
.bk-people-list > div::before {
    counter-increment: bk-person;
    content: counter(bk-person);
    position: absolute;
    top: .7rem;
    right: .8rem;
    width: 26px;
    height: 26px;
    border-radius: 9999px;
    background: linear-gradient(180deg, #fde68a, #f59e0b);
    color: #1b1208;
    font-size: 11px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.bk-people-list > div > p:first-of-type { display: none; }
.bk-people-list { counter-reset: bk-person; }

/* Inputs (booking-input class on inputs rendered by JS) */
.booking-input {
    font-size: 16px !important; /* iOS auto-zoom prevention */
    transition: border-color .25s, background .25s, box-shadow .25s;
}
.booking-input:focus {
    outline: none;
    border-color: rgba(250,204,21,.6) !important;
    background: rgba(2,6,23,.85) !important;
    box-shadow: 0 0 0 3px rgba(250,204,21,.12);
}

/* Screenshot dropzone polish */
.bk-drop {
    display: block;
    cursor: pointer;
    border: 2px dashed rgba(255,255,255,.22);
    border-radius: 1rem;
    background:
        radial-gradient(circle at top, rgba(250,204,21,.06), transparent 60%),
        rgba(2,6,23,.55);
    padding: 1.2rem 1rem;
    text-align: center;
    transition: border-color .3s, background .3s, transform .3s;
    -webkit-tap-highlight-color: transparent;
}
.bk-drop:hover,
.bk-drop:focus-within {
    border-color: rgba(250,204,21,.55);
    background:
        radial-gradient(circle at top, rgba(250,204,21,.10), transparent 60%),
        rgba(2,6,23,.5);
    transform: translateY(-1px);
}
.bk-drop-glyph {
    font-size: 34px;
    line-height: 1;
    margin-bottom: .35rem;
}
.bk-drop-cta {
    font-size: 13px;
    font-weight: 800;
    color: #fff;
}
.bk-drop-sub {
    font-size: 11px;
    color: var(--bk-text-3);
    margin-top: .25rem;
}
#screenshotPreview {
    max-height: 13rem;
    margin-inline: auto;
    border-radius: .9rem;
    border: 1px solid var(--bk-border);
    object-fit: contain;
}

/* Submit area */
.bk-submit-wrap { padding-top: .3rem; }
.bk-submit-wrap [data-sticky-action] { display: block; }

/* The submit button keeps the original class-set so
   recomputeSubmitState()'s class swap still works without
   any JS change. The CSS below only sharpens the visuals
   in the *enabled* state. */
#submitBtn {
    width: 100%;
    padding: 1rem 1.5rem;
    border-radius: 1.25rem;
    font-size: 14px;
    font-weight: 900;
    letter-spacing: .01em;
    transition: transform .25s var(--bk-ease),
                box-shadow .35s ease,
                background .35s ease,
                color .35s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .55rem;
    min-height: 52px;
    -webkit-tap-highlight-color: transparent;
}
#submitBtn:not([disabled]):hover {
    transform: translateY(-2px);
    box-shadow:
        0 18px 36px -10px rgba(250,204,21,.55),
        0 0 22px rgba(251,191,36,.35) !important;
}
#submitBtn:disabled {
    transform: none;
    box-shadow: none;
    background: rgba(255,255,255,.07) !important;
    color: rgba(229,231,235,.55) !important;
    border: 1px dashed rgba(255,255,255,.16);
}
.bk-submit-hint {
    text-align: center;
    font-size: 11px;
    color: var(--bk-text-3);
    margin-top: .55rem;
}

/* Trust strip */
.bk-trust {
    margin-top: 1rem;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: .55rem;
}
.bk-trust-cell {
    background: rgba(255,255,255,.03);
    border: 1px solid var(--bk-border);
    border-radius: .85rem;
    padding: .55rem;
    text-align: center;
    font-size: 11px;
    color: var(--bk-text-2);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .2rem;
    line-height: 1.3;
}
.bk-trust-cell .glyph {
    font-size: 16px;
    line-height: 1;
}

@media (prefers-reduced-motion: reduce) {
    .bk-pay,
    .bk-counter-btn,
    .bk-drop,
    #submitBtn,
    .bk-step,
    .bk-field {
        transition: none !important;
    }
}
</style>


<section data-booking class="max-w-5xl mx-auto px-4 sm:px-6">

    {{-- Progress rail --}}
    <ol class="bk-rail" aria-label="مراحل الحجز">
        <li class="bk-step">
            <span class="bk-step-no">١</span>
            <span class="bk-step-label">حوّل المبلغ</span>
        </li>
        <li class="bk-step is-active">
            <span class="bk-step-no">٢</span>
            <span class="bk-step-label">بياناتك</span>
        </li>
        <li class="bk-step">
            <span class="bk-step-no">٣</span>
            <span class="bk-step-label">تأكيد</span>
        </li>
    </ol>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-6">

        {{-- ============================================================
             ASIDE — DETAILS + TRANSFER (Step 1)
        ============================================================ --}}
        <aside class="md:col-span-1 bk-aside md:sticky md:top-24 md:self-start">

            <p class="bk-aside-eyebrow">عرض مسرحي · تفاصيل</p>
            <h1 class="bk-aside-title">{{ $showTime->show->title }}</h1>

            <div class="bk-aside-meta">
                <div class="bk-meta-cell">
                    <div class="bk-meta-cell-label">📅 التاريخ</div>
                    <div class="bk-meta-cell-value">
                        {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}
                    </div>
                </div>
                <div class="bk-meta-cell">
                    <div class="bk-meta-cell-label">⏰ الساعة</div>
                    <div class="bk-meta-cell-value">
                        {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}
                    </div>
                </div>
                <div class="bk-meta-cell is-price" style="grid-column: 1 / -1;">
                    <div class="bk-meta-cell-label">🎟️ سعر التذكرة</div>
                    <div class="bk-meta-cell-value">
                        {{ $showTime->ticket_price }} جنيه
                    </div>
                </div>
            </div>

            <div class="bk-step-panel">
                <h3>
                    <span class="pip">١</span>
                    حوّل قيمة التذكرة
                </h3>
                <p class="bk-step-panel-desc">
                    حوّل المبلغ المطلوب على أحد الأرقام التالية، ثم ارفع لقطة شاشة من التحويل في الخطوة ٢.
                    اضغط على الرقم لنسخه.
                </p>

                {{-- Each payment number is tappable: tap to copy. The
                     [data-copy] + .copy-hint contract is preserved
                     so the page-script handles the copy flow. --}}
                <button type="button"
                        data-copy="{{ $transferWallet }}"
                        aria-label="نسخ رقم محفظة الدفع"
                        class="bk-pay">
                    <div class="min-w-0 flex-1 text-right">
                        <p class="bk-pay-label">📱 محفظة</p>
                        <p class="bk-pay-value truncate" dir="ltr">{{ $transferWallet }}</p>
                    </div>
                    <span class="copy-hint">نسخ</span>
                </button>

                <button type="button"
                        data-copy="{{ $transferInsta }}"
                        aria-label="نسخ حساب InstaPay"
                        class="bk-pay">
                    <div class="min-w-0 flex-1 text-right">
                        <p class="bk-pay-label">⚡ InstaPay</p>
                        <p class="bk-pay-value truncate" dir="ltr">{{ $transferInsta }}</p>
                    </div>
                    <span class="copy-hint">نسخ</span>
                </button>
            </div>
        </aside>

        {{-- ============================================================
             FORM — Step 2
        ============================================================ --}}
        <div class="md:col-span-2 bk-form">

            <div class="bk-form-h">
                <span class="pip">٢</span>
                <h2>ارفع لقطة الشاشة وكمّل بياناتك</h2>
            </div>

            @if ($errors->any())
                <div role="alert" class="bk-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('bookings.store', $showTime) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  id="bookingForm"
                  novalidate
                  class="space-y-4 mt-4">
                @csrf

                {{-- Idempotency token (PR #9). Client-generated +
                     stashed in sessionStorage so a refresh re-uses
                     it and is rejected by the server cache. --}}
                <input type="hidden" name="idempotency_token" id="idempotencyToken" value="">

                {{-- 👥 عدد التذاكر --}}
                <div class="bk-field">
                    <div class="bk-field-h">
                        <label class="bk-field-label">
                            <span aria-hidden="true">👥</span>
                            عدد التذاكر
                        </label>
                        <span id="maxHint" class="text-[11px] text-gray-400"></span>
                    </div>

                    <div class="bk-counter">
                        <button type="button"
                                aria-label="إنقاص"
                                onclick="changeCount(-1)"
                                class="bk-counter-btn">−</button>

                        <span id="ticketsCount" class="bk-counter-num">1</span>

                        <button type="button"
                                aria-label="زيادة"
                                onclick="changeCount(1)"
                                class="bk-counter-btn">+</button>
                    </div>

                    <p id="totalPriceHint" class="bk-counter-total"></p>

                    <input type="hidden" name="tickets_count" id="tickets_count" value="1">
                </div>

                {{-- 👤 الأشخاص (rendered by JS into #namesContainer) --}}
                <div id="namesContainer" class="bk-people-list space-y-3"></div>

                {{-- Screenshot --}}
                <div class="bk-field">
                    <div class="bk-field-h">
                        <label for="screenshot" class="bk-field-label">
                            <span aria-hidden="true">📸</span>
                            لقطة شاشة من التحويل
                        </label>
                        <span class="text-[10px] uppercase tracking-widest text-gray-500">PNG / JPG</span>
                    </div>

                    <label for="screenshot"
                           id="screenshotDropzone"
                           class="bk-drop mt-3">
                        <div id="screenshotEmptyState">
                            <p class="bk-drop-glyph" aria-hidden="true">📎</p>
                            <p class="bk-drop-cta">اضغط لاختيار صورة من معرض الصور</p>
                            <p class="bk-drop-sub">حجم أقل من 5MB · لقطات iPhone مدعومة</p>
                        </div>
                        <div id="screenshotPreviewWrap" class="hidden">
                            <img id="screenshotPreview" alt="">
                            <p id="screenshotFileName" class="text-[11px] text-gray-300 mt-2 truncate"></p>
                            <p class="text-[11px] text-amber-300 mt-1">اضغط لاستبدال الصورة</p>
                        </div>
                    </label>

                    <input type="file"
                           name="payment_screenshot"
                           id="screenshot"
                           accept="image/*"
                           class="hidden">

                    <p id="screenshotError"
                       class="hidden text-[12px] text-red-300 mt-2"></p>
                </div>

                {{-- Natural-position submit (also drives the sticky
                     footer clone). The submit button itself is wrapped
                     in data-sticky-action; the hint sits OUTSIDE so it
                     never duplicates into the floating footer. The
                     button's initial Tailwind classes are preserved
                     verbatim so recomputeSubmitState() can still flip
                     them. --}}
                <div class="bk-submit-wrap">
                    <div data-sticky-action>
                        <button type="submit"
                                id="submitBtn"
                                disabled
                                class="w-full px-6 py-3.5 rounded-2xl
                                       bg-gray-600 text-black/80 text-sm font-bold
                                       disabled:cursor-not-allowed transition
                                       shadow-[0_8px_30px_rgba(250,204,21,0.0)]
                                       flex items-center justify-center gap-2">
                            <span id="submitLabel">
                                <span aria-hidden="true">🎟️</span>
                                إرسال طلب الحجز
                            </span>
                        </button>
                    </div>
                    <p id="submitHint" class="bk-submit-hint">
                        أكمل البيانات وارفع لقطة الشاشة لتفعيل زر الإرسال
                    </p>
                </div>
            </form>

            {{-- Trust strip --}}
            <div class="bk-trust" aria-hidden="true">
                <div class="bk-trust-cell">
                    <span class="glyph">🔒</span>
                    <span>دفع آمن</span>
                </div>
                <div class="bk-trust-cell">
                    <span class="glyph">⚡</span>
                    <span>تأكيد سريع</span>
                </div>
                <div class="bk-trust-cell">
                    <span class="glyph">📱</span>
                    <span>تذكرة QR على واتساب</span>
                </div>
            </div>
        </div>

    </div>
</section>


{{-- ======================
| SCRIPT (preserved verbatim from PR #9)
====================== --}}
<script>
(function () {
    // --- Tickets state ---------------------------------------------
    var count = {{ (int) old('tickets_count', 1) }};
    var maxTickets = {{ max(0, $showTime->total_tickets - $showTime->bookings()
        ->whereIn('status', ['approved', 'pending'])
        ->sum('tickets_count')) }};
    var ticketPrice = {{ (int) $showTime->ticket_price }};

    var namesContainer = document.getElementById('namesContainer');
    var ticketsInput   = document.getElementById('tickets_count');
    var countDisplay   = document.getElementById('ticketsCount');
    var maxHint        = document.getElementById('maxHint');
    var totalPriceHint = document.getElementById('totalPriceHint');

    // Preserve old() values when the server bounced the form back
    // with validation errors so the user doesn't have to retype.
    var oldNames  = @json(old('names', []));
    var oldPhones = @json(old('phones', []));

    function renderNames() {
        namesContainer.innerHTML = '';
        for (var i = 1; i <= count; i++) {
            var idx = i - 1;
            var nameVal  = String(oldNames[idx]  || '').replace(/"/g, '&quot;');
            var phoneVal = String(oldPhones[idx] || '').replace(/"/g, '&quot;');

            namesContainer.insertAdjacentHTML('beforeend',
                '<div class="space-y-2 bg-black/40 border border-white/10 rounded-2xl p-3">' +
                  '<p class="text-[11px] text-gray-400">شخص ' + i + '</p>' +
                  '<input type="text" name="names[]" value="' + nameVal + '"' +
                    ' placeholder="الاسم بالكامل"' +
                    ' autocomplete="name"' +
                    ' inputmode="text"' +
                    ' class="booking-input w-full rounded-xl bg-black/60 border border-white/15 px-3 py-3 text-white"' +
                    ' required>' +
                  '<input type="tel" name="phones[]" value="' + phoneVal + '"' +
                    ' placeholder="رقم واتساب (مثال 01012345678)"' +
                    ' autocomplete="tel"' +
                    ' inputmode="tel"' +
                    ' pattern="[0-9+\\s\\-]{8,16}"' +
                    ' class="booking-input w-full rounded-xl bg-black/60 border border-white/15 px-3 py-3 text-white"' +
                    ' required>' +
                '</div>'
            );
        }
        wireInputs();
        recomputeSubmitState();
    }

    function changeCount(val) {
        var prev = count;
        count += val;
        if (count < 1) count = 1;
        if (count > maxTickets) {
            count = Math.max(1, maxTickets);
            flashMax();
        }
        if (count === prev) {
            updatePriceHint();
            return;
        }

        // Stash what the user typed so re-rendering doesn't blow away
        // valid entries when they tap +/− mid-flow.
        var existingNames  = namesContainer.querySelectorAll('input[name="names[]"]');
        var existingPhones = namesContainer.querySelectorAll('input[name="phones[]"]');
        oldNames  = Array.prototype.map.call(existingNames,  function (n) { return n.value; });
        oldPhones = Array.prototype.map.call(existingPhones, function (n) { return n.value; });

        countDisplay.innerText = count;
        ticketsInput.value     = count;
        updatePriceHint();
        renderNames();
    }
    window.changeCount = changeCount;

    function updatePriceHint() {
        totalPriceHint.innerText =
            'الإجمالي: ' + (count * ticketPrice).toLocaleString('ar-EG') + ' جنيه';
        maxHint.innerText = maxTickets > 0
            ? 'المتاح: ' + maxTickets + ' تذكرة'
            : 'لا تذاكر متاحة حاليًا';
    }

    function flashMax() {
        if (!countDisplay.animate) return;
        countDisplay.animate(
            [{ transform: 'scale(1)' }, { transform: 'scale(1.18)' }, { transform: 'scale(1)' }],
            { duration: 260 }
        );
    }

    // --- Submit gate -----------------------------------------------
    var screenshotInput  = document.getElementById('screenshot');
    var previewWrap      = document.getElementById('screenshotPreviewWrap');
    var emptyState       = document.getElementById('screenshotEmptyState');
    var previewImg       = document.getElementById('screenshotPreview');
    var fileNameEl       = document.getElementById('screenshotFileName');
    var screenshotError  = document.getElementById('screenshotError');
    var submitBtn        = document.getElementById('submitBtn');
    var submitLabel      = document.getElementById('submitLabel');
    var submitHint       = document.getElementById('submitHint');
    var bookingForm      = document.getElementById('bookingForm');

    var isSubmitting = false;

    function fieldsValid() {
        var ok = true;
        namesContainer.querySelectorAll('input').forEach(function (el) {
            if (!el.value.trim()) ok = false;
        });
        return ok;
    }

    function screenshotReady() {
        return screenshotInput.files && screenshotInput.files.length > 0;
    }

    function recomputeSubmitState() {
        if (isSubmitting) return;
        var ready = fieldsValid() && screenshotReady() && maxTickets > 0;
        submitBtn.disabled = !ready;
        if (ready) {
            submitBtn.classList.remove('bg-gray-600', 'text-black/80');
            submitBtn.classList.add('bg-amber-400', 'text-black',
                'shadow-[0_10px_40px_rgba(250,204,21,0.35)]');
            submitHint.classList.add('hidden');
        } else {
            submitBtn.classList.add('bg-gray-600', 'text-black/80');
            submitBtn.classList.remove('bg-amber-400', 'text-black',
                'shadow-[0_10px_40px_rgba(250,204,21,0.35)]');
            submitHint.classList.remove('hidden');
        }
    }

    function wireInputs() {
        namesContainer.querySelectorAll('input').forEach(function (el) {
            el.addEventListener('input', recomputeSubmitState);
            el.addEventListener('blur',  recomputeSubmitState);
        });
    }

    screenshotInput.addEventListener('change', function () {
        screenshotError.classList.add('hidden');
        var file = screenshotInput.files[0];
        if (!file) {
            previewWrap.classList.add('hidden');
            emptyState.classList.remove('hidden');
            recomputeSubmitState();
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            screenshotError.innerText = 'حجم الصورة أكبر من 5MB — جرّب صورة أصغر.';
            screenshotError.classList.remove('hidden');
            screenshotInput.value = '';
            previewWrap.classList.add('hidden');
            emptyState.classList.remove('hidden');
            recomputeSubmitState();
            return;
        }
        var url = URL.createObjectURL(file);
        previewImg.src = url;
        previewImg.onload = function () { URL.revokeObjectURL(url); };
        fileNameEl.innerText = file.name;
        emptyState.classList.add('hidden');
        previewWrap.classList.remove('hidden');
        recomputeSubmitState();
    });

    // --- Idempotency token ----------------------------------------
    // We stash a random token per page load in sessionStorage,
    // scoped to this showtime. A refresh re-uses the same token,
    // which the server uses as a Cache::add() key to reject the
    // duplicate POST cleanly. Cleared on successful submit.
    var tokenKey = 'booking_token_show_{{ $showTime->id }}';
    var tokenEl  = document.getElementById('idempotencyToken');

    function makeToken() {
        try {
            var buf = new Uint8Array(16);
            (window.crypto || window.msCrypto).getRandomValues(buf);
            return Array.prototype.map.call(buf, function (b) {
                return ('0' + b.toString(16)).slice(-2);
            }).join('');
        } catch (e) {
            return Date.now() + '-' + Math.random().toString(36).slice(2);
        }
    }

    var token = null;
    try { token = sessionStorage.getItem(tokenKey); } catch (e) {}
    if (!token) {
        token = makeToken();
        try { sessionStorage.setItem(tokenKey, token); } catch (e) {}
    }
    tokenEl.value = token;

    // --- Submit handling ------------------------------------------
    bookingForm.addEventListener('submit', function (e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        if (submitBtn.disabled) {
            e.preventDefault();
            return false;
        }
        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.classList.add('is-loading');
        submitLabel.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span>جاري الإرسال…';
        submitHint.innerText = 'لا تغلق الصفحة حتى يكتمل الرفع';
        submitHint.classList.remove('hidden');
    });

    // Reset state if the user navigates back via bfcache (iOS Safari).
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            isSubmitting = false;
            submitBtn.classList.remove('is-loading');
            submitLabel.innerText = 'إرسال طلب الحجز';
            recomputeSubmitState();
        }
    });

    // --- Tap-to-copy on payment numbers ---------------------------
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var value = btn.getAttribute('data-copy') || '';
            var hint  = btn.querySelector('.copy-hint');
            var done = function () {
                if (!hint) return;
                var prev = hint.innerText;
                hint.innerText = 'تم النسخ ✓';
                hint.classList.add('text-emerald-300');
                setTimeout(function () {
                    hint.innerText = prev;
                    hint.classList.remove('text-emerald-300');
                }, 1200);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(done, done);
            } else {
                // iOS Safari fallback
                var ta = document.createElement('textarea');
                ta.value = value;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); } catch (err) {}
                document.body.removeChild(ta);
                done();
            }
        });
    });

    // --- Init ------------------------------------------------------
    if (maxTickets === 0) {
        count = 0;
        countDisplay.innerText = '0';
        ticketsInput.value = 0;
    } else {
        if (count > maxTickets) count = maxTickets;
        countDisplay.innerText = count;
        ticketsInput.value = count;
    }
    updatePriceHint();
    renderNames();
})();
</script>

@endsection
