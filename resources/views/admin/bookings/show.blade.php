@extends('layouts.app')

@section('title', 'تفاصيل الحجز #' . $booking->id)

@section('content')
{{--
    Admin booking detail page — mobile-first redesign.

    Notes for future maintainers:
    -----------------------------
    * Door operators land on this page from the bookings list. The
      page must answer four questions, in this order:
        1. WHO is this booking for?
        2. WHICH show/time is it for?
        3. Have they paid (screenshot OK)?
        4. What can I DO right now (approve / reject / resend / delete)?
      The redesign rearranges the page to match that hierarchy.

    * Tap targets are ≥44×44 (see `.adm-tap`). The previous version
      had View-Ticket / Resend / Open-Screenshot / Back all at 30–36
      pixels tall, well under the iOS HIG 44pt minimum.

    * The destructive "delete" affordance is now a two-step interaction:
        Tap 1 reveals a confirm pill, with an explicit "Cancel" beside it.
      The native `confirm()` dialog has been removed — it's ugly on iOS
      Safari and the system back-gesture can dismiss it accidentally.

    * Sticky context bar at the top keeps the booking number and back
      link visible while the operator scrolls into the screenshot or
      the long ticket list.

    * The approve/reject buttons keep the `data-sticky-action` attribute
      so the existing layout-level JS clones them into the floating
      footer when they scroll out of view.
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

    /* Sticky context bar — mobile only.
       Pinned directly under the global navbar so the operator
       always sees the booking ID + back affordance while scrolling
       through the screenshot or a long ticket list. */
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

    /* Section card. Consistent radius / border / padding makes the
       page read as a series of clean cards rather than a wall of
       fields. */
    .adm-card {
        background: rgba(0,0,0,0.4);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 1rem;
        padding: 16px;
    }
    @media (min-width: 640px) {
        .adm-card { padding: 20px; }
    }

    /* Stat tile (number + label).  */
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

    /* Ticket card — one per attendee. On mobile the actions stack
       below the name/phone row so each action is full-width and
       comfortably tappable. */
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

    /* Action-area sticky padding. The layout-level `has-sticky-action`
       class already pads the bottom of <main>, but we also pad the
       innermost section for safe-area visibility. */
    .adm-actions-pad { padding-bottom: max(20px, env(safe-area-inset-bottom)); }

    /* Two-step destructive confirm. The trigger button has class
       `.adm-confirm-trigger`; on tap it sets `data-armed="true"` on
       the parent `[data-confirm]`, which reveals the armed cluster
       (submit + cancel + helper text) via sibling visibility rules.
       No JS framework required. */
    [data-confirm] .adm-confirm-armed-row,
    [data-confirm] .adm-confirm-armed-text { display: none; }
    [data-confirm][data-armed="true"] .adm-confirm-trigger { display: none; }
    [data-confirm][data-armed="true"] .adm-confirm-armed-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 8px;
    }
    [data-confirm][data-armed="true"] .adm-confirm-armed-text { display: block; }

    /* Loading state — picks up the layout-level `.is-loading` class
       added by the single-submit guard. The shared layout already
       defines `.btn-spinner`, this just opts the destructive button
       in. */
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
</style>

<section class="space-y-4 sm:space-y-5 max-w-3xl mx-auto adm-actions-pad">

    {{-- ------------------------------------------------------
         STICKY CONTEXT BAR (mobile)
         ------------------------------------------------------
         The operator opens this page on a phone, scrolls into the
         payment screenshot, and the booking ID was previously lost
         off the top of the screen. This strip keeps the booking
         number + back link permanently in view. --}}
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
         STATUS + SHOW INFO
         ------------------------------------------------------
         Status pill is the immediate "what's the state?" cue.
         Below it, a quick show/time line answers "which event?". --}}
    <div class="flex flex-col items-center gap-3 pt-1">
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

        @if($booking->showTime)
            <div class="text-center text-[13px] text-gray-300 leading-relaxed">
                <span class="text-amber-300">🎭</span>
                <span class="text-white font-medium">
                    {{ $booking->showTime->show->title ?? '—' }}
                </span>
                <span class="text-gray-500 mx-1">·</span>
                <span class="text-gray-400 tabular-nums" dir="ltr">
                    {{ $booking->showTime->date->format('d/m/Y') }}
                    · {{ \Carbon\Carbon::parse($booking->showTime->time)->format('g:i A') }}
                </span>
            </div>
        @endif

        {{-- Guest header — name + phone in a compact line. The
             ticket list below adds per-attendee detail. --}}
        <div class="text-center pt-1">
            <p class="text-[15px] font-semibold text-white">{{ $booking->full_name }}</p>
            <p class="text-[12.5px] text-gray-400 mt-0.5" dir="ltr">{{ $booking->phone }}</p>
        </div>
    </div>

    {{-- Summary stats --}}
    <div class="grid grid-cols-2 gap-3">
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
    </div>

    {{-- ------------------------------------------------------
         🎟️ TICKETS
         ------------------------------------------------------ --}}
    <div class="adm-card space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-[13px] text-amber-300 font-semibold">🎟️ التذاكر</h2>
            @php
                $sentCount  = $booking->tickets->where('whatsapp_sent', true)->count();
                $totalCount = $booking->tickets->count();
            @endphp
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
                                  method="POST" class="contents resend-form">
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
         the booking without scrolling back to the bottom. --}}
    @if($booking->status === 'pending')
        <div data-sticky-action class="grid grid-cols-2 gap-3 pt-2">
            <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST"
                  class="approve-form">
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
                  class="approve-form">
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
                          class="delete-form">
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

/* Single-submit guard for every form on this page. Same pattern
   the public booking flow uses. Disabling the button immediately
   on submit prevents double-tap / double-click from firing the
   POST twice and accidentally re-running the WhatsApp template
   send. */
(function () {
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            requestAnimationFrame(function () {
                form.querySelectorAll('button[type=submit], input[type=submit]').forEach(function (b) {
                    if (b.disabled) return;
                    b.disabled = true;
                    b.classList.add('is-loading');
                });
            });
        });
    });
})();
</script>
@endsection
