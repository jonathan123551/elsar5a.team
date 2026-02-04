@extends('layouts.app')

@section('title', 'إدارة الحجوزات')

@section('content')
<section class="space-y-6">

    {{-- العنوان --}}
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold mb-2">إدارة الحجوزات</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع للوحة التحكم
        </a>
    </div>

    {{-- رسالة --}}
    @if(session('status'))
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3">
            {{ session('status') }}
        </div>
    @endif

    {{-- 🔥 الفلتر --}}
    <div class="bg-black/40 border border-white/10 rounded-2xl p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">

            <input
                type="text"
                id="searchInput"
                placeholder="بحث بالاسم / الموبايل / كود الحجز"
                class="rounded-xl bg-black/60 border border-white/15 px-3 py-2 focus:outline-none focus:border-amber-400"
            >

            <select id="statusFilter"
                class="rounded-xl bg-black/60 border border-white/15 px-3 py-2 focus:outline-none focus:border-amber-400">
                <option value="">كل الحالات</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>

            {{-- ✅ ميعاد العرض (تاريخ + ساعة) --}}
            <select id="dateTimeFilter"
                class="rounded-xl bg-black/60 border border-white/15 px-3 py-2 focus:outline-none focus:border-amber-400">
                <option value="">كل المواعيد</option>

                @foreach(
                    $bookings
                        ->map(fn($b) => $b->showTime
                            ? $b->showTime->date->format('Y-m-d').' '.$b->showTime->time
                            : null
                        )
                        ->filter()
                        ->unique()
                        ->sort()
                    as $datetime
                )
                    <option value="{{ $datetime }}">
                        {{ \Carbon\Carbon::parse($datetime)->format('d/m/Y • g:i A') }}
                    </option>
                @endforeach
            </select>

        </div>
    </div>

    {{-- الجدول --}}
    @if($bookings->isEmpty())
        <p class="text-sm text-gray-400">لا توجد حجوزات.</p>
    @else
        <div class="bg-black/40 border border-white/10 rounded-2xl overflow-x-auto">
            <table class="w-full text-sm text-gray-200">
                <thead class="bg-white/5 text-xs text-gray-400">
                    <tr>
                        
                        <th class="px-3 py-2 text-right">الضيف</th>
                        <th class="px-3 py-2 text-right">العرض / الموعد</th>
                        <th class="px-3 py-2 text-right">الحالة</th>
                        <th class="px-3 py-2 text-center">التذكرة</th>
                        <th class="px-3 py-2 text-right">إجراءات</th>
                        <th class="px-3 py-2 text-right">رقم الحجز</th>

                    </tr>
                </thead>
                <tbody>
                @foreach($bookings as $booking)
                    @php
                        $dateTimeValue = $booking->showTime
                            ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                            : '';
                    @endphp

                    <tr class="border-t border-white/5 booking-row"
                        data-status="{{ $booking->status }}"
                        data-datetime="{{ $dateTimeValue }}"
                        data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                    >
                        

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
                            @if($booking->status === 'approved')
                                <span class="px-2 py-1 rounded-full bg-emerald-500/15 text-emerald-200 border border-emerald-500/40 text-[11px]">
                                    approved
                                </span>
                            @elseif($booking->status === 'rejected')
                                <span class="px-2 py-1 rounded-full bg-red-500/15 text-red-200 border border-red-500/40 text-[11px]">
                                    rejected
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-full bg-sky-500/15 text-sky-200 border border-sky-500/40 text-[11px]">
                                    pending
                                </span>
                            @endif
                        </td>
                        
                        <td class="px-3 py-2 text-center">
                            @if($booking->whatsapp_sent)
                                {{-- 🟢 استلم التذكرة --}}
                                <span
                                    class="inline-block w-3 h-3 rounded-full bg-emerald-400"
                                    title="تم استلام التذكرة {{ optional($booking->whatsapp_sent_at)->format('d/m/Y h:i A') }}"
                                ></span>
                            @else
                                {{-- 🔴 لم يستلم --}}
                                <span
                                    class="inline-block w-3 h-3 rounded-full bg-red-500"
                                    title="لم يتم استلام التذكرة بعد"
                                ></span>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-xs">
                            <a href="{{ route('admin.bookings.show', $booking) }}"
                               class="px-2 py-1 rounded-full bg-white/10 hover:bg-white/20">
                                تفاصيل
                            </a>
                        </td>

                        <td class="px-3 py-2 text-xs font-mono">
                            {{ $booking->reference_code }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

{{-- ⚡ JS --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput   = document.getElementById('searchInput');
    const statusFilter  = document.getElementById('statusFilter');
    const dateTimeFilter = document.getElementById('dateTimeFilter');
    const rows          = document.querySelectorAll('.booking-row');

    function filterTable() {
        const search   = searchInput.value.toLowerCase();
        const status   = statusFilter.value;
        const datetime = dateTimeFilter.value;

        rows.forEach(row => {
            const matchSearch   = row.dataset.search.includes(search);
            const matchStatus   = !status || row.dataset.status === status;
            const matchDateTime = !datetime || row.dataset.datetime === datetime;

            row.style.display =
                (matchSearch && matchStatus && matchDateTime)
                    ? ''
                    : 'none';
        });
    }

    [searchInput, statusFilter, dateTimeFilter].forEach(el => {
        el.addEventListener('input', filterTable);
        el.addEventListener('change', filterTable);
    });
});
</script>
@endsection
