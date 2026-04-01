@extends('layouts.app')

@section('title', 'إدارة الحجوزات')

@section('content')
<section class="space-y-6">

    {{-- العنوان --}}
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">إدارة الحجوزات</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
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
                    $bookings->map(fn($b) => $b->showTime
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

    {{-- 💻 DESKTOP TABLE --}}
    <div class="hidden md:block">
        <div class="bg-black/40 border border-white/10 rounded-2xl overflow-x-auto">
            <table class="w-full text-sm text-gray-200">
                <thead class="bg-white/5 text-xs text-gray-400">
                <tr>
                    <th class="px-3 py-2 text-right">الضيف</th>
                    <th class="px-3 py-2 text-right">العرض / الموعد</th>
                    <th class="px-3 py-2 text-right">الحالة</th>
                    <th class="px-3 py-2 text-center">التذكرة</th>
                    <th class="px-3 py-2 text-right">إجراءات</th>
                    <th class="px-3 py-2 text-right">الكود</th>
                </tr>
                </thead>
                <tbody>
                @foreach($bookings as $booking)
                    @php
                        $dt = $booking->showTime
                            ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                            : '';
                    @endphp
                    <tr class="border-t border-white/5 booking-row"
                        data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                        data-status="{{ $booking->status }}"
                        data-datetime="{{ $dt }}">

                        <td class="px-3 py-2">
                            <div>
                                <p class="font-bold">{{ $booking->full_name }}</p>

                                <p class="text-xs text-amber-400">
                                    🎟️ {{ $booking->tickets_count }} تذكرة
                                </p>
                            </div>

                            <span class="text-gray-400 block mb-1">{{ $booking->phone }}</span>

                            {{-- 👇 عرض كل الأشخاص --}}
                            @foreach($booking->tickets as $ticket)
                                <div class="text-xs text-gray-400 mr-2">
                                    👤 {{ $ticket->name }} - 📱 {{ $ticket->phone }}
                                </div>
                            @endforeach
                        </td>

                        <td class="px-3 py-2">
                            {{ $booking->showTime->show->title ?? '-' }}<br>
                            <span class="text-gray-400">
                                {{ $booking->showTime?->date->format('d/m/Y') }}
                                • {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                            </span>
                        </td>

                        <td class="px-3 py-2">
                            <span class="px-2 py-1 rounded-full text-[11px]
                                {{ $booking->status==='approved' ? 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/40' :
                                   ($booking->status==='rejected' ? 'bg-red-500/15 text-red-200 border border-red-500/40' :
                                   'bg-sky-500/15 text-sky-200 border border-sky-500/40') }}">
                                {{ $booking->status }}
                            </span>
                        </td>

                       <td class="px-3 py-2 text-center">

    @php
        $allSent = $booking->tickets->every(fn($t) => $t->whatsapp_sent);
        $total   = $booking->tickets->count();
        $sent    = $booking->tickets->where('whatsapp_sent', true)->count();
    @endphp

    <div class="flex flex-col items-center gap-1">

        {{-- الدوت --}}
        <span class="inline-block w-3 h-3 rounded-full 
            {{ $allSent ? 'bg-emerald-400' : 'bg-red-500' }}">
        </span>

        {{-- عدد التذاكر --}}
        <span class="text-[10px] text-gray-400">
            {{ $sent }}/{{ $total }}
        </span>

    </div>

</td>

                        <td class="px-3 py-2">
                            <a href="{{ route('admin.bookings.show',$booking) }}"
                               class="px-2 py-1 rounded-full bg-white/10">تفاصيل</a>
                        </td>

                        <td class="px-3 py-2 font-mono text-xs">{{ $booking->reference_code }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- 📱 MOBILE CARDS --}}
    <div class="md:hidden space-y-3">
        @foreach($bookings as $booking)
            @php
                $dt = $booking->showTime
                    ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                    : '';
            @endphp

            <div class="bg-black/40 border border-white/10 rounded-2xl p-4 text-xs booking-card"
                 data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                 data-status="{{ $booking->status }}"
                 data-datetime="{{ $dt }}">

                <div class="flex justify-between mb-2">
                    <div>
                        <div class="font-semibold text-sm">{{ $booking->full_name }}</div>

                        <div class="text-amber-400 text-xs mb-1">
                            🎟️ {{ $booking->tickets_count }} تذكرة
                        </div>

                        <div class="text-gray-400">{{ $booking->phone }}</div>

                        {{-- 👇 الأشخاص --}}
                        @foreach($booking->tickets as $ticket)
                            <div class="text-[11px] text-gray-400">
                                👤 {{ $ticket->name }} - 📱 {{ $ticket->phone }}
                            </div>
                        @endforeach
                        <div class="text-gray-400">{{ $booking->phone }}</div>
                    </div>
                    <span class="font-mono bg-white/5 px-2 py-1 rounded">
                        {{ $booking->reference_code }}
                    </span>
                </div>

                <div class="mb-3">
                    🎭 {{ $booking->showTime->show->title ?? '-' }}<br>
                    <span class="text-gray-400">
                        🕒 {{ $booking->showTime?->date->format('d/m/Y') }}
                        • {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="px-2 py-1 rounded-full text-[11px]
                        {{ $booking->status==='approved' ? 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/40' :
                           ($booking->status==='rejected' ? 'bg-red-500/15 text-red-200 border border-red-500/40' :
                           'bg-sky-500/15 text-sky-200 border border-sky-500/40') }}">
                        {{ $booking->status }}
                    </span>

                     @php
                        $allSent = $booking->tickets->every(fn($t) => $t->whatsapp_sent);
                        $total   = $booking->tickets->count();
                        $sent    = $booking->tickets->where('whatsapp_sent', true)->count();
                    @endphp

                    <div class="flex items-center gap-2">

                        <span class="w-2.5 h-2.5 rounded-full 
                            {{ $allSent ? 'bg-emerald-400' : 'bg-red-500' }}">
                        </span>

                        <span class="text-gray-400 text-[11px]">
                            {{ $sent }}/{{ $total }}
                        </span>

                    </div>

                    <a href="{{ route('admin.bookings.show',$booking) }}"
                       class="px-3 py-1 rounded-full bg-white/10">
                        تفاصيل
                    </a>
                </div>
            </div>
        @endforeach
    </div>

</section>

{{-- JS FILTER --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('searchInput');
    const status = document.getElementById('statusFilter');
    const dt     = document.getElementById('dateTimeFilter');
    const items  = document.querySelectorAll('.booking-row, .booking-card');

    function filter(){
        const s = search.value.toLowerCase();
        items.forEach(el=>{
            const ok =
                el.dataset.search.includes(s) &&
                (!status.value || el.dataset.status===status.value) &&
                (!dt.value || el.dataset.datetime===dt.value);
            el.style.display = ok ? '' : 'none';
        });
    }

    [search,status,dt].forEach(i=>{
        i.addEventListener('input',filter);
        i.addEventListener('change',filter);
    });
});
</script>
@endsection

