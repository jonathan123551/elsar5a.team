@extends('layouts.app')

@section('title', 'تفاصيل الحجز #' . $booking->id)

@section('content')
{{--
    Admin booking detail page — mobile-first redesign.

    Why this exists
    ---------------
    Door operators land on this page from the bookings list. The
    page must answer four questions, in this order:
        1. WHO is this booking for?
        2. WHICH show/time is it for?
        3. Have they paid (screenshot OK)?
        4. What can I DO right now (approve / reject / resend / delete)?
    The layout follows that hierarchy.

    Notes for future maintainers
    ----------------------------
    * Tap targets are ≥44×44 (see `.adm-tap`).
    * The destructive "delete" affordance is a two-step inline
      confirm (tap "حذف" → reveal "تأكيد" + "إلغاء"). Native
      confirm() is avoided because it's ugly on iOS Safari and the
      system back-gesture can dismiss it accidentally.
    * Sticky context bar at the top keeps the booking number and
      back link visible while the operator scrolls into the
      screenshot or the long ticket list.
    * The approve/reject buttons keep the `data-sticky-action`
      attribute so the existing layout-level JS clones them into
      the floating footer when they scroll out of view.

    Processing overlay
    ------------------
    Approve / Reject / Delete / Resend each kick off a server
    pipeline that can take 10–30 seconds (Cloudinary upload,
    ticket image generation, WhatsApp Cloud API call). The button
    spinner alone is too subtle — operators were tapping
    "Approve" and assuming nothing was happening. We pop a
    full-screen blocking overlay (`#adm-processing`) the instant
    a form on this page submits, with a contextual headline so
    the operator knows what's running.
--}}

