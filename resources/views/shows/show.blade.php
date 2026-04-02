{{-- resources/views/shows/show.blade.php --}}
@extends('layouts.app')

@section('title', $show->title)

@section('content')

    <p class="text-xs text-amber-400 mb-4">
        ❤ رسالتنا: نجول… نصرخ… فيزداد العقل وعيًا ❤
    </p>

    <section class="space-y-6">

        {{-- صورة + وصف العرض --}}
        <div class="flex flex-col md:flex-row gap-6">

            @if($show->poster_path)
                <div class="relative w-full md:w-72 overflow-hidden rounded-2xl border border-white/10 shadow-[0_0_40px_rgba(250,204,21,0.25)]">
                    <img src="{{ $show->poster_path }}"
                        alt="{{ $show->title }}"
                        class="w-full h-96 object-cover transform hover:scale-[1.03] transition-transform duration-500">

                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/25 to-transparent pointer-events-none"></div>

                    <div class="absolute top-3 right-3 text-[11px] px-2 py-1 rounded-full bg-black/70 border border-white/20 text-gray-100">
                        🎭 عرض مسرحي
                    </div>
                </div>
            @endif

            <div class="flex-1 space-y-3">
                <h1 class="text-2xl md:text-3xl font-bold">{{ $show->title }}</h1>

                <p class="text-sm text-gray-300 leading-relaxed whitespace-pre-line">
                    {{ $show->description }}
                </p>

                <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-gray-300">
                    <span class="px-2 py-1 rounded-full bg-white/5 border border-white/10">
                        🎟️ حجز إلكتروني + تذكرة QR
                    </span>
                </div>
            </div>

        </div>

        {{-- المواعيد --}}
        <div class="space-y-3">
            <h2 class="text-lg font-semibold">المواعيد المتاحة</h2>

            <div class="space-y-3">

                @forelse($show->showTimes as $time)

                    @php
                        $totalTickets = $time->total_tickets;

                        $reserved = \App\Models\Booking::where('show_time_id', $time->id)
                            ->whereIn('status', ['approved','pending'])
                            ->sum('tickets_count');

                        $remaining = $totalTickets - $reserved;

                        $isSoldOut = $time->is_sold_out || $remaining <= 0;
                        $fewTickets = $remaining > 0 && $remaining <= 10;
                    @endphp

                    <div class="relative bg-black/40 border border-white/10 rounded-2xl px-4 py-3 flex flex-col md:flex-row md:items-center justify-between gap-3
                        @if($isSoldOut) opacity-60 @endif">

                        {{-- الشريط الجانبي --}}
                        <div class="absolute right-0 top-0 bottom-0 w-1 rounded-r-2xl
                            @if($isSoldOut)
                                bg-red-500
                            @elseif($fewTickets)
                                bg-amber-400
                            @else
                                bg-emerald-400
                            @endif">
                        </div>

                        <div class="pr-3">
                            <div class="text-sm font-medium">
                                {{ $time->date->format('d/m/Y') }}
                                •
                                {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                            </div>

                            <div class="text-xs text-gray-400 mt-1 flex flex-wrap gap-2 items-center">

                                <span>
                                    سعر التذكرة:
                                    <span class="text-amber-300 font-semibold">
                                        {{ $time->ticket_price }} جنيه
                                    </span>
                                </span>

                                <span class="text-[11px] px-2 py-0.5 rounded-full
                                    @if($isSoldOut)
                                        bg-red-500/15 text-red-200 border border-red-500/40
                                    @elseif($fewTickets)
                                        bg-amber-400/15 text-amber-200 border border-amber-400/40
                                    @else
                                        bg-emerald-500/15 text-emerald-200 border border-emerald-500/40
                                    @endif
                                ">

                                    @if($isSoldOut)
                                        Sold Out
                                    @elseif($fewTickets)
                                        مقاعد محدودة: {{ $remaining }}
                                    @else
                                        Available
                                    @endif

                                </span>

                            </div>
                        </div>

                        <div class="pl-1">
                            @if($isSoldOut)
                                <span class="inline-flex items-center px-3 py-1 text-xs rounded-full bg-red-500/20 text-red-300 border border-red-500/40">
                                    Sold Out
                                </span>
                            @else
                                <a href="{{ route('bookings.create', $time) }}"
                                   class="inline-flex items-center px-4 py-1.5 text-sm rounded-full
                                   @if($fewTickets)
                                       bg-amber-400 text-black hover:bg-amber-300
                                   @else
                                       bg-emerald-400 text-black hover:bg-emerald-300
                                   @endif
                                   font-medium transition">
                                    احجز الآن
                                </a>
                            @endif
                        </div>

                    </div>

                @empty
                    <p class="text-xs text-gray-400">
                        لا توجد مواعيد متاحة حاليًا لهذا العرض.
                    </p>
                @endforelse

            </div>
        </div>

        <a href="{{ route('shows.index') }}" class="text-sm text-gray-300 hover:text-amber-300">
            ← رجوع لكل العروض
        </a>

    </section>

@endsection
