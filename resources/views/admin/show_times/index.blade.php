@extends('layouts.app')

@section('title', 'مواعيد العرض - ' . $show->title)

@section('content')

<section class="space-y-6">

{{-- Header --}}

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold mb-1">مواعيد العرض</h1>
        <p class="text-xs text-gray-400">{{ $show->title }}</p>
    </div>


<div class="flex items-center gap-2">
    <a href="{{ route('admin.shows.times.create', $show) }}"
       class="inline-flex items-center px-4 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition">
        + إضافة موعد جديد
    </a>

    <a href="{{ route('admin.shows.index') }}"
       class="text-xs px-3 py-1.5 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
        ← رجوع
    </a>
</div>


</div>

{{-- Success --}}
@if(session('status')) <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3">
{{ session('status') }} </div>
@endif

@if($times->isEmpty()) <div class="text-sm text-gray-400 bg-black/40 border border-white/10 rounded-2xl p-4">
لا توجد مواعيد لهذا العرض حتى الآن. </div>
@else

{{-- 💻 DESKTOP --}}

<div class="hidden md:block bg-black/40 border border-white/10 rounded-2xl">
    <div class="overflow-x-auto">
       <table class="min-w-[720px] w-full text-sm text-gray-200 text-center">


        <thead class="bg-white/5 text-xs text-gray-400">
            <tr>
                <th class="px-3 py-2 text-center">التاريخ</th>
                <th class="px-3 py-2 text-center">الساعة</th>
                <th class="px-3 py-2 text-center">السعر</th>
                <th class="px-3 py-2 text-center">المتاح / الإجمالي</th>
                <th class="px-3 py-2 text-center">الحالة</th>
                <th class="px-3 py-2 text-center">إجراءات</th>
            </tr>
        </thead>

        <tbody>
        @foreach($times as $time)

            @php
                $reserved = $time->bookings()
                    ->whereIn('status', ['approved','pending'])
                    ->sum('tickets_count');

                $remaining = max(0, $time->total_tickets - $reserved);
            @endphp

            <tr class="border-t border-white/5 hover:bg-white/5 transition">

                <td class="px-3 py-2 text-center align-middle">{{ $time->date->format('d/m/Y') }}</td>

                <td class="px-3 py-2 text-center align-middle">
                    {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                </td>

                <td class="px-3 py-2 text-amber-300">
                    {{ $time->ticket_price }} ج
                </td>

                <td class="px-3 py-2 text-center align-middle">
                    {{ $remaining }} / {{ $time->total_tickets }}
                </td>

                {{-- 🔥 PREMIUM SWITCH --}}
                <td class="px-3 py-2 text-center">
                @php
                    $isLocked = $remaining <= 0;
                @endphp

                <form action="{{ route('admin.shows.times.toggle', [$show, $time]) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <label class="cursor-pointer block w-fit">

                        <input type="checkbox"
                            class="sr-only peer"
                            onchange="this.form.submit()"
                            {{ ($time->is_sold_out || $isLocked) ? 'checked' : '' }}
                            {{ $isLocked ? 'disabled' : '' }}>

                        <div class="relative flex items-center justify-between w-[120px] h-9 px-2 rounded-full
                            transition-all duration-300
                            {{ ($time->is_sold_out || $isLocked) ? 'bg-red-500/20 border border-red-500/40' : 'bg-emerald-500/10 border border-emerald-500/40' }}
                            {{ $isLocked ? 'opacity-60 cursor-not-allowed' : '' }}">

                            {{-- circle --}}
                            <div class="absolute top-1 w-7 h-7 bg-white rounded-full shadow-md
                                transition-all duration-300
                                {{ ($time->is_sold_out || $isLocked) ? 'left-1' : 'left-[calc(100%-2rem)]' }}">
                            </div>

                            {{-- text --}}
                            <span class="text-xs w-full text-center font-medium z-10
                                {{ ($time->is_sold_out || $isLocked) ? 'text-red-200' : 'text-emerald-300' }}">
                                {{ ($time->is_sold_out || $isLocked) ? 'Sold Out' : 'متاح' }}
                            </span>

                        </div>

                    </label>
                </form>

                </td>


                <td class="px-3 py-2 text-center align-middle">
                   <div class="flex justify-center items-center gap-2">
                        <a href="{{ route('admin.shows.times.edit', [$show, $time]) }}"
                           class="px-2 py-1 rounded-full bg-white/10">
                            تعديل
                        </a>

                        <form action="{{ route('admin.shows.times.destroy', [$show, $time]) }}"
                              method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="px-2 py-1 rounded-full bg-red-500/20 text-red-200">
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

{{-- 📱 MOBILE --}}

<div class="md:hidden space-y-3">

@foreach($times as $time)


@php
    $reserved = $time->bookings()
        ->whereIn('status', ['approved','pending'])
        ->sum('tickets_count');

    $remaining = max(0, $time->total_tickets - $reserved);
@endphp

<div class="bg-black/40 border border-white/10 rounded-xl p-3 space-y-3">

    <div class="flex justify-between text-xs">
        <span>{{ $time->date->format('d/m/Y') }}</span>
        <span class="text-amber-300">
            {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
        </span>
    </div>

    <div class="grid grid-cols-3 text-center text-xs">
        <div>
            <div class="text-gray-400">السعر</div>
            <div class="text-amber-300">{{ $time->ticket_price }} ج</div>
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

    {{-- 🔥 SWITCH MOBILE --}}
    @php
    $isLocked = $remaining <= 0;
@endphp

<form action="{{ route('admin.shows.times.toggle', [$show, $time]) }}" method="POST">
    @csrf
    @method('PATCH')

    <label class="cursor-pointer block w-full">

        <input type="checkbox"
               class="sr-only peer"
               onchange="this.form.submit()"
               {{ ($time->is_sold_out || $isLocked) ? 'checked' : '' }}
               {{ $isLocked ? 'disabled' : '' }}>

        <div class="relative flex items-center justify-between w-full h-10 px-3 rounded-full
            transition-all duration-300
            {{ ($time->is_sold_out || $isLocked) ? 'bg-red-500/20 border border-red-500/40' : 'bg-emerald-500/10 border border-emerald-500/40' }}
            {{ $isLocked ? 'opacity-60 cursor-not-allowed' : '' }}">

            <div class="absolute top-1 w-7 h-7 bg-white rounded-full shadow-md
                transition-all duration-300
                {{ ($time->is_sold_out || $isLocked) ? 'left-1' : 'left-[calc(100%-2rem)]' }}">
            </div>

            <span class="text-xs w-full text-center font-medium z-10
                {{ ($time->is_sold_out || $isLocked) ? 'text-red-200' : 'text-emerald-300' }}">
                {{ ($time->is_sold_out || $isLocked) ? 'Sold Out' : 'متاح' }}
            </span>

        </div>

    </label>
        </form>

   

    <div class="flex gap-2">
        <a href="{{ route('admin.shows.times.edit', [$show, $time]) }}"
           class="flex-1 text-center py-2 bg-white/10 rounded-lg text-xs">
            تعديل
        </a>

        <form action="{{ route('admin.shows.times.destroy', [$show, $time]) }}" method="POST" class="flex-1">
            @csrf
            @method('DELETE')
            <button class="w-full py-2 bg-red-500/20 text-red-200 rounded-lg text-xs">
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
