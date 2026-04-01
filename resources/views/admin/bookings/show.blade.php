@extends('layouts.app')

@section('title', 'تفاصيل الحجز #' . $booking->id)

@section('content')
<section class="space-y-6 max-w-4xl mx-auto px-3 sm:px-0">

    {{-- رسالة --}}
    @if(session('status'))
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3 text-center">
            {{ session('status') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex justify-between items-center">
        <h1 class="text-xl font-bold">تفاصيل الحجز #{{ $booking->id }}</h1>

        <a href="{{ route('admin.bookings.index') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10">
            رجوع
        </a>
    </div>

    {{-- GRID --}}
    <div class="grid sm:grid-cols-2 gap-4">

        {{-- 🎟️ التذاكر --}}
        <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-3 shadow-lg flex flex-col">

            <h2 class="text-sm text-amber-300 font-semibold">🎟️ التذاكر</h2>

            <div class="space-y-3 max-h-[500px] overflow-auto">

                @foreach($booking->tickets as $ticket)
                    <div class="bg-white/5 border border-white/10 rounded-xl p-3 hover:bg-white/10 transition">

                        <div class="flex justify-between items-center">

                            <div>
                                <p class="text-white font-semibold">{{ $ticket->name }}</p>
                                <p class="text-xs text-gray-400">{{ $ticket->phone }}</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full 
                                    {{ $ticket->whatsapp_sent ? 'bg-green-500' : 'bg-red-500' }}"></span>

                                <span class="text-[10px] 
                                    {{ $ticket->whatsapp_sent ? 'text-green-300' : 'text-red-300' }}">
                                    {{ $ticket->whatsapp_sent ? 'تم الاستلام' : 'لم يستلم' }}
                                </span>
                            </div>
                        </div>

                        @if($booking->status === 'approved')
                            <div class="flex gap-2 mt-2">

                                @if($ticket->qr_image_path)
                                    <a href="{{ $ticket->qr_image_path }}"
                                       target="_blank"
                                       class="text-[10px] px-3 py-1 bg-white/10 rounded-full">
                                        عرض 🎫
                                    </a>
                                @endif

                                <form action="{{ route('admin.resend.ticket', $ticket->id) }}" method="POST">
                                    @csrf
                                    <button class="text-[10px] px-3 py-1 bg-blue-500 rounded-full text-white">
                                        إعادة إرسال
                                    </button>
                                </form>

                            </div>
                        @endif

                    </div>
                @endforeach

            </div>
        </div>

        {{-- 📊 الحجز --}}
        <div class="bg-black/40 border border-white/10 rounded-2xl p-5 shadow-lg">

            <h2 class="text-sm text-gray-300 mb-3">📊 الحجز</h2>

            <div class="space-y-2 text-xs">

                <div class="flex justify-between">
                    <span>عدد التذاكر</span>
                    <span>{{ $booking->tickets_count }}</span>
                </div>

                <div class="flex justify-between">
                    <span>السعر</span>
                    <span class="text-amber-300">{{ $booking->total_price }} جنيه</span>
                </div>

                <div class="flex justify-between">
                    <span>الحالة</span>

                    @if($booking->status === 'approved')
                        <span class="text-green-400">✔ مقبول</span>
                    @elseif($booking->status === 'rejected')
                        <span class="text-red-400">✖ مرفوض</span>
                    @else
                        <span class="text-sky-400">⏳ pending</span>
                    @endif
                </div>

            </div>

        </div>

    </div>

    {{-- Screenshot --}}
    @if($booking->transfer_screenshot_path)
        <div class="bg-black/40 border border-white/10 rounded-xl p-4">
            <img src="{{ $booking->transfer_screenshot_path }}" class="w-full rounded-xl">
        </div>
    @endif

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
    {{-- 🔥 DELETE BUTTON (يظهر بس لو approved) --}}
    @if($booking->status === 'approved')
        <div class="text-center mt-6">

            <form action="{{ route('admin.booking.delete', $booking->id) }}" method="POST"
                  onsubmit="return confirm('متأكد عايز تمسح الحجز بكل التذاكر؟');">
                @csrf
                @method('DELETE')

                <button class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-full text-sm">
                    🗑️ حذف الحجز بالكامل
                </button>
            </form>

        </div>
    @endif

</section>
@endsection