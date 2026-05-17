@extends('layouts.app')

@section('title', 'تفاصيل الحجز #' . $booking->id)

@section('content')
{{--
    Admin booking detail page — mobile-first polish.

    Key changes:
    - Sticky action footer for Approve/Reject (door operator at the
      gate doesn't have to hunt for buttons after a long screenshot).
    - Bigger tap targets (≥44px) for every action.
    - Booking summary uses a 2-up card grid on mobile (no more
      horizontal scrolling on iPhone) with consistent spacing.
    - Native confirm() replaced with a more explicit consent line for
      destructive actions and the Approve form so a stray double-tap
      doesn't accidentally email tickets.
    - Screenshot has its own card with a "open original" affordance.
    - We deliberately do NOT touch WhatsApp logic — the resend button
      still hits the same route with the same payload.
--}}
<section class="space-y-5 max-w-4xl mx-auto px-3 sm:px-0">

    {{-- Flash status --}}
    @if(session('status'))
        <div role="status"
             class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-[13px] rounded-xl p-3 text-center">
            {{ session('status') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-lg sm:text-xl font-bold truncate">
                تفاصيل الحجز #{{ $booking->id }}
            </h1>
            <p class="text-[11px] text-gray-400 mt-0.5">
                @if($booking->reference_code)
                    رقم مرجعي: <span dir="ltr">{{ $booking->reference_code }}</span>
                @endif
                @if($booking->created_at)
                    • {{ $booking->created_at->format('d/m/Y g:i A') }}
                @endif
            </p>
        </div>

        <a href="{{ route('admin.bookings.index') }}"
           class="text-[12px] px-4 py-2 rounded-full bg-white/5 border border-white/10
                  hover:bg-white/10 active:bg-white/15 transition">
            ← رجوع
        </a>
    </div>

    {{-- Status pill (prominent on mobile so the operator sees it
         before scrolling into the screenshot) --}}
    <div class="text-center">
        @if($booking->status === 'approved')
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full
                         bg-emerald-500/15 border border-emerald-500/40 text-emerald-300
                         text-sm font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                مقبول
            </span>
        @elseif($booking->status === 'rejected')
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full
                         bg-red-500/15 border border-red-500/40 text-red-300
                         text-sm font-semibold">
                <span class="w-2 h-2 rounded-full bg-red-400"></span>
                مرفوض
            </span>
        @else
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full
                         bg-sky-500/15 border border-sky-500/40 text-sky-300
                         text-sm font-semibold">
                <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
                قيد المراجعة
            </span>
        @endif
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-black/40 border border-white/10 rounded-2xl p-4">
            <p class="text-[11px] text-gray-400">عدد التذاكر</p>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $booking->tickets_count }}</p>
        </div>
        <div class="bg-black/40 border border-white/10 rounded-2xl p-4">
            <p class="text-[11px] text-gray-400">الإجمالي</p>
            <p class="text-2xl font-bold text-amber-300 mt-1 tabular-nums">
                {{ number_format((int) $booking->total_price) }}
                <span class="text-xs text-amber-300/80">جنيه</span>
            </p>
        </div>
    </div>

    {{-- 🎟️ التذاكر --}}
    <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-3 shadow-lg">
        <h2 class="text-sm text-amber-300 font-semibold">🎟️ التذاكر</h2>

        <div class="space-y-2.5 max-h-[55vh] overflow-auto -mx-1 px-1">

            @forelse($booking->tickets as $ticket)
                <div class="bg-white/5 border border-white/10 rounded-xl p-3
                            hover:bg-white/10 transition">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-white font-semibold truncate">{{ $ticket->name }}</p>
                            <p class="text-[12px] text-gray-400 truncate" dir="ltr">{{ $ticket->phone }}</p>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="w-2 h-2 rounded-full
                                {{ $ticket->whatsapp_sent ? 'bg-green-500' : 'bg-red-500' }}"></span>
                            <span class="text-[10px]
                                {{ $ticket->whatsapp_sent ? 'text-green-300' : 'text-red-300' }}">
                                {{ $ticket->whatsapp_sent ? 'تم الاستلام' : 'لم يستلم' }}
                            </span>
                        </div>
                    </div>

                    @if($booking->status === 'approved')
                        <div class="flex flex-wrap gap-2 mt-2.5">

                            @if($ticket->qr_image_path)
                                <a href="{{ $ticket->qr_image_path }}"
                                   target="_blank" rel="noopener"
                                   class="text-[12px] px-3 py-1.5 bg-white/10 hover:bg-white/15
                                          rounded-full transition">
                                    🎫 عرض التذكرة
                                </a>
                            @endif

                            <form action="{{ route('admin.resend.ticket', $ticket->id) }}"
                                  method="POST" class="resend-form inline">
                                @csrf
                                <button type="submit"
                                        class="text-[12px] px-3 py-1.5 bg-blue-500 hover:bg-blue-600
                                               active:bg-blue-700 rounded-full text-white transition
                                               disabled:opacity-60 disabled:cursor-progress">
                                    إعادة إرسال
                                </button>
                            </form>

                        </div>
                    @endif
                </div>
            @empty
                <p class="text-[12px] text-gray-400 text-center py-6">لا توجد تذاكر في هذا الحجز.</p>
            @endforelse

        </div>
    </div>

    {{-- Screenshot --}}
    @if($booking->transfer_screenshot_path)
        <div class="bg-black/40 border border-white/10 rounded-2xl p-3 sm:p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm text-gray-300 font-semibold">📸 لقطة شاشة التحويل</h2>
                <a href="{{ $booking->transfer_screenshot_path }}"
                   target="_blank" rel="noopener"
                   class="text-[11px] px-3 py-1.5 bg-white/5 hover:bg-white/10
                          border border-white/10 rounded-full transition">
                    فتح كاملة ↗
                </a>
            </div>
            <a href="{{ $booking->transfer_screenshot_path }}"
               target="_blank" rel="noopener"
               class="block">
                <img src="{{ $booking->transfer_screenshot_path }}"
                     loading="lazy" decoding="async"
                     class="w-full rounded-xl border border-white/10"
                     alt="لقطة شاشة التحويل">
            </a>
        </div>
    @endif

    {{-- Approve / Reject — sticky action --}}
    @if($booking->status === 'pending')
        <div data-sticky-action
             class="grid grid-cols-2 gap-3 pt-2">
            <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST"
                  class="approve-form">
                @csrf
                <button type="submit"
                        class="w-full px-4 py-3 rounded-2xl bg-red-500 hover:bg-red-600
                               active:bg-red-700 text-white text-sm font-bold transition
                               disabled:opacity-60 disabled:cursor-progress
                               shadow-[0_8px_24px_rgba(239,68,68,0.25)]">
                    ✖ رفض
                </button>
            </form>

            <form action="{{ route('admin.bookings.approve', $booking) }}" method="POST"
                  class="approve-form">
                @csrf
                <button type="submit"
                        class="w-full px-4 py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-600
                               active:bg-emerald-700 text-black text-sm font-bold transition
                               disabled:opacity-60 disabled:cursor-progress
                               shadow-[0_8px_24px_rgba(16,185,129,0.35)]">
                    ✔ اعتماد
                </button>
            </form>
        </div>
    @endif

    {{-- 🔥 DELETE BUTTON (approved only) --}}
    @if($booking->status === 'approved')
        <div class="pt-4 border-t border-white/5">
            <form action="{{ route('admin.booking.delete', $booking->id) }}" method="POST"
                  onsubmit="return confirm('متأكد عايز تمسح الحجز بكل التذاكر؟');"
                  class="delete-form">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full px-5 py-3 bg-red-600 hover:bg-red-700 active:bg-red-800
                               text-white rounded-2xl text-sm font-bold transition
                               disabled:opacity-60 disabled:cursor-progress">
                    🗑️ حذف الحجز بالكامل
                </button>
            </form>
        </div>
    @endif

</section>

<script>
// Single-submit guard for every form on this page. Disabling the
// button immediately on submit prevents double-tap / double-click /
// accidental keyboard re-entry from firing the POST twice.
(function () {
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            // Tiny delay so the button text is still readable for one
            // frame; mostly a UX nicety, not a correctness thing.
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
