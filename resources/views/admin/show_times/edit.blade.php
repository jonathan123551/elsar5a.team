@extends('layouts.app')

@section('title', 'تعديل موعد - ' . $show->title)

@section('content')
<section class="max-w-2xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl font-bold">تعديل موعد</h1>
            <p class="text-[12px] text-gray-400 truncate">🎭 {{ $show->title }}</p>
        </div>

        <a href="{{ route('admin.shows.times.index', $show) }}"
           class="text-[12px] px-3 py-2 rounded-full bg-white/5 border border-white/10
                  hover:bg-white/10 active:bg-white/15 transition shrink-0">
            ← رجوع
        </a>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-[13px] rounded-xl p-3">
            <ul class="list-disc pe-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.shows.times.update', [$show, $showTime]) }}"
          method="POST"
          class="space-y-4 single-submit-form">
        @csrf
        @method('PUT')

        <div class="bg-black/40 border border-white/10 rounded-2xl p-4 sm:p-5 space-y-4
                    shadow-xl shadow-black/40">

            {{-- DATE & TIME --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs text-gray-300">📅 التاريخ</label>
                    <input type="date" name="date" required
                           value="{{ old('date', $showTime->date->format('Y-m-d')) }}"
                           class="w-full h-11 rounded-xl bg-black/70 border border-white/10 px-3 text-sm
                                  text-center focus:outline-none focus:border-amber-400
                                  focus:ring-1 focus:ring-amber-400/40 transition">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs text-gray-300">⏰ الساعة</label>
                    <input type="time" name="time" required
                           value="{{ old('time', $showTime->time) }}"
                           class="w-full h-11 rounded-xl bg-black/70 border border-white/10 px-3 text-sm
                                  text-center focus:outline-none focus:border-amber-400
                                  focus:ring-1 focus:ring-amber-400/40 transition">
                </div>
            </div>

            {{-- PRICE & TOTAL --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs text-gray-300">💰 سعر التذكرة</label>
                    <input type="number" step="0.5" min="0" name="ticket_price" required
                           inputmode="decimal"
                           value="{{ old('ticket_price', $showTime->ticket_price) }}"
                           class="w-full h-11 rounded-xl bg-black/70 border border-white/10 px-3 text-sm
                                  text-center text-amber-300 font-medium tabular-nums
                                  focus:outline-none focus:border-amber-400
                                  focus:ring-1 focus:ring-amber-400/40 transition">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs text-gray-300">🎟️ إجمالي التذاكر</label>
                    <input type="number" min="1" name="total_tickets" required
                           inputmode="numeric"
                           value="{{ old('total_tickets', $showTime->total_tickets) }}"
                           class="w-full h-11 rounded-xl bg-black/70 border border-white/10 px-3 text-sm
                                  text-center tabular-nums focus:outline-none focus:border-amber-400
                                  focus:ring-1 focus:ring-amber-400/40 transition">
                </div>
            </div>

            {{-- Sold-out toggle --}}
            <div class="flex items-center justify-between bg-white/5 border border-white/10 rounded-xl px-3 py-3">
                <span class="text-sm text-gray-200">الحالة</span>

                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox"
                           name="is_sold_out"
                           value="1"
                           class="sr-only peer"
                           {{ $showTime->is_sold_out ? 'checked' : '' }}>

                    <div class="w-14 h-8 rounded-full transition-colors duration-300
                                bg-emerald-500/25 peer-checked:bg-red-500/40
                                ring-1 ring-white/10"></div>

                    <div class="absolute start-1 top-1 w-6 h-6 bg-white rounded-full shadow
                                transition-transform duration-300
                                peer-checked:-translate-x-6 rtl:peer-checked:translate-x-6"></div>
                </label>
            </div>
        </div>

        {{-- Sticky submit --}}
        <div data-sticky-action class="pt-2">
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-2xl
                           bg-amber-400 text-black text-sm font-bold hover:bg-amber-300 active:bg-amber-500
                           transition disabled:opacity-60 disabled:cursor-progress
                           shadow-[0_8px_24px_rgba(251,191,36,0.25)]">
                <span class="btn-label">حفظ التعديلات</span>
                <span class="btn-spinner hidden" aria-hidden="true"></span>
            </button>
        </div>

    </form>
</section>

<script>
document.querySelectorAll('.single-submit-form').forEach(function (f) {
    f.addEventListener('submit', function () {
        requestAnimationFrame(function () {
            f.querySelectorAll('button[type=submit]').forEach(function (b) {
                if (b.disabled) return;
                b.disabled = true;
                b.classList.add('is-loading');
                var spin = b.querySelector('.btn-spinner');
                if (spin) spin.classList.remove('hidden');
            });
        });
    });
});
</script>
@endsection
