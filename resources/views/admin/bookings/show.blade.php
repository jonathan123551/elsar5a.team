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
                    @php $status = $booking->status; @endphp

                    @if($status === 'approved')
                        <span class="px-2 py-1 text-[11px] rounded-full bg-emerald-500/15 text-emerald-200 border border-emerald-500/40">
                            مقبول / تم الاعتماد
                        </span>
                    @elseif($status === 'rejected')
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
                    <p class="text-xs text-gray-400 mt-1">
                        <span class="text-gray-300">ملاحظات الأدمن:</span>
                        {{ $booking->admin_notes }}
                    </p>
                @endif
            </div>
        </div>

        {{-- صورة التحويل --}}
        @if($booking->transfer_screenshot_path)
            <div class="bg-black/40 border border-white/10 rounded-xl p-4 text-sm space-y-3">
                <h2 class="text-sm font-semibold">Screenshot التحويل</h2>
                <p class="text-xs text-gray-400">
                    افحص التحويل كويس قبل ما تقبل الحجز.
                </p>

                {{-- زر فتح الصورة في تاب جديدة --}}
                <a href="{{ asset('storage/' . $booking->transfer_screenshot_path) }}"
                   target="_blank"
                   class="inline-flex items-center text-[11px] px-3 py-1 rounded-full bg-white/5 border border-white/15 text-gray-200 hover:bg-white/10 mb-2">
                    فتح الصورة في تبويب جديد 🔍
                </a>

                {{-- عرض الصورة نفسها --}}
                <div class="border border-white/10 rounded-xl overflow-hidden max-h-[480px] bg-black">
                    <img src="{{ asset('storage/' . $booking->transfer_screenshot_path) }}"
                         alt="صورة التحويل"
                         class="w-full object-contain">
                </div>
            </div>
        @else
            <div class="bg-black/40 border border-amber-500/40 rounded-xl p-4 text-xs text-amber-200">
                ⚠️ لا توجد صورة تحويل مرفوعة مع هذا الحجز.
            </div>
        @endif

        {{-- تذكرة QR لو موجودة --}}
       @if($booking->qr_code_path)
    <div class="mt-4 text-center">
        <img
            src="{{ asset('storage/' . $booking->qr_code_path) }}"
            alt="QR Code"
            style="width:220px"
        >
        <p class="mt-2 text-sm text-gray-400">
            Reference: {{ $booking->reference_code }}
        </p>
    </div>
@endif



        {{-- أزرار اعتماد / رفض الحجز --}}
        <div class="flex flex-wrap items-center gap-3 text-sm">
            @if($booking->status === 'pending')
                <form action="{{ route('admin.bookings.approve', $booking) }}" method="POST"
                      onsubmit="return confirm('متأكد إنك عايز تقبل الحجز وتخصم التذاكر من الميعاد؟');">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 rounded-full bg-emerald-500 text-black font-medium hover:bg-emerald-400 transition">
                        ✅ اعتماد الحجز
                    </button>
                </form>

                <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST"
                      onsubmit="return confirm('متأكد إنك عايز ترفض هذا الحجز؟');">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 rounded-full bg-red-500/80 text-white font-medium hover:bg-red-500 transition">
                        ❌ رفض الحجز
                    </button>
                </form>
            @else
                <p class="text-xs text-gray-400">
                    لا يمكن تعديل هذا الحجز لأن حالته الآن:
                    <span class="font-semibold">
                        {{ $booking->status === 'approved' ? 'مقبول' : 'مرفوض' }}
                    </span>
                </p>
            @endif
        </div>

    </section>
@endsection
