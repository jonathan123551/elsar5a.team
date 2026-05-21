@extends('layouts.app')

@section('title', 'إدارة الحجوزات')

@section('content')
{{--
    Admin bookings list — mobile-first redesign.

    Notes for future maintainers:
    -----------------------------
    * The page is consumed by door operators on phones during live
      events. Every change here is optimized for "thumb on iPhone in
      one hand while holding a ticket scanner in the other" usage.
    * The filter bar is sticky and pinned UNDER the global navbar
      (the navbar is `sticky top-0 z-40`). Previously this used
      `top-2` which let the filter slide UNDER the navbar by ~45px
      whenever the operator scrolled — measured live on iPhone 12
      Pro emulation: navbar.bottom = 53px, filter.top = 8px.
      We now pin the filter at `top: 56px / 64px` so it stacks
      against the navbar instead of being hidden by it.
    * Both the desktop `.booking-row`s and the mobile
      `.booking-card`s carry the same `data-search`, `data-status`,
      `data-datetime` attributes. The filter JS toggles visibility
      on BOTH so the filter behavior is identical regardless of
      which layout the viewport is rendering.
    * The status segmented control (chips) is a faster touch input
      than the original `<select>` — one tap vs the native picker
      modal. We keep the `<select>` in the DOM (hidden on mobile)
      so the JS filter has a single source of truth for the active
      status, and screen readers still have a labelled control.
    * Tap targets are kept ≥ 44×44 via the shared `.adm-tap` utility
      defined below. iOS Human Interface Guidelines call out 44pt as
      the minimum hit target.
    * Safe-area handling on iPhone is via `env(safe-area-inset-*)`
      around the page padding so the last card isn't hidden under
      the home-indicator on full-screen Safari.
--}}

<style>
    /* ---------------------------------------------------------
       Local utility classes for this page. Kept inline so the
       Blade view is self-contained and we don't risk Tailwind's
       JIT/CDN-purge dropping classes that only appear in attribute
       strings.
       --------------------------------------------------------- */

    /* Minimum-44px tap target, with comfortable padding. */
    .adm-tap {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Status-colored left edge on mobile cards — the dominant
       at-a-glance signal of "is this approved / pending / rejected"
       without forcing the operator to read the pill text. RTL flips
       the visual edge to the right, which is correct here because
       the Arabic page reads right-to-left. */
    .adm-card {
        position: relative;
        overflow: hidden;
    }
    .adm-card::before {
        content: "";
        position: absolute;
        inset-inline-start: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--adm-accent, rgba(125,211,252,0.7));
        opacity: 0.85;
    }
    .adm-card[data-status="approved"] { --adm-accent: rgb(52,211,153); }
    .adm-card[data-status="rejected"] { --adm-accent: rgb(248,113,113); }
    .adm-card[data-status="pending"]  { --adm-accent: rgb(125,211,252); }

    /* Pill (status filter) chip. Visually segmented on mobile so
       the operator can switch between "pending only" and "all"
       with a single thumb tap. */
    .adm-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 38px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 12.5px;
        font-weight: 500;
        color: rgb(209,213,219);
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        transition: background .15s, color .15s, border-color .15s, transform .1s;
        white-space: nowrap;
        flex: 1 1 auto;
    }
    .adm-chip:active { transform: scale(.97); }
    .adm-chip.is-active {
        background: rgb(250,204,21);
        color: rgb(2,6,23);
        border-color: rgb(250,204,21);
        font-weight: 700;
    }
    .adm-chip[data-tone="pending"].is-active  { background: rgb(125,211,252); border-color: rgb(125,211,252); }
    .adm-chip[data-tone="approved"].is-active { background: rgb(52,211,153); border-color: rgb(52,211,153); }
    .adm-chip[data-tone="rejected"].is-active { background: rgb(248,113,113); border-color: rgb(248,113,113); color: rgb(255,255,255); }

    /* Hide the native <select> only on mobile — the chips above
       drive the same filter on phones, but keeping the <select> in
       the DOM lets the existing JS keep a single source of truth. */
    @media (max-width: 639px) {
        #statusFilter { display: none; }
    }

    /* Sticky page-context bar on mobile.
       Sits directly under the navbar with `top: 56px`, so the
       operator never loses the page name / back affordance while
       scrolling through hundreds of rows. The filter bar below it
       stacks under it at `top: 100px` to maintain the visual
       hierarchy. */
    .adm-ctx-bar {
        position: sticky;
        top: 56px;
        z-index: 35;
    }
    @media (min-width: 640px) {
        .adm-ctx-bar { top: 60px; }
    }

    /* The filter bar is the SECOND sticky strip — pinned just
       below the page-context bar. Avoids the regression where the
       filter slid under the global navbar. */
    .adm-filter-bar {
        position: sticky;
        top: 104px;
        z-index: 30;
    }
    @media (min-width: 640px) {
        .adm-filter-bar { top: 114px; }
    }

    /* Pad the bottom of the booking list so the last card never
       sits under the iPhone home-indicator / browser chrome. */
    .adm-list { padding-bottom: max(24px, env(safe-area-inset-bottom)); }

    /* "WhatsApp delivery" badge — kept compact on mobile, expanded
       to a labelled pill on larger viewports. The single dot is the
       at-a-glance signal; the count is for confirmation. */
    .adm-wa-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.08);
        color: rgb(209,213,219);
    }

    /* Empty state — same look on mobile and desktop. */
    .adm-empty {
        background: rgba(0,0,0,0.4);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 1rem;
        padding: 32px 24px;
        text-align: center;
    }