<style>
    /* Shared with the bookings list — kept local so each Blade
       view is self-contained. */
    .adm-tap {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Sticky context bar — mobile only. Pinned directly under the
       global navbar so the operator always sees the booking ID +
       back affordance while scrolling. */
    .adm-ctx-bar {
        position: sticky;
        top: 56px;
        z-index: 35;
    }
    @media (min-width: 640px) {
        .adm-ctx-bar { top: 60px; }
    }

    /* Status pill, prominent enough to be the second thing the
       operator sees on the page (after "this is booking #N"). */
    .adm-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 700;
        border: 1px solid transparent;
    }
    .adm-status[data-tone="approved"] {
        background: rgba(16,185,129,0.15);
        color: rgb(110,231,183);
        border-color: rgba(16,185,129,0.4);
    }
    .adm-status[data-tone="rejected"] {
        background: rgba(239,68,68,0.15);
        color: rgb(252,165,165);
        border-color: rgba(239,68,68,0.4);
    }
    .adm-status[data-tone="pending"] {
        background: rgba(14,165,233,0.15);
        color: rgb(125,211,252);
        border-color: rgba(14,165,233,0.4);
    }
    .adm-status .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
    }
    .adm-status[data-tone="pending"] .dot {
        animation: dotPulse 1.5s ease-in-out infinite;
    }
    @keyframes dotPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .55; transform: scale(.75); }
    }

    /* Hero card — anchors the page with the show title +
       date/time + a faded poster strip. Visually distinct from
       the regular `.adm-card`s below so the operator's eye lands
       here first. */
    .adm-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        border: 1px solid rgba(255,255,255,0.1);
        background:
            linear-gradient(135deg, rgba(245,158,11,0.08) 0%, rgba(0,0,0,0.55) 60%),
            rgba(0,0,0,0.5);
        padding: 18px 18px 20px;
    }
    .adm-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: var(--adm-hero-img, none);
        background-size: cover;
        background-position: center;
        opacity: .18;
        filter: blur(2px) saturate(120%);
        z-index: 0;
    }
    .adm-hero > * { position: relative; z-index: 1; }

    /* Section card. Consistent radius / border / padding makes
       the page read as a series of clean cards rather than a
       wall of fields. */
    .adm-card {
        background: rgba(0,0,0,0.4);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 1rem;
        padding: 16px;
    }
    @media (min-width: 640px) {
        .adm-card { padding: 20px; }
    }

    /* Stat tile (number + label). */
    .adm-stat {
        background: rgba(0,0,0,0.4);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 1rem;
        padding: 14px 16px;
    }
    .adm-stat .num {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
        margin-top: 6px;
    }

    /* Compact timeline row used in the hero (created → approved). */
    .adm-timeline {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 14px;
        margin-top: 10px;
        font-size: 11.5px;
        color: rgb(156,163,175);
    }
    .adm-timeline > span { display: inline-flex; align-items: center; gap: 6px; }
    .adm-timeline .lbl { color: rgb(107,114,128); }

    /* Ticket card — one per attendee. On mobile the actions
       stack below the name/phone row so each action is
       full-width and comfortably tappable. */
    .adm-ticket {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 14px;
        padding: 14px;
    }
    .adm-ticket + .adm-ticket { margin-top: 10px; }
    .adm-ticket .actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 12px;
    }
    @media (max-width: 359px) {
        .adm-ticket .actions { grid-template-columns: 1fr; }
    }
    .adm-ticket .actions > * {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        transition: background .15s, transform .1s, opacity .15s;
    }
    .adm-ticket .actions > *:active { transform: scale(.98); }
    .adm-ticket-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 11px;
        letter-spacing: .04em;
        color: rgb(252,211,77);
        background: rgba(245,158,11,0.08);
        border: 1px solid rgba(245,158,11,0.2);
        border-radius: 6px;
        padding: 2px 6px;
        display: inline-block;
        margin-top: 4px;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Action-area sticky padding. The layout-level
       `has-sticky-action` class already pads the bottom of
       <main>, but we also pad the innermost section for safe-area
       visibility. */
    .adm-actions-pad { padding-bottom: max(20px, env(safe-area-inset-bottom)); }

    /* Two-step destructive confirm. The trigger button has class
       `.adm-confirm-trigger`; on tap it sets `data-armed="true"`
       on the parent `[data-confirm]`, which reveals the armed
       cluster (submit + cancel + helper text) via sibling
       visibility rules. No JS framework required. */
    [data-confirm] .adm-confirm-armed-row,
    [data-confirm] .adm-confirm-armed-text { display: none; }
    [data-confirm][data-armed="true"] .adm-confirm-trigger { display: none; }
    [data-confirm][data-armed="true"] .adm-confirm-armed-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 8px;
    }
    [data-confirm][data-armed="true"] .adm-confirm-armed-text { display: block; }

    /* Loading state — picks up the layout-level `.is-loading`
       class added by the single-submit guard. The shared layout
       already defines `.btn-spinner`; this just opts the
       destructive button in. */
    .adm-btn-spinner::before {
        content: "";
        display: none;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid currentColor;
        border-top-color: transparent;
        animation: btnSpin .7s linear infinite;
        margin-inline-end: 6px;
        vertical-align: -2px;
    }
    .adm-btn-spinner.is-loading::before { display: inline-block; }

    /* =========================================================
       PROCESSING OVERLAY
       ---------------------------------------------------------
       Full-screen blocking modal shown while an approve/reject/
       delete/resend request is in flight. The button spinner on
       its own was too subtle for a 10-30s pipeline (ticket
       generation, Cloudinary upload, WhatsApp send) — operators
       were tapping "Approve" and assuming nothing was happening.

       Behaviour
       ---------
       * Mounted hidden in the markup. Forms with
         `data-processing-message="…"` on submit set the message
         and reveal the overlay (see script at the bottom of the
         file). The page navigates away when the request returns,
         which unmounts the overlay naturally.
       * Pointer-events captured at the overlay level so the
         operator can't accidentally re-tap the underlying button.
       * `beforeunload` warns if the operator tries to close the
         tab while a request is in flight.
       ========================================================= */
    .adm-processing {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(2,6,23,0.78);
        backdrop-filter: blur(8px) saturate(140%);
        -webkit-backdrop-filter: blur(8px) saturate(140%);
        animation: admOverlayFade .18s ease-out both;
    }
    .adm-processing[data-open="true"] { display: flex; }
    @keyframes admOverlayFade {
        from { opacity: 0; }
        to   { opacity: 1; }
    }
    .adm-processing .panel {
        width: min(380px, 100%);
        background:
            linear-gradient(180deg, rgba(245,158,11,0.06), rgba(0,0,0,0)) ,
            rgba(15,23,42,0.95);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 1.25rem;
        padding: 28px 24px 24px;
        text-align: center;
        box-shadow: 0 24px 80px rgba(0,0,0,0.5);
        animation: admPanelIn .25s cubic-bezier(.2,.7,.2,1) both;
    }
    @keyframes admPanelIn {
        from { opacity: 0; transform: translateY(8px) scale(.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .adm-processing .ring {
        width: 56px;
        height: 56px;
        margin: 0 auto 14px;
        border-radius: 50%;
        border: 3px solid rgba(245,158,11,0.15);
        border-top-color: rgb(252,211,77);
        animation: btnSpin .9s linear infinite;
    }
    .adm-processing .title {
        font-size: 16px;
        font-weight: 700;
        color: rgb(254,243,199);
        margin: 4px 0 4px;
    }
    .adm-processing .subtitle {
        font-size: 12.5px;
        color: rgb(156,163,175);
        line-height: 1.6;
    }
    .adm-processing .steps {
        margin-top: 14px;
        text-align: start;
        font-size: 12px;
        color: rgb(203,213,225);
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        padding: 12px 14px;
    }
    .adm-processing .steps li {
        list-style: none;
        padding: 4px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .adm-processing .steps li::before {
        content: "•";
        color: rgba(252,211,77,0.8);
        font-weight: 700;
    }
    .adm-processing .hint {
        margin-top: 12px;
        font-size: 11px;
        color: rgb(248,113,113);
        font-weight: 600;
    }
</style>

<section class="space-y-4 sm:space-y-5 max-w-3xl mx-auto adm-actions-pad">

    {{-- ------------------------------------------------------
         STICKY CONTEXT BAR (mobile)
         ------------------------------------------------------
         The operator opens this page on a phone, scrolls into
         the payment screenshot, and the booking ID was
         previously lost off the top of the screen. This strip
         keeps the booking number + back link permanently in view. --}}
    <div class="adm-ctx-bar sm:!static
                bg-black/70 sm:bg-transparent
                backdrop-blur-md sm:backdrop-blur-0
                border-b border-white/10 sm:border-0
                -mx-3 sm:mx-0 px-3 sm:px-0 py-2 sm:py-0">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-base sm:text-xl font-bold leading-tight truncate">
                    تفاصيل الحجز <span class="text-amber-300">#{{ $booking->id }}</span>
                </h1>
                <p class="text-[11px] text-gray-400 mt-0.5 truncate" dir="ltr">
                    @if($booking->reference_code)
                        {{ $booking->reference_code }}
                    @endif
                    @if($booking->created_at)
                        · {{ $booking->created_at->format('d/m/Y g:i A') }}
                    @endif
                </p>
            </div>

            <a href="{{ route('admin.bookings.index') }}"
               class="adm-tap text-[12px] px-3 rounded-full bg-white/5 border border-white/10
                      hover:bg-white/10 active:bg-white/15 transition shrink-0">
                ← رجوع
            </a>
        </div>
    </div>

    {{-- Flash status --}}
    @if(session('status'))
        <div role="status"
             class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200
                    text-[13px] rounded-xl p-3 text-center">
            {{ session('status') }}
        </div>
    @endif

    {{-- ------------------------------------------------------
         🎭 HERO — show + status + key dates
         ------------------------------------------------------
         The hero card consolidates the three things the operator
         needs at-a-glance: which show, what's the booking's
         current status, and where in the lifecycle (created /
         approved / rejected) it is. The poster is used as a
         faded background so the card feels grounded in the
         specific show, not generic. --}}
    @php
        $show     = $booking->showTime?->show;
        $showTime = $booking->showTime;
        $posterCss = $show?->poster_path ? "url('" . e($show->poster_path) . "')" : null;
    @endphp
    <div class="adm-hero" @if($posterCss) style="--adm-hero-img: {{ $posterCss }};" @endif>
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-[11px] uppercase tracking-wider text-amber-300/80 font-semibold">
                    العرض
                </p>
                <p class="text-base sm:text-lg font-bold text-white leading-snug mt-1 line-clamp-2">
                    {{ $show?->title ?? '—' }}
                </p>
                @if($showTime)
                    <p class="text-[12.5px] text-gray-300 mt-1.5 tabular-nums" dir="ltr">
                        🗓 {{ $showTime->date->format('d/m/Y') }}
                        · {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}
                    </p>
                @endif
            </div>

            <div class="shrink-0">
                @if($booking->status === 'approved')
                    <span class="adm-status" data-tone="approved">
                        <span class="dot"></span>
                        مقبول
                    </span>
                @elseif($booking->status === 'rejected')
                    <span class="adm-status" data-tone="rejected">
                        <span class="dot"></span>
                        مرفوض
                    </span>
                @else
                    <span class="adm-status" data-tone="pending">
                        <span class="dot"></span>
                        قيد المراجعة
                    </span>
                @endif
            </div>
        </div>

        {{-- Booker name + phone, with quick WhatsApp deep link
             so the operator can ping the customer without
             leaving the page. --}}
        <div class="mt-4 pt-4 border-t border-white/10
                    flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[15px] font-semibold text-white truncate">
                    {{ $booking->full_name }}
                </p>
                <p class="text-[12.5px] text-gray-400 mt-0.5 tabular-nums" dir="ltr">
                    {{ $booking->phone }}
                </p>
            </div>
            @if($booking->phone)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->phone) }}"
                   target="_blank" rel="noopener"
                   class="adm-tap text-[12px] px-3 rounded-full
                          bg-emerald-500/15 hover:bg-emerald-500/25
                          border border-emerald-500/40 text-emerald-200
                          transition shrink-0">
                    💬 واتساب
                </a>
            @endif
        </div>

        {{-- Lifecycle timeline (created → approved/rejected). --}}
        <div class="adm-timeline" dir="ltr">
            @if($booking->created_at)
                <span><span class="lbl">Created</span>
                    {{ $booking->created_at->format('d/m g:i A') }}</span>
            @endif
            @if($booking->approved_at)
                <span class="text-emerald-300">
                    <span class="lbl">Approved</span>
                    {{ $booking->approved_at->format('d/m g:i A') }}
                </span>
            @endif
            @if($booking->rejected_at ?? null)
                <span class="text-red-300">
                    <span class="lbl">Rejected</span>
                    {{ $booking->rejected_at->format('d/m g:i A') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Summary stats --}}
    @php
        $sentCount  = $booking->tickets->where('whatsapp_sent', true)->count();
        $totalCount = $booking->tickets->count();
    @endphp
    <div class="grid grid-cols-3 gap-2 sm:gap-3">
        <div class="adm-stat">
            <p class="text-[11px] text-gray-400">عدد التذاكر</p>
            <p class="num text-white tabular-nums">{{ $booking->tickets_count }}</p>
        </div>
        <div class="adm-stat">
            <p class="text-[11px] text-gray-400">الإجمالي</p>
            <p class="num text-amber-300 tabular-nums">
                {{ number_format((int) $booking->total_price) }}
                <span class="text-sm text-amber-300/80">جنيه</span>
            </p>
        </div>
        <div class="adm-stat">
            <p class="text-[11px] text-gray-400">على واتساب</p>
            <p class="num tabular-nums {{ $totalCount && $sentCount === $totalCount ? 'text-emerald-300' : 'text-white' }}">
                {{ $sentCount }}<span class="text-sm text-gray-500">/{{ $totalCount }}</span>
            </p>
        </div>
    </div>

    {{-- ------------------------------------------------------
         🎟️ TICKETS
         ------------------------------------------------------ --}}
    <div class="adm-card space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-[13px] text-amber-300 font-semibold">🎟️ التذاكر</h2>
            <span class="text-[11px] text-gray-400 tabular-nums">
                واتساب: {{ $sentCount }}/{{ $totalCount }}
            </span>
        </div>

        <div class="space-y-2.5 max-h-[55vh] overflow-auto -mx-1 px-1">

            @forelse($booking->tickets as $ticket)
                <div class="adm-ticket">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-white font-semibold truncate">{{ $ticket->name }}</p>
                            <p class="text-[12.5px] text-gray-400 truncate" dir="ltr">{{ $ticket->phone }}</p>
                            @if($ticket->ticket_code)
                                <span class="adm-ticket-code" dir="ltr">{{ $ticket->ticket_code }}</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="w-2 h-2 rounded-full
                                {{ $ticket->whatsapp_sent ? 'bg-emerald-400' : 'bg-red-500' }}"></span>
                            <span class="text-[11px]
                                {{ $ticket->whatsapp_sent ? 'text-emerald-300' : 'text-red-300' }}">
                                {{ $ticket->whatsapp_sent ? 'تم الاستلام' : 'لم يستلم' }}
                            </span>
                        </div>
                    </div>

                    @if($booking->status === 'approved')
                        <div class="actions">

                            @if($ticket->qr_image_path)
                                <a href="{{ $ticket->qr_image_path }}"
                                   target="_blank" rel="noopener"
                                   class="bg-white/10 hover:bg-white/15 active:bg-white/20 text-white">
                                    🎫 عرض التذكرة
                                </a>
                            @else
                                <span class="bg-white/5 text-gray-500 opacity-60">
                                    — التذكرة غير جاهزة
                                </span>
                            @endif

                            <form action="{{ route('admin.resend.ticket', $ticket->id) }}"
                                  method="POST"
                                  class="contents"
                                  data-processing-message="جاري إعادة إرسال التذكرة على واتساب…"
                                  data-processing-steps='["الاتصال بـ WhatsApp Cloud API","إرسال صورة التذكرة"]'>
                                @csrf
                                <button type="submit"
                                        class="adm-btn-spinner
                                               bg-blue-500 hover:bg-blue-600 active:bg-blue-700
                                               text-white
                                               disabled:opacity-60 disabled:cursor-progress">
                                    إعادة إرسال
                                </button>
                            </form>

                        </div>
                    @endif
                </div>
            @empty
                <p class="text-[12.5px] text-gray-400 text-center py-6">لا توجد تذاكر في هذا الحجز.</p>
            @endforelse

        </div>
    </div>

    {{-- ------------------------------------------------------
         📸 PAYMENT SCREENSHOT
         ------------------------------------------------------
         The transfer screenshot is the single most important
         visual evidence for the operator to verify a pending
         booking. We make it large by default, with a clear
         "open original" affordance for high-res inspection. --}}
    @if($booking->transfer_screenshot_path)
        <div class="adm-card space-y-3">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-[13px] text-gray-200 font-semibold">📸 لقطة شاشة التحويل</h2>
                <a href="{{ $booking->transfer_screenshot_path }}"
                   target="_blank" rel="noopener"
                   class="adm-tap text-[12px] px-3 rounded-full bg-white/5 hover:bg-white/10
                          border border-white/10 transition">
                    فتح كاملة ↗
                </a>
            </div>
            <a href="{{ $booking->transfer_screenshot_path }}"
               target="_blank" rel="noopener"
               class="block">
                <img src="{{ $booking->transfer_screenshot_path }}"
                     loading="lazy" decoding="async"
                     class="w-full rounded-xl border border-white/10 bg-black/40"
                     alt="لقطة شاشة التحويل">
            </a>
        </div>
    @endif

    {{-- ------------------------------------------------------
         APPROVE / REJECT  (pending only)
         ------------------------------------------------------
         `data-sticky-action` is read by the layout-level JS that
         clones these buttons into a floating bottom action bar
         while they're off-screen, so the operator can decide on
         the booking without scrolling back to the bottom.

         The `data-processing-message` attribute on each form is
         read by the page's submit handler (see script below) to
         show the full-screen overlay. The wording is specific to
         the action so the operator knows what's happening — an
         approve takes 10-30s (ticket generation + WhatsApp); a
         reject is instant but still benefits from the "hands
         off, processing" cue. --}}
    @if($booking->status === 'pending')
        <div data-sticky-action class="grid grid-cols-2 gap-3 pt-2">
            <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST"
                  data-processing-message="جاري رفض الحجز…"
                  data-processing-steps='["تحديث حالة الحجز"]'>
                @csrf
                <button type="submit"
                        class="adm-btn-spinner w-full px-4 py-3 rounded-2xl
                               bg-red-500 hover:bg-red-600 active:bg-red-700
                               text-white text-sm font-bold transition
                               disabled:opacity-60 disabled:cursor-progress
                               shadow-[0_8px_24px_rgba(239,68,68,0.25)]">
                    ✖ رفض
                </button>
            </form>

            <form action="{{ route('admin.bookings.approve', $booking) }}" method="POST"
                  data-processing-message="جاري اعتماد الحجز وإرسال التذاكر…"
                  data-processing-steps='["إنشاء صور التذاكر","رفع التذاكر إلى Cloudinary","إرسال إشعار الواتساب"]'
                  data-processing-hint="قد يستغرق حتى 30 ثانية. من فضلك لا تغلق الصفحة.">
                @csrf
                <button type="submit"
                        class="adm-btn-spinner w-full px-4 py-3 rounded-2xl
                               bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700
                               text-black text-sm font-bold transition
                               disabled:opacity-60 disabled:cursor-progress
                               shadow-[0_8px_24px_rgba(16,185,129,0.35)]">
                    ✔ اعتماد
                </button>
            </form>
        </div>
    @endif

    {{-- ------------------------------------------------------
         🗑️ DELETE  (approved only)
         ------------------------------------------------------
         Two-step inline confirm: tap "حذف الحجز" → reveals
         "تأكيد الحذف" + "إلغاء". Removes the native confirm()
         (which is ugly on iOS Safari and dismissable by edge
         swipe), and gives the operator a more visible safety net. --}}
    @if($booking->status === 'approved')
        <div class="pt-4 border-t border-white/5">
            <div data-confirm class="space-y-2">
                <button type="button"
                        class="adm-confirm-trigger
                               w-full px-5 py-3 rounded-2xl
                               bg-red-600/15 border border-red-500/40
                               text-red-200 text-sm font-bold transition
                               hover:bg-red-600/25 active:bg-red-600/35">
                    🗑️ حذف الحجز بالكامل
                </button>

                <div class="adm-confirm-armed-row">
                    <form action="{{ route('admin.booking.delete', $booking->id) }}" method="POST"
                          data-processing-message="جاري حذف الحجز وكل التذاكر المرتبطة به…"
                          data-processing-steps='["حذف التذاكر","حذف الحجز"]'>
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="adm-btn-spinner w-full px-5 py-3 rounded-2xl
                                       bg-red-600 hover:bg-red-700 active:bg-red-800
                                       text-white text-sm font-bold transition
                                       disabled:opacity-60 disabled:cursor-progress
                                       shadow-[0_8px_24px_rgba(220,38,38,0.35)]">
                            تأكيد الحذف نهائيًا
                        </button>
                    </form>
                    <button type="button"
                            class="adm-confirm-cancel adm-tap px-4 rounded-2xl
                                   bg-white/5 hover:bg-white/10
                                   border border-white/10 text-gray-200 text-sm">
                        إلغاء
                    </button>
                </div>

                <p class="adm-confirm-armed-text text-[11px] text-gray-400 text-center">
                    سيتم حذف الحجز وجميع التذاكر المرتبطة به نهائيًا.
                </p>
            </div>
        </div>
    @endif

</section>

{{-- ------------------------------------------------------------
     PROCESSING OVERLAY (full-screen modal)
     ------------------------------------------------------------
     Mounted once per page. The submit handler at the bottom of
     this file populates the title / steps / hint based on the
     `data-processing-*` attributes on the form that just
     submitted, then reveals the overlay. The overlay closes
     naturally when the server's redirect response replaces the
     page. --}}
<div id="adm-processing"
     class="adm-processing"
     role="dialog" aria-modal="true" aria-live="polite"
     aria-labelledby="adm-processing-title">
    <div class="panel">
        <div class="ring" aria-hidden="true"></div>
        <h2 id="adm-processing-title" class="title">جاري المعالجة…</h2>
        <p class="subtitle" data-role="subtitle">برجاء الانتظار حتى يكتمل الطلب.</p>
        <ul class="steps" data-role="steps" hidden></ul>
        <p class="hint" data-role="hint" hidden></p>
    </div>
</div>

<script>
/* ----------------------------------------------------------
   Two-step destructive confirm.
   ----------------------------------------------------------
   Replaces the native confirm() dialog (which is ugly on iOS
   Safari and dismissable by edge swipe). The trigger toggles
   `data-armed="true"` on its `[data-confirm]` parent; sibling
   CSS rules above swap which controls are visible. Auto-disarms
   after 6 seconds if the operator doesn't click confirm or
   cancel, so the page doesn't sit in a "primed for delete"
   state indefinitely.
   ---------------------------------------------------------- */
(function () {
    document.querySelectorAll('[data-confirm]').forEach(function (wrap) {
        var trigger = wrap.querySelector('.adm-confirm-trigger');
        var cancel  = wrap.querySelector('.adm-confirm-cancel');
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
        if (cancel) {
            cancel.addEventListener('click', disarm);
        }
    });
})();

/* ----------------------------------------------------------
   Processing overlay + single-submit guard.
   ----------------------------------------------------------
   For every form on this page:
     1. Disable the submit button on submit so a double-tap
        doesn't fire the POST twice (would re-run ticket
        generation, double-charge WhatsApp templates, etc.).
     2. If the form opted into the processing overlay via
        `data-processing-message`, populate and reveal it.
     3. Arm a `beforeunload` warning so the operator can't
        accidentally close the tab mid-request.

   The overlay stays open until the server's response
   replaces the page (redirect after approve / reject / etc.).
   ---------------------------------------------------------- */
(function () {
    var overlay  = document.getElementById('adm-processing');
    var titleEl  = overlay && overlay.querySelector('[id="adm-processing-title"]');
    var subEl    = overlay && overlay.querySelector('[data-role="subtitle"]');
    var stepsEl  = overlay && overlay.querySelector('[data-role="steps"]');
    var hintEl   = overlay && overlay.querySelector('[data-role="hint"]');

    var inFlight = false;

    function showOverlay(form) {
        if (!overlay) return;

        var msg   = form.getAttribute('data-processing-message');
        var steps = form.getAttribute('data-processing-steps');
        var hint  = form.getAttribute('data-processing-hint');

        if (msg && titleEl) {
            titleEl.textContent = msg;
        }
        if (subEl) {
            subEl.textContent = 'برجاء الانتظار حتى يكتمل الطلب.';
        }
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
            if (hint) {
                hintEl.textContent = hint;
                hintEl.hidden = false;
            } else {
                hintEl.textContent = '';
                hintEl.hidden = true;
            }
        }
        overlay.setAttribute('data-open', 'true');
    }

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            // single-submit guard: disable buttons immediately so
            // a fast double-tap doesn't submit twice.
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

    // Warn the operator if they try to navigate away while a
    // request is in flight — closing the tab during ticket
    // generation can leave the booking in a half-approved state
    // (some tickets generated, WhatsApp not sent).
    window.addEventListener('beforeunload', function (e) {
        if (!inFlight) return;
        // Modern browsers ignore the message and show their
        // standard "are you sure?" prompt, but we still need to
        // set returnValue + preventDefault to trigger it.
        e.preventDefault();
        e.returnValue = '';
    });
})();
</script>
@endsection
