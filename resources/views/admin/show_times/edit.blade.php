@extends('layouts.app')

@section('title', 'تعديل موعد - ' . $show->title)

@section('content')
    <section class="max-w-xl mx-auto space-y-4">
        <h1 class="text-2xl font-bold mb-1">تعديل موعد</h1>
        <p class="text-xs text-gray-400">للعرض: {{ $show->title }}</p>

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-xs rounded-xl p-3 mb-2">
                <ul class="list-disc pr-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.shows.times.update', [$show, $showTime]) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs mb-1">التاريخ</label>
                    <input type="date" name="date" value="{{ old('date', $showTime->date->format('Y-m-d')) }}"
                           class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
                </div>
                <div>
                    <label class="block text-xs mb-1">الساعة</label>
                    <input type="time" name="time" value="{{ old('time', $showTime->time) }}"
                           class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs mb-1">سعر التذكرة (جنيه)</label>
                    <input type="number" step="0.5" min="0" name="ticket_price" value="{{ old('ticket_price', $showTime->ticket_price) }}"
                           class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
                </div>
                <div>
                    <label class="block text-xs mb-1">إجمالي التذاكر</label>
                    <input type="number" min="1" name="total_tickets" value="{{ old('total_tickets', $showTime->total_tickets) }}"
                           class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
                </div>
            </div>

            <div>
                <label class="block text-xs mb-1">التذاكر المتاحة الآن</label>
                <input type="number" min="0" name="available_tickets" value="{{ old('available_tickets', $showTime->available_tickets) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-xs focus:outline-none focus:border-amber-400">
            </div>

            <div class="flex items-center gap-2 text-xs">
                <input type="checkbox" name="is_sold_out" id="is_sold_out" value="1" class="scale-90"
                       {{ $showTime->is_sold_out ? 'checked' : '' }}>
                <label for="is_sold_out">تحديد الموعد كـ Sold Out</label>
            </div>

            <div class="flex items-center justify-between gap-2">
                <a href="{{ route('admin.shows.times.index', $show) }}"
                   class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
                    إلغاء و رجوع للمواعيد
                </a>

            <button type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition">
                حفظ التعديلات
            </button>
            </div>
        </form>
    </section>
@endsection
