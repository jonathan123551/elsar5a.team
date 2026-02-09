@extends('layouts.app')

@section('title', 'تم إرسال طلب الحجز')

@section('content')
<section class="max-w-lg mx-auto px-4">

    <div class="bg-black/50 border border-white/10 rounded-3xl p-6 sm:p-7 text-center space-y-5
                shadow-[0_0_40px_rgba(250,204,21,0.15)]">

        {{-- Icon --}}
        <div class="mx-auto w-14 h-14 flex items-center justify-center rounded-full
                    bg-amber-400/15 border border-amber-400/40 text-2xl">
            🎟️
        </div>

        {{-- Title --}}
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-amber-300">
                تم إرسال طلب الحجز بنجاح
            </h1>
            <p class="text-xs sm:text-sm text-gray-300 mt-1">
                شكراً يا <span class="font-semibold text-white">{{ $booking->full_name }}</span>
            </p>
        </div>

        {{-- Booking Info --}}
        <div class="bg-white/5 border border-white/10 rounded-2xl p-4 text-sm text-gray-200 space-y-2 text-left">

            <div class="flex justify-between">
                <span class="text-gray-400">رقم الحجز</span>
                <span class="font-mono text-amber-300">
                    {{ $booking->reference_code }}
                </span>
            </div>

            <div class="flex justify-between">
                <span class="text-gray-400">عدد التذاكر</span>
                <span>{{ $booking->tickets_count }}</span>
            </div>

            <div class="flex justify-between">
                <span class="text-gray-400">إجمالي المبلغ</span>
                <span class="font-semibold text-emerald-300">
                    {{ $booking->total_price }} جنيه
                </span>
            </div>
        </div>

        {{-- IMPORTANT NOTICE --}}
        <div class="bg-amber-500/10 border border-amber-400/40 rounded-2xl p-4 text-left space-y-2">

            <div class="flex items-center gap-2 text-amber-300 font-semibold text-sm">
                ⏳ الخطوة الجاية
            </div>

            <p class="text-xs sm:text-sm text-gray-200 leading-relaxed">
                دلوقتي <span class="text-white font-semibold">الأدمن هيقوم بمراجعة عملية الدفع</span>
                والتأكد من التحويل.
            </p>

            <p class="text-xs sm:text-sm text-gray-200 leading-relaxed">
                أول ما الحجز يتحول إلى
                <span class="text-emerald-300 font-semibold">Approved</span>،
                <br class="sm:hidden">
                <span class="text-white font-semibold">
                    هنبعتلك التذكرة كـ QR Code مباشرة على واتساب
                </span>
                على الرقم اللي سجلته.
            </p>
        </div>

        {{-- Footer Note --}}
        <p class="text-[11px] text-gray-400 leading-relaxed">
            لو في أي مشكلة في التحويل أو البيانات، هنتواصل معاك قبل رفض الطلب.
            <br>
            متقلقش، طلبك محفوظ على السيستم ✨
        </p>

        {{-- Action --}}
        <a href="{{ route('shows.index') }}"
           class="inline-flex items-center justify-center w-full sm:w-auto
                  px-5 py-2.5 rounded-full
                  bg-white/10 border border-white/20
                  text-xs sm:text-sm text-gray-200
                  hover:bg-white/20 hover:border-white/30 transition">
            الرجوع لصفحة العروض
        </a>

    </div>

</section>
@endsection
