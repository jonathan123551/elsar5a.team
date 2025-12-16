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
                @if($booking->showTime)
                    {{ \Carbon\Carbon::parse($booking->showTime->time)->format('g:i A') }}
                @else
                    -
                @endif
            </p>
        </div>

        <a href="{{ route('admin.bookings.index') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع لقائمة الحجوزات
        </a>
    </div>

    {{-- بيانات العميل + الحجز --}}
    <div class="grid md:grid-cols-2 gap-4 text-sm">
        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-2">
            <h2 class="text-sm font-semibold mb-1">بيانات العميل</h2>
            <p><span class="text-gray-400 text-xs">الاسم:</span> {{ $booking->full_name }}</p>
            <p><span class="text-gray-400 text-xs">رقم الموبايل / واتساب:</span> {{ $booking->phone }}</p>
        </div>

        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-2">
            <h2 class="text-sm font-semibold mb-1">بيانات الحجز</h2>
            <p><span class="text-gray-400 text-xs">عدد التذاكر:</span> {{ $booking->tickets_count }}</p>
            <p>
                <span class="text-gray-400 text-xs">إجمالي السعر:</span>
                <span class="text-amber-300 font-semibold">{{ $booking->total_price }} جنيه</span>
            </p>

            <p>
                <span class="text-gray-400 text-xs">الحالة الحالية:</span>
                @if($booking->status === 'approved')
                    <span class="px-2 py-1 text-[11px] rounded-full bg-emerald-500/15 text-emerald-200 border border-emerald-500/40">
                        مقبول / تم الاعتماد
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
                    <span class="text-gray-300">ملاحظات الأدمن:</span>
                    {{ $booking->admin_notes }}
                </p>
            @endif
        </div>
    </div>

    {{-- Screenshot التحويل --}}
    @if($booking->transfer_screenshot_path)
        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-3">
            <h2 class="text-sm font-semibold">Screenshot التحويل</h2>

            <a href="{{ asset('storage/' . $booking->transfer_screenshot_path) }}"
               target="_blank"
               class="inline-flex text-[11px] px-3 py-1 rounded-full bg-white/5 border border-white/15 hover:bg-white/10">
                فتح الصورة في تبويب جديد 🔍
            </a>

            <div class="border border-white/10 rounded-xl overflow-hidden bg-black">
                <img src="{{ asset('storage/' . $booking->transfer_screenshot_path) }}"
                     class="w-full object-contain">
            </div>
        </div>
    @endif

    {{-- 🎟️ QR Ticket --}}
    @if($booking->qr_code_path)
        <div class="flex justify-center mt-6">
            <div class="qr-card">
                <img
                    src="{{ asset('storage/' . $booking->qr_code_path) }}"
                    alt="QR Ticket"
                    class="qr-img"
                >

                <div class="qr-ref">
                    Reference: {{ $booking->reference_code }}
                </div>
            </div>
        </div>
    @endif

    {{-- أزرار اعتماد / رفض --}}
    <div class="flex gap-3 text-sm">
        @if($booking->status === 'pending')
            <form method="POST" action="{{ route('admin.bookings.approve', $booking) }}">
                @csrf
                <button class="px-4 py-2 rounded-full bg-emerald-500 text-black font-medium">
                    ✅ اعتماد الحجز
                </button>
            </form>

            <form method="POST" action="{{ route('admin.bookings.reject', $booking) }}">
                @csrf
                <button class="px-4 py-2 rounded-full bg-red-500 text-white font-medium">
                    ❌ رفض الحجز
                </button>
            </form>
        @endif
    </div>

</section>

{{-- ستايل QR --}}
<style>
.qr-card {
    background: linear-gradient(145deg, #020617, #0f172a);
    padding: 16px;
    border-radius: 16px;
    box-shadow: 0 12px 30px rgba(0,0,0,.5);
    border: 1px solid rgba(255,255,255,.08);
    text-align: center;
}

.qr-img {
    width: 180px;
    height: 180px;
    background: white;
    padding: 8px;
    border-radius: 12px;
}

.qr-ref {
    margin-top: 10px;
    font-size: 12px;
    color: #cbd5f5;
    letter-spacing: .4px;
}
</style>
@endsection
