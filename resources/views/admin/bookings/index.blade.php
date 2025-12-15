@extends('layouts.app')

@section('title', 'إدارة الحجوزات')

@section('content')
<section class="space-y-6">

    {{-- العنوان + زر الرجوع للوحة التحكم --}}
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold mb-2">إدارة الحجوزات</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع للوحة التحكم
        </a>
    </div>

    {{-- رسائل الحالة --}}
    @if(session('status'))
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3">
            {{ session('status') }}
        </div>
    @endif

    {{-- فورم الفلترة والبحث --}}
    <form id="filters-form"
      method="GET"
      action="{{ route('admin.bookings.index') }}"
      class="bg-black/40 border border-white/10 rounded-2xl px-4 py-3 text-xs flex flex-wrap gap-3 items-end">

    <div class="flex flex-col">
        <label class="mb-1 text-gray-300">فلترة بالحالة</label>
        <select name="status"
                class="rounded-xl bg-black/60 border border-white/15 px-3 py-1.5 text-xs focus:outline-none focus:border-amber-400">
            <option value="">الكل</option>
            <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
    </div>

    <div class="flex flex-col flex-1 min-w-[180px]">
        <label class="mb-1 text-gray-300">بحث (اسم / موبايل / كود الحجز)</label>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="اكتب جزء من الاسم أو رقم الموبايل أو كود SRC..."
               class="rounded-xl bg-black/60 border border-white/15 px-3 py-1.5 text-xs focus:outline-none focus:border-amber-400">
    </div>

    {{-- سيبنا المكان ده فاضي، شيلنا الزراير خالص --}}
</form>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form        = document.getElementById('filters-form');
        if (!form) return;

        const statusField = form.querySelector('select[name="status"]');
        const searchField = form.querySelector('input[name="search"]');

        let searchTimeout = null;

        // أول ما تتغير الحالة → ابعت الفورم
        if (statusField) {
            statusField.addEventListener('change', function () {
                form.submit();
            });
        }

        // أول ما تكتب في البحث → استنى شوية صغيرين وبعدين ابعت
        if (searchField) {
            searchField.addEventListener('input', function () {
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }

                searchTimeout = setTimeout(function () {
                    form.submit();
                }, 400); // 0.4 ثانية بعد آخر حرف
            });
        }
    });
</script>


    {{-- الجدول --}}
    @if($bookings->isEmpty())
        <p class="text-sm text-gray-400">لا توجد حجوزات حسب الفلتر الحالي.</p>
    @else
     <p class="text-[10px] text-gray-500 md:hidden px-1">
            ‼️ تقدر تسحب الجدول يمين/شمال لو مش باين كله على الشاشة.
        </p>
        <div class="bg-black/40 border border-white/10 rounded-2xl overflow-x-auto">
            <table class="w-full text-sm text-gray-200">
                <thead class="bg-white/5 text-xs text-gray-400">
                    <tr>
                        <th class="px-3 py-2 text-right">رقم الحجز</th>
                        <th class="px-3 py-2 text-right">الضيف</th>
                        <th class="px-3 py-2 text-right">العرض / الموعد</th>
                        <th class="px-3 py-2 text-right">الحالة</th>
                        <th class="px-3 py-2 text-right">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($bookings as $booking)
                    <tr class="border-t border-white/5">
                        <td class="px-3 py-2 text-xs font-mono">
                            {{ $booking->reference_code }}
                        </td>

                        <td class="px-3 py-2 text-xs">
                            {{ $booking->full_name }}<br>
                            <span class="text-gray-400">{{ $booking->phone }}</span>
                        </td>

                        <td class="px-3 py-2 text-xs">
                            {{ $booking->showTime->show->title ?? '-' }}<br>
                            @if($booking->showTime)
                                <span class="text-gray-400">
                                    {{ $booking->showTime->date->format('d/m/Y') }}
                                    •
                                    {{ \Carbon\Carbon::parse($booking->showTime->time)->format('g:i A') }}
                                </span>
                            @endif
                        </td>

                       
                        <td class="px-3 py-2 text-xs">
                            @php $status = $booking->status; @endphp

                            @if($status === 'approved')
                                <span class="px-2 py-1 rounded-full bg-emerald-500/15 text-emerald-200 border border-emerald-500/40 text-[11px]">
                                    approved
                                </span>
                            @elseif($status === 'rejected')
                                <span class="px-2 py-1 rounded-full bg-red-500/15 text-red-200 border border-red-500/40 text-[11px]">
                                    rejected
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-full bg-sky-500/15 text-sky-200 border border-sky-500/40 text-[11px]">
                                    pending
                                </span>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-xs">
                            <a href="{{ route('admin.bookings.show', $booking) }}"
                               class="px-2 py-1 rounded-full bg-white/10 hover:bg-white/20">
                                تفاصيل
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