</style>

<section class="adm-list space-y-4 sm:space-y-5">

    {{-- ------------------------------------------------------
         STICKY PAGE-CONTEXT BAR (mobile)
         ------------------------------------------------------
         On phone, this strip stays pinned right under the navbar
         while the operator scrubs through the bookings list. It
         carries the page title, a quick back link, and the live
         result count. On desktop we let it sit naturally at the
         top of the page (top-auto via sm: classes below) so it
         doesn't waste vertical pixels. --}}
    <div class="adm-ctx-bar sm:!static
                bg-black/70 sm:bg-transparent
                backdrop-blur-md sm:backdrop-blur-0
                border-b border-white/10 sm:border-0
                -mx-3 sm:mx-0 px-3 sm:px-0 py-2 sm:py-0">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-base sm:text-2xl font-bold leading-tight truncate">إدارة الحجوزات</h1>
                <p id="resultCount" class="text-[11px] text-gray-400 mt-0.5 tabular-nums"></p>
            </div>
            <a href="{{ route('admin.dashboard') }}"
               class="adm-tap text-[12px] px-3 py-2 rounded-full
                      bg-white/5 border border-white/10
                      hover:bg-white/10 active:bg-white/15 transition shrink-0">
                ← رجوع
            </a>
        </div>
    </div>

    {{-- Flash status --}}
    @if(session('status'))
        <div role="status"
             class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-[13px] rounded-xl p-3">
            {{ session('status') }}
        </div>
    @endif

    {{-- ------------------------------------------------------
         STATUS COUNT SUMMARY
         ------------------------------------------------------
         "At a glance" counts so the operator opens the page and
         instantly sees "I have 3 pending bookings waiting on me".
         Each count is a tap-target that snaps the status filter
         to that bucket. --}}
    @php
        $counts = [
            'all'      => $bookings->count(),
            'pending'  => $bookings->where('status', 'pending')->count(),
            'approved' => $bookings->where('status', 'approved')->count(),
            'rejected' => $bookings->where('status', 'rejected')->count(),
        ];
    @endphp

    @if($bookings->isNotEmpty())
        <div class="grid grid-cols-4 gap-2 sm:gap-3" role="tablist" aria-label="عدّاد الحالات">
            <button type="button" class="adm-chip is-active" data-status-jump="" data-tone="">
                <span class="opacity-70">الكل</span>
                <span class="font-bold tabular-nums">{{ $counts['all'] }}</span>
            </button>
            <button type="button" class="adm-chip" data-status-jump="pending" data-tone="pending">
                <span class="opacity-70">قيد المراجعة</span>
                <span class="font-bold tabular-nums">{{ $counts['pending'] }}</span>
            </button>
            <button type="button" class="adm-chip" data-status-jump="approved" data-tone="approved">
                <span class="opacity-70">مقبول</span>
                <span class="font-bold tabular-nums">{{ $counts['approved'] }}</span>
            </button>
            <button type="button" class="adm-chip" data-status-jump="rejected" data-tone="rejected">
                <span class="opacity-70">مرفوض</span>
                <span class="font-bold tabular-nums">{{ $counts['rejected'] }}</span>
            </button>
        </div>
    @endif

    {{-- ------------------------------------------------------
         STICKY FILTER BAR
         ------------------------------------------------------
         Pinned under the context bar. Mobile uses chips (above)
         to drive the status filter; this bar holds the search +
         show-time pickers which are wider controls that don't
         fit nicely as chips.

         Tap targets are ≥44px (min-height on inputs + selects).
         --}}
    <div class="adm-filter-bar
                bg-black/70 backdrop-blur-md
                border border-white/10 rounded-2xl
                p-3 sm:p-4
                shadow-[0_4px_30px_rgba(0,0,0,0.4)]">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-[14px]">
            <div class="relative">
                <input id="searchInput" type="search"
                       autocomplete="off"
                       enterkeyhint="search"
                       inputmode="search"
                       placeholder="🔎 بحث بالاسم / الموبايل / الكود / رقم الحجز (#3)"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3
                              py-3 sm:py-2.5 pe-9 min-h-[44px]
                              focus:outline-none focus:border-amber-400/60 transition">
                <button id="searchClear" type="button"
                        aria-label="مسح البحث"
                        class="hidden absolute inset-y-0 end-2 my-auto h-9 w-9 rounded-full
                               bg-white/10 hover:bg-white/15 text-sm">✕</button>
            </div>

            {{-- Show-time picker. On mobile the operator usually
                 wants to scope to "tonight's show"; this picker
                 makes that one tap. --}}
            <select id="dateTimeFilter"
                    class="rounded-xl bg-black/60 border border-white/15 px-3
                           py-3 sm:py-2.5 min-h-[44px]
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

        {{-- The hidden <select> below is the single source of
             truth that the filter JS reads. The mobile chips above
             write to it; the desktop sm:block makes it visible. --}}
        <div class="sm:mt-2.5">
            <select id="statusFilter"
                    class="hidden sm:block w-full rounded-xl bg-black/60 border border-white/15 px-3
                           py-2.5 min-h-[44px]
                           focus:outline-none focus:border-amber-400/60 transition">
                <option value="">كل الحالات</option>
                <option value="pending">قيد المراجعة</option>
                <option value="approved">مقبول</option>
                <option value="rejected">مرفوض</option>
            </select>
        </div>
    </div>

    @if($bookings->isEmpty())
        {{-- Empty state — no bookings yet. --}}
        <div class="adm-empty space-y-3">
            <div class="text-5xl">📬</div>
            <h2 class="text-base font-semibold text-gray-200">لا توجد حجوزات بعد</h2>
            <p class="text-xs text-gray-400 leading-relaxed">
                بمجرد وصول أول حجز من الموقع، هيظهر هنا تلقائيًا وتقدر تراجع تحويله وتعتمده.
            </p>
            <div class="pt-2">
                <a href="{{ route('admin.shows.index') }}"
                   class="adm-tap inline-flex text-xs px-5 py-2 rounded-full bg-amber-400 text-black font-semibold
                          hover:bg-amber-300 active:bg-amber-500 transition">
                    🎭 إدارة العروض
                </a>
            </div>
        </div>
    @else

    {{-- ----------------- 💻 DESKTOP TABLE ----------------- --}}
    <div class="hidden md:block">
        <div class="bg-black/40 border border-white/10 rounded-2xl overflow-x-auto">
            <table class="w-full text-sm text-gray-200">
                <thead class="bg-white/5 text-xs text-gray-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-3 text-right font-medium w-20">#</th>
                        <th class="px-4 py-3 text-right font-medium">الضيف</th>
                        <th class="px-4 py-3 text-right font-medium">العرض / الموعد</th>
                        <th class="px-4 py-3 text-right font-medium">الحالة</th>
                        <th class="px-4 py-3 text-center font-medium">واتساب</th>
                        <th class="px-4 py-3 text-right font-medium">الكود</th>
                        <th class="px-4 py-3 text-left font-medium"></th>
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
                    <tr class="border-t border-white/5 booking-row hover:bg-white/[0.03] transition"
                        data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                        data-pn="{{ $booking->public_number ?? '' }}"
                        data-status="{{ $booking->status }}"
                        data-datetime="{{ $dt }}">

                        <td class="px-3 py-3 align-top">
                            <span class="inline-flex items-center justify-center min-w-[44px] h-8 px-2
                                         rounded-lg font-bold tabular-nums text-[13px]
                                         bg-amber-400/10 text-amber-200 border border-amber-400/30">
                                #{{ $booking->public_number ?? $booking->id }}
                            </span>
                        </td>

                        <td class="px-4 py-3 align-top">
                            <p class="font-bold leading-tight text-white">{{ $booking->full_name }}</p>
                            <p class="text-[12px] text-gray-400 mt-1" dir="ltr">{{ $booking->phone }}</p>
                            <p class="text-[11px] text-amber-300 mt-0.5">
                                🎟️ {{ $booking->tickets_count }} تذكرة
                            </p>

                            @if($booking->tickets->count() > 1)
                                <details class="mt-1.5 group">
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

                        <td class="px-4 py-3 align-top">
                            <p class="leading-tight">{{ $booking->showTime->show->title ?? '-' }}</p>
                            <span class="text-gray-400 text-[12px]">
                                {{ $booking->showTime?->date->format('d/m/Y') }}
                                • {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                            </span>
                        </td>

                        <td class="px-4 py-3 align-top">
                            @if($booking->status === 'approved')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px]
                                             bg-emerald-500/15 text-emerald-200 border border-emerald-500/40">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    مقبول
                                </span>
                            @elseif($booking->status === 'rejected')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px]
                                             bg-red-500/15 text-red-200 border border-red-500/40">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                    مرفوض
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px]
                                             bg-sky-500/15 text-sky-200 border border-sky-500/40">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                                    قيد المراجعة
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center align-top">
                            <div class="inline-flex flex-col items-center gap-0.5">
                                <span class="inline-block w-2.5 h-2.5 rounded-full
                                    {{ $allSent ? 'bg-emerald-400' : 'bg-red-500' }}"></span>
                                <span class="text-[10px] text-gray-400 tabular-nums">
                                    {{ $sent }}/{{ $total }}
                                </span>
                            </div>
                        </td>

                        <td class="px-4 py-3 font-mono text-[11px] text-gray-300 align-top" dir="ltr">
                            {{ $booking->reference_code }}
                        </td>

                        <td class="px-4 py-3 align-top">
                            <a href="{{ route('admin.bookings.show', $booking) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                                      bg-amber-400 text-black text-[12px] font-semibold
                                      hover:bg-amber-300 active:bg-amber-500 transition">
                                تفاصيل
                                <span aria-hidden="true">←</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ----------------- 📱 MOBILE CARDS ----------------- --}}
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
               class="adm-card block bg-black/40 border border-white/10 rounded-2xl
                      ps-5 pe-4 py-4 text-[13.5px]
                      hover:bg-black/55 active:bg-black/65 transition booking-card"
               data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
               data-pn="{{ $booking->public_number ?? '' }}"
               data-status="{{ $booking->status }}"
               data-datetime="{{ $dt }}">

                {{-- Row 1: name + status pill (the two scan-points
                     an operator first looks at) --}}
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center justify-center min-w-[40px] h-6 px-2
                                         rounded-md font-bold tabular-nums text-[12px]
                                         bg-amber-400/15 text-amber-200 border border-amber-400/30">
                                #{{ $booking->public_number ?? $booking->id }}
                            </span>
                        </div>
                        <p class="font-bold text-[15px] text-white truncate leading-tight">
                            {{ $booking->full_name }}
                        </p>
                        <p class="text-[12.5px] text-gray-400 truncate mt-0.5" dir="ltr">
                            {{ $booking->phone }}
                        </p>
                    </div>

                    @if($booking->status === 'approved')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] shrink-0
                                     bg-emerald-500/15 text-emerald-200 border border-emerald-500/40">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            مقبول
                        </span>
                    @elseif($booking->status === 'rejected')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] shrink-0
                                     bg-red-500/15 text-red-200 border border-red-500/40">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                            مرفوض
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] shrink-0
                                     bg-sky-500/15 text-sky-200 border border-sky-500/40">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                            قيد المراجعة
                        </span>
                    @endif
                </div>

                {{-- Row 2: event title + show time on one line so
                     the date doesn't wrap awkwardly between two <br>
                     tags (the prior layout). --}}
                <div class="text-[12.5px] text-gray-300 mb-3 leading-relaxed">
                    <span class="text-amber-300">🎭</span>
                    <span class="text-gray-200 font-medium">
                        {{ $booking->showTime->show->title ?? '—' }}
                    </span>
                    <span class="text-gray-500 mx-1">·</span>
                    <span class="text-gray-400 tabular-nums" dir="ltr">
                        {{ $booking->showTime?->date->format('d/m') }}
                        · {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                    </span>
                </div>

                {{-- Row 3: tickets + WhatsApp + ref code (the three
                     small metadata signals, grouped at the bottom) --}}
                <div class="flex items-center justify-between gap-3 pt-2 border-t border-white/5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full
                                     text-[11px] text-amber-200 bg-amber-500/10 border border-amber-500/30
                                     font-semibold tabular-nums">
                            🎟️ {{ $booking->tickets_count }}
                        </span>

                        <span class="adm-wa-badge tabular-nums">
                            <span class="w-2 h-2 rounded-full
                                {{ $allSent ? 'bg-emerald-400' : 'bg-red-500' }}"></span>
                            {{ $sent }}/{{ $total }}
                        </span>
                    </div>

                    <span class="font-mono text-[10.5px] text-gray-500 truncate" dir="ltr">
                        {{ $booking->reference_code }}
                    </span>
                </div>

                @if($booking->tickets->count() > 1)
                    <div class="mt-2.5 pt-2 border-t border-white/5 space-y-0.5">
                        @foreach($booking->tickets->take(3) as $ticket)
                            <div class="text-[11.5px] text-gray-400 truncate">
                                👤 {{ $ticket->name }}
                                <span class="text-gray-500" dir="ltr">— {{ $ticket->phone }}</span>
                            </div>
                        @endforeach
                        @if($booking->tickets->count() > 3)
                            <div class="text-[10.5px] text-gray-500">
                                +{{ $booking->tickets->count() - 3 }} آخرين…
                            </div>
                        @endif
                    </div>
                @endif
            </a>
        @endforeach
    </div>

    {{-- "No matches" empty state (shared between mobile + desktop
         since the JS hides every row/card). --}}
    <div id="noMatchesEmpty"
         class="hidden adm-empty space-y-2">
        <div class="text-3xl">🔍</div>
        <p class="text-sm text-gray-200">لا توجد نتائج تطابق الفلترة الحالية.</p>
        <button type="button" id="clearFilters"
                class="adm-tap mt-1 inline-flex text-[12px] px-4 py-2 rounded-full
                       bg-white/10 hover:bg-white/15 transition">
            مسح الفلتر
        </button>
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
    var statusChips  = document.querySelectorAll('[data-status-jump]');
    var rows         = document.querySelectorAll('.booking-row');
    var cards        = document.querySelectorAll('.booking-card');
    var items        = [].concat(
        Array.prototype.slice.call(rows),
        Array.prototype.slice.call(cards)
    );

    if (!search) return;

    function filter() {
        var s = (search.value || '').toLowerCase().trim();
        var st = status.value;
        var when = dt.value;

        // Booking-number search.
        // ----------------------
        // If the operator types `#3`, `# 3`, or bare `3`, we treat it
        // as an exact match against the booking's public_number
        // (not a substring) so `#3` doesn't also pull up #30, #33,
        // #300, #13, etc. Substring match is still fine for names,
        // phones, and reference_codes — those are handled by
        // `data-search`.
        //
        // Per-show scoping happens for free: when the operator
        // narrows by date+time (which is a single showtime, and a
        // showtime always belongs to exactly one show), the only
        // remaining `#3` row is the one #3 for that show. Without
        // a date-time filter, `#3` will list `#3` across every
        // show — which is the right behaviour when the operator
        // hasn't told us which show they care about yet.
        var hashMatch = s.match(/^#?\s*(\d+)$/);
        var pnQuery = hashMatch ? hashMatch[1] : null;
        var searchQuery = pnQuery ? '' : s;

        function matches(el) {
            if (pnQuery !== null && el.dataset.pn !== pnQuery) return false;
            if (searchQuery && !el.dataset.search.includes(searchQuery)) return false;
            if (st && el.dataset.status !== st) return false;
            if (when && el.dataset.datetime !== when) return false;
            return true;
        }

        // We toggle visibility per logical booking (mobile card +
        // matching desktop row may both exist). Result count uses
        // the desktop rows only — they're always present even on
        // small viewports, the CSS just hides them.
        var visibleRows = 0;
        rows.forEach(function (el) {
            var ok = matches(el);
            el.style.display = ok ? '' : 'none';
            if (ok) visibleRows++;
        });
        cards.forEach(function (el) {
            el.style.display = matches(el) ? '' : 'none';
        });

        clearBtn.classList.toggle('hidden', !s);
        if (noMatches) {
            noMatches.classList.toggle('hidden', visibleRows > 0 || items.length === 0);
        }
        // The result counter sits in the sticky context bar on
        // mobile, so we always render it — it doubles as a hint of
        // "filter is active" because it's only populated when one
        // of the filters has a non-default value.
        resultCount.innerText = (s || st || when)
            ? 'النتائج: ' + visibleRows
            : (items.length ? items.length / 2 + ' حجز' : '');
        // (items.length is rows + cards, so /2 == real bookings.)
    }

    function syncChips() {
        statusChips.forEach(function (c) {
            c.classList.toggle('is-active', (c.dataset.statusJump || '') === (status.value || ''));
        });
    }

    [search, status, dt].forEach(function (i) {
        i.addEventListener('input', function () { filter(); syncChips(); });
        i.addEventListener('change', function () { filter(); syncChips(); });
    });

    statusChips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            status.value = chip.dataset.statusJump || '';
            filter();
            syncChips();
        });
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
            syncChips();
        });
    }

    // Initial render so the count appears.
    filter();
    syncChips();
});
</script>
@endsection
