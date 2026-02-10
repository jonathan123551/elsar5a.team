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

           <ul class="space-y-2 text-xs sm:text-sm text-gray-200 leading-relaxed text-left">

    <li class="flex items-start gap-2">
        <span class="mt-1 text-amber-300">•</span>
        <span>
            يتم <span class="text-white font-semibold">مراجعة عملية الدفع</span>
            والتأكد من التحويل يدويًا مع الأدمن.
        </span>
    </li>

    <li class="flex items-start gap-2">
        <span class="mt-1 text-emerald-300">•</span>
        <span>
            بعد تأكيد الحجز وتحويل حالته إلى
            <span class="text-emerald-300 font-semibold">Approved</span>،
            سيتم إرسال <span class="text-white font-semibold">التذكرة </span>
            مباشرة على <span class="text-white font-semibold">رقم الواتساب المُسجَّل</span>.
        </span>
    </li>

    <li class="flex items-start gap-2">
        <span class="mt-1 text-sky-300">•</span>
        <span>
            عملية المراجعة قد تستغرق بحد أقصى
            <span class="text-white font-semibold">24 ساعة</span>
            من وقت إرسال طلب الحجز.
        </span>
    </li>

</ul>

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
