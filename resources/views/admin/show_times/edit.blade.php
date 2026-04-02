@extends('layouts.app')

@section('title', 'تعديل موعد - ' . $show->title)

@section('content')

<section class="max-w-2xl mx-auto px-4 space-y-6">

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

<div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-5 shadow-xl shadow-black/40">


{{-- DATE & TIME --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

    <div class="flex flex-col gap-1">
        <label class="text-xs text-gray-400">📅 التاريخ</label>
        <input type="date" name="date"
               value="{{ old('date', $showTime->date->format('Y-m-d')) }}"
               class="w-full h-11 rounded-xl bg-black/70 border border-white/10 px-3 text-sm text-center focus:border-amber-400 appearance-none">
    </div>

    <div class="flex flex-col gap-1">
        <label class="text-xs text-gray-400">⏰ الساعة</label>
        <input type="time" name="time"
               value="{{ old('time', $showTime->time) }}"
               class="w-full h-11 rounded-xl bg-black/70 border border-white/10 px-3 text-sm text-center focus:border-amber-400 appearance-none">
    </div>

</div>

{{-- PRICE & TOTAL --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

   <div class="flex flex-col gap-1">
    <label class="text-xs text-gray-400">💰 سعر التذكرة</label>

    <input type="number" step="0.5" min="0" name="ticket_price"
           value="{{ old('ticket_price', $showTime->ticket_price) }}"
           class="w-full h-11 rounded-xl bg-black/70 border border-white/10 px-3 text-sm text-center text-amber-400 focus:border-amber-400">
</div>

    <div class="flex flex-col gap-1">
        <label class="text-xs text-gray-400">🎟️ إجمالي التذاكر</label>
        <input type="number" min="1" name="total_tickets"
               value="{{ old('total_tickets', $showTime->total_tickets) }}"
               class="w-full h-11 rounded-xl bg-black/70 border border-white/10 px-3 text-sm text-center focus:border-amber-400">
    </div>

</div>

{{-- 🔥 PREMIUM SWITCH (FIXED + ANIMATED) --}}
<div class="flex items-center justify-between bg-white/5 border border-white/10 rounded-xl px-3 py-3">

<span class="text-xs text-gray-300">الحالة</span>

<label class="relative inline-flex items-center cursor-pointer">

    <input type="checkbox"
           name="is_sold_out"
           value="1"
           class="sr-only peer"
           {{ $showTime->is_sold_out ? 'checked' : '' }}>

    {{-- background --}}
    <div class="w-14 h-8 rounded-full transition-all duration-300
        bg-emerald-500/20 peer-checked:bg-red-500/30">
    </div>

    {{-- circle --}}
    <div class="absolute left-1 top-1 w-6 h-6 bg-white rounded-full
        transition-all duration-300
        peer-checked:translate-x-6">
    </div>

</label>


</div>


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


</section>

@endsection
