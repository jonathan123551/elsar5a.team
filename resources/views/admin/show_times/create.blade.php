@extends('layouts.app')

@section('title', 'إضافة موعد جديد - ' . $show->title)

@section('content')
<section class="max-w-xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl font-bold">إضافة موعد جديد</h1>
            <p class="text-[12px] text-gray-400 truncate">للعرض: {{ $show->title }}</p>
        </div>

        <a href="{{ route('admin.shows.times.index', $show) }}"
           class="text-[12px] px-3 py-2 rounded-full bg-white/5 border border-white/10
                  hover:bg-white/10 active:bg-white/15 transition shrink-0">
            ← رجوع
        </a>
    </div>

    @if ($errors->any())
        <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-[13px] rounded-xl p-3">
            <ul class="list-disc pe-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.shows.times.store', $show) }}" method="POST"
          class="space-y-4 single-submit-form">
        @csrf

        <div class="grid sm:grid-cols-2 gap-3 sm:gap-4">
            <div>
                <label class="block text-xs mb-1.5 text-gray-300">التاريخ</label>
                <input type="date" name="date" required
                       value="{{ old('date') }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2.5 text-sm
                              focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400/40 transition">
            </div>
            <div>
                <label class="block text-xs mb-1.5 text-gray-300">الساعة</label>
                <input type="time" name="time" required
                       value="{{ old('time') }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2.5 text-sm
                              focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400/40 transition">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-3 sm:gap-4">
            <div>
                <label class="block text-xs mb-1.5 text-gray-300">سعر التذكرة (جنيه)</label>
                <input type="number" step="0.5" min="0" name="ticket_price" required
                       inputmode="decimal"
                       value="{{ old('ticket_price') }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2.5 text-sm
                              focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400/40 transition">
            </div>
            <div>
                <label class="block text-xs mb-1.5 text-gray-300">إجمالي التذاكر</label>
                <input type="number" min="1" name="total_tickets" required
                       inputmode="numeric"
                       value="{{ old('total_tickets', 50) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2.5 text-sm
                              focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400/40 transition">
            </div>
        </div>

        <div>
            <label class="block text-xs mb-1.5 text-gray-300">التذاكر المتاحة الآن (اختياري)</label>
            <input type="number" min="0" name="available_tickets"
                   inputmode="numeric"
                   value="{{ old('available_tickets') }}"
                   placeholder="لو سيبته فاضي → هيبقى نفس إجمالي التذاكر"
                   class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2.5 text-[13px]
                          focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400/40 transition">
        </div>

        <label for="is_sold_out"
               class="flex items-center gap-2.5 text-sm bg-black/30 border border-white/10
                      rounded-xl px-3 py-2.5 cursor-pointer hover:bg-black/40 transition">
            <input type="checkbox" name="is_sold_out" id="is_sold_out" value="1"
                   {{ old('is_sold_out') ? 'checked' : '' }}
                   class="w-4 h-4 accent-amber-400">
            <span>تحديد الموعد كـ Sold Out من البداية</span>
        </label>

        {{-- Sticky submit --}}
        <div data-sticky-action class="pt-2">
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-2xl
                           bg-amber-400 text-black text-sm font-bold hover:bg-amber-300 active:bg-amber-500
                           transition disabled:opacity-60 disabled:cursor-progress
                           shadow-[0_8px_24px_rgba(251,191,36,0.25)]">
                <span class="btn-label">حفظ الموعد</span>
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
