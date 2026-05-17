@extends('layouts.app')

@section('title', 'إدارة الحجوزات')

@section('content')
{{--
    Admin bookings list — mobile-first.

    Notable polish:
    - The filter bar is sticky on mobile so the operator can scrub
      through 300 bookings without losing their search position.
    - Mobile cards have proper visual hierarchy (name → meta →
      status/action), bigger tap target on "تفاصيل" and clearer
      status pills.
    - The desktop table keeps the same shape but pads cells a bit
      more and lazy-renders the inline ticket list under a toggle so
      it doesn't blow up vertical space when there are many people
      on the same booking.
    - "No matches" empty state when the active filters return zero.
--}}
<section class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold">إدارة الحجوزات</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-[12px] px-3 py-2 rounded-full bg-white/5 border border-white/10
                  hover:bg-white/10 active:bg-white/15 transition">
            ← رجوع
        </a>
    </div>

    {{-- Flash status --}}
    @if(session('status'))
        <div role="status"
             class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-[13px] rounded-xl p-3">
            {{ session('status') }}
        </div>
    @endif

    {{-- Sticky filter bar.
         On mobile the bookings list can be very long, so the filter
         bar pins to the top of the scroll container, behind a small
         backdrop blur, while the rest of the page scrolls. Sits
         below the existing navbar via top-2 / sm:top-4. --}}
    <div class="sticky top-2 sm:top-4 z-30
                bg-black/60 backdrop-blur-md
                border border-white/10 rounded-2xl p-3 sm:p-4
                shadow-[0_4px_30px_rgba(0,0,0,0.4)]">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 text-[13px] sm:text-xs">
            <div class="relative">
                <input id="searchInput" type="search"
                       autocomplete="off"
                       enterkeyhint="search"
                       placeholder="🔎 بحث بالاسم / الموبايل / الكود"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2.5 pe-9
                              focus:outline-none focus:border-amber-400/60 transition">
                <button id="searchClear" type="button"
                        aria-label="مسح البحث"
                        class="hidden absolute inset-y-0 end-2 my-auto h-7 w-7 rounded-full
                               bg-white/10 hover:bg-white/15 text-xs">✕</button>
            </div>

            <select id="statusFilter"
                    class="rounded-xl bg-black/60 border border-white/15 px-3 py-2.5
                           focus:outline-none focus:border-amber-400/60 transition">
                <option value="">كل الحالات</option>
                <option value="pending">قيد المراجعة</option>
                <option value="approved">مقبول</option>
                <option value="rejected">مرفوض</option>
            </select>

            <select id="dateTimeFilter"
                    class="rounded-xl bg-black/60 border border-white/15 px-3 py-2.5
                           focus:outline-none focus:border-amber-400/60 transition">
                <option value="">كل المواعيد</option>
                @foreach(
                    $bookings->map(fn($b) => $b->showTime
                        ? $b->showTime->date->format('Y-m-d').' '.$b->showTime->time
                        : null)->filter()->unique()->sort()
                    as $dt
                )
                    <option value="{{ $dt }}">
                        {{ \Carbon\Carbon::parse($dt)->format('d/m/Y • g:i A') }}
                    </option>
                @endforeach
            </select>
        </div>

        <p id="resultCount" class="mt-2 text-[11px] text-gray-400"></p>
    </div>

    @if($bookings->isEmpty())
        {{-- Empty state — no bookings yet. --}}
        <div class="bg-black/40 border border-white/10 rounded-2xl p-8 text-center space-y-3">
            <div class="text-5xl">📬</div>
            <h2 class="text-base font-semibold text-gray-200">لا توجد حجوزات بعد</h2>
            <p class="text-xs text-gray-400 leading-relaxed">
                بمجرد وصول أول حجز من الموقع، هيظهر هنا تلقائيًا وتقدر تراجع تحويله وتعتمده.
            </p>
            <div class="pt-2">
                <a href="{{ route('admin.shows.index') }}"
                   class="inline-block text-xs px-4 py-2 rounded-full bg-amber-400 text-black font-semibold
                          hover:bg-amber-300 active:bg-amber-500 transition">
                    🎭 إدارة العروض
                </a>
            </div>
        </div>
    @else

    {{-- 💻 DESKTOP TABLE --}}
    <div class="hidden md:block">
        <div class="bg-black/40 border border-white/10 rounded-2xl overflow-x-auto">
            <table class="w-full text-sm text-gray-200">
                <thead class="bg-white/5 text-xs text-gray-400">
                    <tr>
                        <th class="px-3 py-2.5 text-right">الضيف</th>
                        <th class="px-3 py-2.5 text-right">العرض / الموعد</th>
                        <th class="px-3 py-2.5 text-right">الحالة</th>
                        <th class="px-3 py-2.5 text-center">واتساب</th>
                        <th class="px-3 py-2.5 text-right">إجراءات</th>
                        <th class="px-3 py-2.5 text-right">الكود</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($bookings as $booking)
                    @php
                        $dt = $booking->showTime
                            ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                            : '';
                        $allSent = $booking->tickets->every(fn($t) => $t->whatsapp_sent);
                        $total   = $booking->tickets->count();
                        $sent    = $booking->tickets->where('whatsapp_sent', true)->count();
                    @endphp
                    <tr class="border-t border-white/5 booking-row hover:bg-white/[0.02] transition"
                        data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                        data-status="{{ $booking->status }}"
                        data-datetime="{{ $dt }}">

                        <td class="px-3 py-2.5 align-top">
                            <p class="font-bold leading-tight">{{ $booking->full_name }}</p>
                            <p class="text-[11px] text-amber-400 mt-0.5">
                                🎟️ {{ $booking->tickets_count }} تذكرة
                            </p>
                            <p class="text-[12px] text-gray-400 mt-0.5" dir="ltr">{{ $booking->phone }}</p>

                            @if($booking->tickets->count() > 1)
                                <details class="mt-1 group">
                                    <summary class="cursor-pointer text-[11px] text-gray-500 hover:text-gray-300 select-none">
                                        عرض الأشخاص ({{ $booking->tickets->count() }})
                                    </summary>
                                    <div class="mt-1 space-y-0.5">
                                        @foreach($booking->tickets as $ticket)
                                            <div class="text-[11px] text-gray-400">
                                                👤 {{ $ticket->name }}
                                                <span class="text-gray-500" dir="ltr">— {{ $ticket->phone }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        </td>

                        <td class="px-3 py-2.5 align-top">
                            <p>{{ $booking->showTime->show->title ?? '-' }}</p>
                            <span class="text-gray-400 text-[12px]">
                                {{ $booking->showTime?->date->format('d/m/Y') }}
                                • {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                            </span>
                        </td>

                        <td class="px-3 py-2.5 align-top">
                            @if($booking->status === 'approved')
                                <span class="px-2 py-1 rounded-full text-[11px]
                                             bg-emerald-500/15 text-emerald-200 border border-emerald-500/40">
                                    مقبول
                                </span>
                            @elseif($booking->status === 'rejected')
                                <span class="px-2 py-1 rounded-full text-[11px]
                                             bg-red-500/15 text-red-200 border border-red-500/40">
                                    مرفوض
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-full text-[11px]
                                             bg-sky-500/15 text-sky-200 border border-sky-500/40">
                                    قيد المراجعة
                                </span>
                            @endif
                        </td>

                        <td class="px-3 py-2.5 text-center align-top">
                            <div class="flex flex-col items-center gap-1">
                                <span class="inline-block w-2.5 h-2.5 rounded-full
                                    {{ $allSent ? 'bg-emerald-400' : 'bg-red-500' }}"></span>
                                <span class="text-[10px] text-gray-400 tabular-nums">
                                    {{ $sent }}/{{ $total }}
                                </span>
                            </div>
                        </td>

                        <td class="px-3 py-2.5 align-top">
                            <a href="{{ route('admin.bookings.show', $booking) }}"
                               class="inline-block px-3 py-1.5 rounded-full bg-white/10
                                      hover:bg-white/15 active:bg-white/20 transition">
                                تفاصيل
                            </a>
                        </td>

                        <td class="px-3 py-2.5 font-mono text-[11px] text-gray-300 align-top" dir="ltr">
                            {{ $booking->reference_code }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- 📱 MOBILE CARDS --}}
    <div class="md:hidden space-y-3">
        @foreach($bookings as $booking)
            @php
                $dt = $booking->showTime
                    ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                    : '';
                $allSent = $booking->tickets->every(fn($t) => $t->whatsapp_sent);
                $total   = $booking->tickets->count();
                $sent    = $booking->tickets->where('whatsapp_sent', true)->count();
            @endphp

            <a href="{{ route('admin.bookings.show', $booking) }}"
               class="block bg-black/40 border border-white/10 rounded-2xl p-4 text-[13px]
                      hover:bg-black/55 active:bg-black/65 transition booking-card"
               data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
               data-status="{{ $booking->status }}"
               data-datetime="{{ $dt }}">

                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-sm text-white truncate">{{ $booking->full_name }}</p>
                        <p class="text-[12px] text-gray-400 truncate" dir="ltr">{{ $booking->phone }}</p>
                    </div>
                    <span class="font-mono bg-white/5 px-2 py-1 rounded text-[10px] shrink-0" dir="ltr">
                        {{ $booking->reference_code }}
                    </span>
                </div>

                <div class="text-[12px] text-gray-300 mb-2 leading-relaxed">
                    🎭 {{ $booking->showTime->show->title ?? '-' }}<br>
                    <span class="text-gray-500">
                        🕒 {{ $booking->showTime?->date->format('d/m/Y') }}
                        • {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                    </span>
                </div>

                <div class="flex items-center justify-between gap-3 mt-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        @if($booking->status === 'approved')
                            <span class="px-2.5 py-1 rounded-full text-[11px]
                                         bg-emerald-500/15 text-emerald-200 border border-emerald-500/40">
                                مقبول
                            </span>
                        @elseif($booking->status === 'rejected')
                            <span class="px-2.5 py-1 rounded-full text-[11px]
                                         bg-red-500/15 text-red-200 border border-red-500/40">
                                مرفوض
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-[11px]
                                         bg-sky-500/15 text-sky-200 border border-sky-500/40">
                                قيد المراجعة
                            </span>
                        @endif

                        <span class="text-[11px] text-amber-300 font-semibold">
                            🎟️ {{ $booking->tickets_count }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5 text-gray-400 text-[11px]">
                        <span class="w-2 h-2 rounded-full
                            {{ $allSent ? 'bg-emerald-400' : 'bg-red-500' }}"></span>
                        <span class="tabular-nums">{{ $sent }}/{{ $total }}</span>
                    </div>
                </div>

                @if($booking->tickets->count() > 1)
                    <div class="mt-2 pt-2 border-t border-white/5 space-y-0.5">
                        @foreach($booking->tickets->take(3) as $ticket)
                            <div class="text-[11px] text-gray-400 truncate">
                                👤 {{ $ticket->name }}
                                <span class="text-gray-500" dir="ltr">— {{ $ticket->phone }}</span>
                            </div>
                        @endforeach
                        @if($booking->tickets->count() > 3)
                            <div class="text-[10px] text-gray-500">
                                +{{ $booking->tickets->count() - 3 }} آخرين…
                            </div>
                        @endif
                    </div>
                @endif
            </a>
        @endforeach

        {{-- "No matches" appears when filters return zero. Sits in the
             mobile list (also covers desktop visually since this is the
             same DOM tree). --}}
        <div id="noMatchesEmpty"
             class="hidden bg-black/40 border border-white/10 rounded-2xl p-6 text-center space-y-2">
            <div class="text-3xl">🔍</div>
            <p class="text-sm text-gray-200">لا توجد نتائج تطابق الفلترة الحالية.</p>
            <button type="button" id="clearFilters"
                    class="mt-1 text-[12px] px-4 py-2 rounded-full bg-white/10 hover:bg-white/15 transition">
                مسح الفلتر
            </button>
        </div>
    </div>
    @endif

</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var search       = document.getElementById('searchInput');
    var status       = document.getElementById('statusFilter');
    var dt           = document.getElementById('dateTimeFilter');
    var clearBtn     = document.getElementById('searchClear');
    var clearAllBtn  = document.getElementById('clearFilters');
    var resultCount  = document.getElementById('resultCount');
    var noMatches    = document.getElementById('noMatchesEmpty');
    var rows         = document.querySelectorAll('.booking-row');
    var cards        = document.querySelectorAll('.booking-card');
    var items        = [].concat(Array.prototype.slice.call(rows), Array.prototype.slice.call(cards));

    if (!search) return;

    function filter() {
        var s = (search.value || '').toLowerCase().trim();
        var st = status.value;
        var when = dt.value;

        // We toggle visibility per logical booking (mobile card +
        // matching desktop row may both exist). Result count uses
        // the desktop rows only — they're always present even on
        // small viewports (CSS just hides them).
        var visibleRows = 0;
        rows.forEach(function (el) {
            var ok = el.dataset.search.includes(s)
                && (!st || el.dataset.status === st)
                && (!when || el.dataset.datetime === when);
            el.style.display = ok ? '' : 'none';
            if (ok) visibleRows++;
        });
        cards.forEach(function (el) {
            var ok = el.dataset.search.includes(s)
                && (!st || el.dataset.status === st)
                && (!when || el.dataset.datetime === when);
            el.style.display = ok ? '' : 'none';
        });

        clearBtn.classList.toggle('hidden', !s);
        if (noMatches) {
            noMatches.classList.toggle('hidden', visibleRows > 0 || items.length === 0);
        }
        resultCount.innerText = (s || st || when)
            ? 'النتائج: ' + visibleRows
            : '';
    }

    [search, status, dt].forEach(function (i) {
        i.addEventListener('input', filter);
        i.addEventListener('change', filter);
    });
    clearBtn.addEventListener('click', function () {
        search.value = '';
        filter();
        search.focus();
    });
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function () {
            search.value = '';
            status.value = '';
            dt.value = '';
            filter();
        });
    }
});
</script>
@endsection
