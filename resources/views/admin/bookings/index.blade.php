@extends('layouts.app')

@section('title', 'إدارة الحجوزات')

@section('content')
<section class="space-y-6">

    {{-- العنوان --}}
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">إدارة الحجوزات</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10">
            ← رجوع
        </a>
    </div>

    {{-- رسالة --}}
    @if(session('status'))
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3">
            {{ session('status') }}
        </div>
    @endif

    {{-- الفلتر --}}
    <div class="bg-black/40 border border-white/10 rounded-2xl p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
            <input id="searchInput" type="text"
                   placeholder="بحث بالاسم / الموبايل / كود الحجز"
                   class="rounded-xl bg-black/60 border border-white/15 px-3 py-2">

            <select id="statusFilter"
                    class="rounded-xl bg-black/60 border border-white/15 px-3 py-2">
                <option value="">كل الحالات</option>
                <option value="pending">pending</option>
                <option value="approved">approved</option>
                <option value="rejected">rejected</option>
            </select>

            <select id="dateTimeFilter"
                    class="rounded-xl bg-black/60 border border-white/15 px-3 py-2">
                <option value="">كل المواعيد</option>
                @foreach(
                    $bookings->map(fn($b)=>$b->showTime
                        ? $b->showTime->date->format('Y-m-d').' '.$b->showTime->time
                        : null)->filter()->unique()->sort()
                    as $dt
                )
                    <option value="{{ $dt }}">
                        {{ \Carbon\Carbon::parse($dt)->format('d/m/Y • g:i A') }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- الجدول --}}
    <div class="bg-black/40 border border-white/10 rounded-2xl overflow-hidden">
        <table class="w-full text-sm text-gray-200 responsive-table">
            <thead class="bg-white/5 text-xs text-gray-400">
            <tr>
                <th>الضيف</th>
                <th>العرض / الموعد</th>
                <th>الحالة</th>
                <th>التذكرة</th>
                <th>إجراءات</th>
                <th>رقم الحجز</th>
            </tr>
            </thead>
            <tbody>
            @foreach($bookings as $booking)
                @php
                    $dt = $booking->showTime
                        ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                        : '';
                @endphp

                <tr class="booking-row"
                    data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                    data-status="{{ $booking->status }}"
                    data-datetime="{{ $dt }}">

                    <td data-label="الضيف">
                        <strong>{{ $booking->full_name }}</strong><br>
                        <span class="text-gray-400">{{ $booking->phone }}</span>
                    </td>

                    <td data-label="العرض / الموعد">
                        {{ $booking->showTime->show->title ?? '-' }}<br>
                        <span class="text-gray-400">
                            {{ $booking->showTime?->date->format('d/m/Y') }}
                            • {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                        </span>
                    </td>

                    <td data-label="الحالة">
                        <span class="px-2 py-1 rounded-full text-[11px]
                        {{ $booking->status==='approved' ? 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/40' :
                           ($booking->status==='rejected' ? 'bg-red-500/15 text-red-200 border border-red-500/40' :
                           'bg-sky-500/15 text-sky-200 border border-sky-500/40') }}">
                            {{ $booking->status }}
                        </span>
                    </td>

                    <td data-label="التذكرة">
                        <span class="inline-block w-3 h-3 rounded-full {{ $booking->whatsapp_sent ? 'bg-emerald-400' : 'bg-red-500' }}"></span>
                    </td>

                    <td data-label="إجراءات">
                        <a href="{{ route('admin.bookings.show',$booking) }}"
                           class="px-2 py-1 rounded-full bg-white/10">
                            تفاصيل
                        </a>
                    </td>

                    <td data-label="رقم الحجز" class="font-mono">
                        {{ $booking->reference_code }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

</section>

{{-- FILTER --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('searchInput');
    const status = document.getElementById('statusFilter');
    const dt     = document.getElementById('dateTimeFilter');
    const rows   = document.querySelectorAll('.booking-row');

    function filter(){
        const s = search.value.toLowerCase();
        rows.forEach(r=>{
            const ok =
                r.dataset.search.includes(s) &&
                (!status.value || r.dataset.status===status.value) &&
                (!dt.value || r.dataset.datetime===dt.value);
            r.style.display = ok ? '' : 'none';
        });
    }

    [search,status,dt].forEach(i=>{
        i.addEventListener('input',filter);
        i.addEventListener('change',filter);
    });
});
</script>

{{-- 📱 Responsive magic --}}
<style>
@media (max-width: 768px) {
    .responsive-table thead {
        display: none;
    }

    .responsive-table tr {
        display: block;
        border-bottom: 1px solid rgba(255,255,255,.1);
        padding: 12px;
        margin-bottom: 10px;
    }

    .responsive-table td {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        font-size: 12px;
    }

    .responsive-table td::before {
        content: attr(data-label);
        color: #9ca3af;
        font-weight: 500;
    }
}
</style>
@endsection
