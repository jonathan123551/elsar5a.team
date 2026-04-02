@extends('layouts.app')

@section('title', 'تعديل موعد - ' . $show->title)

@section('content')

<section class="max-w-2xl mx-auto px-4 space-y-6">

```
{{-- Header --}}
<div class="space-y-1">
    <h1 class="text-xl md:text-2xl font-bold">تعديل موعد</h1>
    <p class="text-xs text-gray-400">🎭 {{ $show->title }}</p>
</div>

{{-- Errors --}}
@if ($errors->any())
    <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-xs rounded-xl p-3">
        <ul class="space-y-1">
            @foreach($errors->all() as $error)
                <li>• {{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.shows.times.update', [$show, $showTime]) }}"
      method="POST"
      class="space-y-5">
    @csrf
    @method('PUT')

    {{-- CARD --}}
    <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-4 shadow-xl shadow-black/40">

        {{-- DATE & TIME --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div>
                <label class="text-xs text-gray-400 mb-1 block">📅 التاريخ</label>
                <input type="date" name="date"
                       value="{{ old('date', $showTime->date->format('Y-m-d')) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:border-amber-400">
            </div>

            <div>
                <label class="text-xs text-gray-400 mb-1 block">⏰ الساعة</label>
                <input type="time" name="time"
                       value="{{ old('time', $showTime->time) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:border-amber-400">
            </div>

        </div>

        {{-- PRICE & TOTAL --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div>
                <label class="text-xs text-gray-400 mb-1 block">💰 سعر التذكرة</label>
                <input type="number" step="0.5" min="0" name="ticket_price"
                       value="{{ old('ticket_price', $showTime->ticket_price) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:border-amber-400">
            </div>

            <div>
                <label class="text-xs text-gray-400 mb-1 block">🎟️ إجمالي التذاكر</label>
                <input type="number" min="1" name="total_tickets"
                       value="{{ old('total_tickets', $showTime->total_tickets) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:border-amber-400">
            </div>

        </div>

        {{-- 🔥 PREMIUM SWITCH --}}
        <div class="flex items-center justify-between bg-white/5 border border-white/10 rounded-xl px-3 py-2">

            <span class="text-xs text-gray-300">الحالة</span>

            <label class="cursor-pointer">

                <input type="checkbox"
                       name="is_sold_out"
                       value="1"
                       class="sr-only peer"
                       {{ $showTime->is_sold_out ? 'checked' : '' }}>

                <div class="relative w-[110px] h-8 rounded-full px-2 flex items-center
                    transition-all duration-300
                    {{ $showTime->is_sold_out ? 'bg-red-500/20 border border-red-500/40' : 'bg-emerald-500/10 border border-emerald-500/40' }}">

                    <div class="absolute top-1 w-6 h-6 bg-white rounded-full transition-all
                        {{ $showTime->is_sold_out ? 'left-1' : 'left-[calc(100%-1.75rem)]' }}">
                    </div>

                    <span class="w-full text-center text-xs font-medium
                        {{ $showTime->is_sold_out ? 'text-red-200' : 'text-emerald-300' }}">
                        {{ $showTime->is_sold_out ? 'Sold Out' : 'متاح' }}
                    </span>

                </div>

            </label>

        </div>

    </div>

    {{-- ACTIONS --}}
    <div class="flex flex-col sm:flex-row gap-3">

        <a href="{{ route('admin.shows.times.index', $show) }}"
           class="flex-1 text-center text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            رجوع
        </a>

        <button type="submit"
                class="flex-1 px-4 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition shadow-lg shadow-amber-400/30">
            حفظ التعديلات
        </button>

    </div>

</form>
```

</section>

@endsection
