@extends('layouts.app')

@section('title', 'تفاصيل الحجز #' . $booking->id)

@section('content')
<section class="space-y-6 max-w-3xl mx-auto px-3 sm:px-0">

    {{-- رسالة حالة --}}
    @if(session('status'))
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3 text-center">
            {{ session('status') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold mb-1">
                تفاصيل الحجز #{{ $booking->id }}
            </h1>

            <p class="text-[11px] sm:text-xs text-gray-400">
                {{ $booking->showTime->show->title ?? '-' }}
                • {{ optional($booking->showTime->date)->format('Y-m-d') }}
                • {{ $booking->showTime ? \Carbon\Carbon::parse($booking->showTime->time)->format('g:i A') : '-' }}
            </p>
        </div>

        <a href="{{ route('admin.bookings.index') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 text-center">
            رجوع ←
        </a>
    </div>

    {{-- 🔥 إعادة إرسال لكل التذاكر (فوق العميل) --}}
    @if($booking->status === 'approved')
        <div class="bg-blue-500/10 border border-blue-500/40 rounded-xl p-3 text-center">
            <form action="{{ route('admin.resend.ticket', $booking->tickets->first()->id) }}" method="POST">
                @csrf
                <button class="text-xs px-4 py-2 rounded-full bg-blue-500 text-white">
                    🔁 إعادة إرسال التذاكر
                </button>
            </form>
        </div>
    @endif

    {{-- بيانات --}}
    <div class="grid sm:grid-cols-2 gap-3 text-sm">

        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-2">
            <h2 class="text-xs font-semibold text-gray-300">العميل</h2>
            <p class="text-sm">{{ $booking->full_name }}</p>
            <p class="text-xs text-gray-400">{{ $booking->phone }}</p>
        </div>

        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-2">
            <h2 class="text-xs font-semibold text-gray-300">الحجز</h2>

            <p class="text-xs">
                عدد التذاكر:
                <span class="text-white font-semibold">{{ $booking->tickets_count }}</span>
            </p>

            <p class="text-xs">
                السعر:
                <span class="text-amber-300 font-semibold">{{ $booking->total_price }} جنيه</span>
            </p>

            <p class="text-xs">
                الحالة:
                @if($booking->status === 'approved')
                    <span class="text-emerald-400">✔ مقبول</span>
                @elseif($booking->status === 'rejected')
                    <span class="text-red-400">✖ مرفوض</span>
                @else
                    <span class="text-sky-400">⏳ pending</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Screenshot --}}
    @if($booking->transfer_screenshot_path)
        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-2">
            <h2 class="text-xs text-gray-300">Screenshot</h2>

            <img src="{{ $booking->transfer_screenshot_path }}"
                 class="w-full rounded-xl border border-white/10">
        </div>
    @endif

    {{-- 🎟️ التذاكر --}}
    <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-3">
        <h2 class="text-sm font-semibold text-amber-300">التذاكر</h2>

        <div class="space-y-3">

            @foreach($booking->tickets as $ticket)
                <div class="bg-white/5 border border-white/10 rounded-xl p-3 flex flex-col gap-2">

                    <div class="flex items-center justify-between">

                        <div>
                            <p class="text-sm font-semibold text-white">
                                {{ $ticket->name }}
                            </p>

                            <p class="text-xs text-gray-400">
                                {{ $ticket->phone }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full 
                                {{ $ticket->whatsapp_sent ? 'bg-emerald-500' : 'bg-red-500' }}">
                            </span>

                            <span class="text-[10px] 
                                {{ $ticket->whatsapp_sent ? 'text-emerald-300' : 'text-red-300' }}">
                                {{ $ticket->whatsapp_sent ? 'تم الاستلام' : 'لم يستلم' }}
                            </span>
                        </div>
                    </div>

                    {{-- 🔥 يظهر بس لو approved --}}
                    @if($booking->status === 'approved')
                        <div class="flex flex-wrap gap-2">

                            @if($ticket->qr_image_path)
                                <a href="{{ $ticket->qr_image_path }}"
                                   target="_blank"
                                   class="text-[10px] px-3 py-1 rounded-full bg-white/5 border border-white/10 hover:bg-white/10">
                                    عرض 🎫
                                </a>
                            @endif

                            <form action="{{ route('admin.resend.ticket', $ticket->id) }}" method="POST">
                                @csrf
                                <button class="text-[10px] px-3 py-1 rounded-full bg-blue-500 text-white">
                                    إعادة إرسال
                                </button>
                            </form>

                        </div>
                    @endif

                </div>
            @endforeach

        </div>
    </div>

    {{-- Buttons --}}
    <div class="flex gap-3 justify-center">
        @if($booking->status === 'pending')

            <form action="{{ route('admin.bookings.approve', $booking) }}" method="POST">
                @csrf
                <button class="px-4 py-2 rounded-full bg-emerald-500 text-black text-sm">
                    اعتماد
                </button>
            </form>

            <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST">
                @csrf
                <button class="px-4 py-2 rounded-full bg-red-500 text-white text-sm">
                    رفض
                </button>
            </form>

        @endif
    </div>

</section>
@endsection