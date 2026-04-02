@extends('layouts.app')

@section('title', 'مواعيد العرض - ' . $show->title)

@section('content')

<section class="space-y-6">

{{-- Header --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold mb-1">مواعيد العرض</h1>
        <p class="text-xs text-gray-400">
            {{ $show->title }}
        </p>
    </div>

    <div class="flex items-center gap-2">
       <a href="{{ route('admin.shows.times.create', $show) }}"
           class="inline-flex items-center px-4 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition">
            + إضافة موعد جديد
        </a>
        <a href="{{ route('admin.shows.index') }}"
           class="text-xs px-3 py-1.5 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع لإدارة العروض
        </a>

        
    </div>
</div>

{{-- Success --}}
@if(session('status'))
    <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3">
        {{ session('status') }}
    </div>
@endif

{{-- Empty --}}
@if($times->isEmpty())
    <div class="text-sm text-gray-400 bg-black/40 border border-white/10 rounded-2xl p-4">
        لا توجد مواعيد لهذا العرض حتى الآن.
    </div>
@else

    {{-- 💻 DESKTOP TABLE --}}
    <div class="hidden md:block bg-black/40 border border-white/10 rounded-2xl">
        <div class="overflow-x-auto">
            <table class="min-w-[720px] w-full text-sm text-gray-200">

                <thead class="bg-white/5 text-xs uppercase text-gray-400">
                    <tr>
                        <th class="px-3 py-2 text-right">التاريخ</th>
                        <th class="px-3 py-2 text-right">الساعة</th>
                        <th class="px-3 py-2 text-right">السعر</th>
                        <th class="px-3 py-2 text-right">المتاح / الإجمالي</th>
                        <th class="px-3 py-2 text-right">الحالة</th>
                        <th class="px-3 py-2 text-right">إجراءات</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($times as $time)

                    @php
                        $reserved = $time->bookings()
                            ->whereIn('status', ['approved','pending'])
                            ->sum('tickets_count');

                        $remaining = max(0, $time->total_tickets - $reserved);
                        $fewTickets = $remaining > 0 && $remaining <= 10;
                    @endphp

                    <tr class="border-t border-white/5 hover:bg-white/5 transition">

                        <td class="px-3 py-2">
                            {{ $time->date->format('d/m/Y') }}
                        </td>

                        <td class="px-3 py-2">
                            {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                        </td>

                        <td class="px-3 py-2 text-amber-300 font-semibold">
                            {{ $time->ticket_price }} ج
                        </td>

                        <td class="px-3 py-2">
                            {{ $remaining }} / {{ $time->total_tickets }}
                        </td>

                        <td class="px-3 py-2">
                            @if($time->is_sold_out || $remaining <= 0)
                                <span class="px-2 py-1 rounded-full bg-red-500/20 text-red-200 border border-red-500/40">
                                    Sold Out
                                </span>
                            @elseif($fewTickets)
                                <span class="px-2 py-1 rounded-full bg-amber-400/10 text-amber-200 border border-amber-400/40">
                                    مقاعد محدودة
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-200 border border-emerald-500/40">
                                    متاح
                                </span>
                            @endif
                        </td>

                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.shows.times.edit', [$show, $time]) }}"
                                   class="px-2 py-1 rounded-full bg-white/10 hover:bg-white/20">
                                    تعديل
                                </a>

                                <form action="{{ route('admin.shows.times.destroy', [$show, $time]) }}"
                                      method="POST"
                                      onsubmit="return confirm('متأكد إنك عايز تحذف هذا الموعد؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-2 py-1 rounded-full bg-red-500/20 text-red-200 hover:bg-red-500/30">
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </td>

                    </tr>
                @endforeach
                </tbody>

            </table>
        </div>
    </div>

    {{-- 📱 MOBILE CARDS --}}
    <div class="md:hidden space-y-3">

    @foreach($times as $time)

        @php
            $reserved = $time->bookings()
                ->whereIn('status', ['approved','pending'])
                ->sum('tickets_count');

            $remaining = max(0, $time->total_tickets - $reserved);
            $fewTickets = $remaining > 0 && $remaining <= 10;
        @endphp

        <div class="bg-black/40 border border-white/10 rounded-xl p-3 space-y-3 shadow-md shadow-black/30">

            {{-- Header --}}
            <div class="flex justify-between text-xs">
                <span class="text-gray-400">
                    📅 {{ $time->date->format('d/m/Y') }}
                </span>

                <span class="text-amber-300 font-semibold">
                    {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                </span>
            </div>

            {{-- Info --}}
            <div class="grid grid-cols-3 text-center text-xs gap-2">

                <div>
                    <div class="text-gray-400">السعر</div>
                    <div class="text-amber-300 font-semibold">{{ $time->ticket_price }} ج</div>
                </div>

                <div>
                    <div class="text-gray-400">المتاح</div>
                    <div class="text-emerald-300">{{ $remaining }}</div>
                </div>

                <div>
                    <div class="text-gray-400">الإجمالي</div>
                    <div>{{ $time->total_tickets }}</div>
                </div>

            </div>

            {{-- Status --}}
            <div class="text-center">
                @if($time->is_sold_out || $remaining <= 0)
                    <span class="px-3 py-1 rounded-full bg-red-500/20 text-red-200 text-xs">
                        Sold Out
                    </span>
                @elseif($fewTickets)
                    <span class="px-3 py-1 rounded-full bg-amber-400/10 text-amber-200 text-xs">
                        مقاعد محدودة
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-200 text-xs">
                        متاح
                    </span>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">

                <a href="{{ route('admin.shows.times.edit', [$show, $time]) }}"
                   class="flex-1 text-center py-2 rounded-lg bg-white/10 text-xs">
                    تعديل
                </a>

                <form action="{{ route('admin.shows.times.destroy', [$show, $time]) }}"
                      method="POST"
                      class="flex-1"
                      onsubmit="return confirm('متأكد إنك عايز تحذف هذا الموعد؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full py-2 rounded-lg bg-red-500/20 text-red-200 text-xs">
                        حذف
                    </button>
                </form>

            </div>

        </div>

    @endforeach

    </div>

@endif


</section>

@endsection
