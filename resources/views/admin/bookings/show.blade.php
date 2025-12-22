@extends('layouts.app')

@section('title', 'تفاصيل الحجز #' . $booking->id)

@section('content')
<section class="space-y-6 max-w-3xl mx-auto">

    {{-- رسالة حالة عامة --}}
    @if(session('status'))
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3">
            {{ session('status') }}
        </div>
    @endif

    {{-- العنوان --}}
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold mb-1">
                تفاصيل الحجز #{{ $booking->id }}
            </h1>
            <p class="text-xs text-gray-400">
                للعرض:
                <span class="text-amber-300 font-semibold">
                    {{ $booking->showTime->show->title ?? 'عرض غير معروف' }}
                </span>
                –
                التاريخ:
                {{ optional($booking->showTime->date)->format('Y-m-d') }}
                •
                الساعة:
                {{ $booking->showTime
                    ? \Carbon\Carbon::parse($booking->showTime->time)->format('g:i A')
                    : '-' }}
            </p>
        </div>

        <a href="{{ route('admin.bookings.index') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع
        </a>
    </div>

    {{-- بيانات العميل + الحجز --}}
    <div class="grid md:grid-cols-2 gap-4 text-sm">
        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-2">
            <h2 class="text-sm font-semibold mb-1">بيانات العميل</h2>
            <p><span class="text-gray-400 text-xs">الاسم:</span> {{ $booking->full_name }}</p>
            <p><span class="text-gray-400 text-xs">رقم الموبايل:</span> {{ $booking->phone }}</p>
        </div>

        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-2">
            <h2 class="text-sm font-semibold mb-1">بيانات الحجز</h2>
            <p><span class="text-gray-400 text-xs">عدد التذاكر:</span> {{ $booking->tickets_count }}</p>

            <p>
                <span class="text-gray-400 text-xs">إجمالي السعر:</span>
                <span class="text-amber-300 font-semibold">{{ $booking->total_price }} جنيه</span>
            </p>

            <p>
                <span class="text-gray-400 text-xs">الحالة:</span>
                @if($booking->status === 'approved')
                    <span class="px-2 py-1 text-[11px] rounded-full bg-emerald-500/15 text-emerald-200 border border-emerald-500/40">
                        مقبول
                    </span>
                @elseif($booking->status === 'rejected')
                    <span class="px-2 py-1 text-[11px] rounded-full bg-red-500/15 text-red-200 border border-red-500/40">
                        مرفوض
                    </span>
                @else
                    <span class="px-2 py-1 text-[11px] rounded-full bg-sky-500/15 text-sky-200 border border-sky-500/40">
                        قيد المراجعة
                    </span>
                @endif
            </p>

            @if($booking->admin_notes)
                <p class="text-xs text-gray-400">
                    <span class="text-gray-300">ملاحظات:</span>
                    {{ $booking->admin_notes }}
                </p>
            @endif
        </div>
    </div>

    {{-- Screenshot التحويل --}}
    @if($booking->transfer_screenshot_path)
        @php
            $screenshot = str_starts_with($booking->transfer_screenshot_path, 'http')
                ? $booking->transfer_screenshot_path
                : asset('storage/' . $booking->transfer_screenshot_path);
        @endphp

        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-3">
            <h2 class="text-sm font-semibold">Screenshot التحويل</h2>

            <a href="{{ $screenshot }}"
               target="_blank"
               class="inline-flex text-[11px] px-3 py-1 rounded-full bg-white/5 border border-white/15 hover:bg-white/10">
                فتح الصورة في تبويب جديد 🔍
            </a>

            <div class="border border-white/10 rounded-xl overflow-hidden bg-black">
                <img
                    src="{{ $screenshot }}"
                    class="w-full h-auto object-contain"
                    alt="Transfer Screenshot">
            </div>
        </div>
    @endif

    {{-- 🎫 التذكرة + QR --}}
    @if($booking->qr_code_path)
        @php
            $qr = str_starts_with($booking->qr_code_path, 'http')
                ? $booking->qr_code_path
                : asset('storage/' . $booking->qr_code_path);
        @endphp

        <div class="mt-8 flex flex-col items-center gap-3">
            <div class="bg-black/40 border border-white/10 rounded-2xl p-4">
                <img
                    src="{{ $qr }}"
                    alt="Ticket QR"
                    class="max-w-full h-auto object-contain rounded-lg">
            </div>

            <p class="text-xs text-gray-400">
                Reference: {{ $booking->reference_code }}
            </p>
        </div>
    @endif

    {{-- أزرار اعتماد / رفض --}}
    <div class="flex gap-3">
        @if($booking->status === 'pending')
            <form action="{{ route('admin.bookings.approve', $booking) }}" method="POST">
                @csrf
                <button
                    type="submit"
                    class="px-4 py-2 rounded-full bg-emerald-500 text-black font-medium hover:bg-emerald-400">
                    ✅ اعتماد
                </button>
            </form>

            <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST">
                @csrf
                <button
                    type="submit"
                    class="px-4 py-2 rounded-full bg-red-500/80 text-white hover:bg-red-500">
                    ❌ رفض
                </button>
            </form>
        @endif
    </div>

</section>
@endsection
