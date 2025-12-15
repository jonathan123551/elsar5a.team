@extends('layouts.app')

@section('title', 'تفاصيل الحجز #' . $booking->id)

@section('content')
<section class="space-y-6 max-w-3xl mx-auto">

    {{-- رسالة حالة --}}
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
                {{ $booking->showTime ? \Carbon\Carbon::parse($booking->showTime->time)->format('g:i A') : '-' }}
            </p>
        </div>

        <a href="{{ route('admin.bookings.index') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع لقائمة الحجوزات
        </a>
    </div>

    {{-- بيانات العميل والحجز --}}
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
                <span class="text-gray-400 text-xs">الإجمالي:</span>
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

    {{-- صورة التحويل --}}
    @if($booking->transfer_screenshot_path)
        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-3 text-sm">
            <h2 class="text-sm font-semibold">صورة التحويل</h2>
            <a href="{{ asset('storage/'.$booking->transfer_screenshot_path) }}" target="_blank"
               class="text-xs underline text-amber-300">
                فتح الصورة
            </a>
            <img src="{{ asset('storage/'.$booking->transfer_screenshot_path) }}"
                 class="rounded-xl border border-white/10 max-h-[400px] mx-auto">
        </div>
    @endif

    {{-- QR Ticket --}}
    @if($booking->qr_code_path)
        <div class="bg-black/40 border border-emerald-400/40 rounded-xl p-4 space-y-3 text-sm">
            <h2 class="text-sm font-semibold text-emerald-300">تذكرة QR</h2>
            <div class="flex justify-center">
                <img src="{{ asset('storage/'.$booking->qr_code_path) }}"
                     class="w-48 h-48 bg-white p-3 rounded-xl">
            </div>
        </div>
    @endif

    {{-- أزرار التحكم --}}
    <div class="flex gap-3">
        @if($booking->status === 'pending')

            <form method="POST"
                  action="{{ route('admin.bookings.approve', $booking->id) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('تأكيد اعتماد الحجز؟')"
                        class="px-4 py-2 rounded-full bg-emerald-500 text-black font-medium">
                    ✅ اعتماد الحجز
                </button>
            </form>

            <form method="POST"
                  action="{{ route('admin.bookings.reject', $booking->id) }}">
                @csrf
                <input type="hidden" name="admin_notes" value="تم الرفض من الإدارة">
                <button type="submit"
                        onclick="return confirm('تأكيد رفض الحجز؟')"
                        class="px-4 py-2 rounded-full bg-red-600 text-white font-medium">
                    ❌ رفض الحجز
                </button>
            </form>

        @else
            <p class="text-xs text-gray-400">
                هذا الحجز تم التعامل معه بالفعل.
            </p>
        @endif
    </div>

</section>
@endsection
