@extends('layouts.app')

@section('title', 'تم إرسال طلب الحجز')

@section('content')
    <section class="max-w-lg mx-auto bg-black/40 border border-white/10 rounded-2xl p-6 text-center space-y-3">
        <h1 class="text-xl font-bold text-amber-300">تم إرسال طلب الحجز ✅</h1>
        <p class="text-sm text-gray-200">شكراً يا {{ $booking->full_name }}.</p>

        <div class="bg-white/5 rounded-xl p-3 text-sm text-gray-200 space-y-1">
            <p>رقم الحجز:
                <span class="font-mono text-amber-300">{{ $booking->reference_code }}</span>
            </p>
            <p>عدد التذاكر: {{ $booking->tickets_count }}</p>
            <p>إجمالي المبلغ: {{ $booking->total_price }} جنيه</p>
        </div>

        <p class="text-xs text-gray-400">
            هنراجع عملية الدفع من الأدمن.<br>
            بعد التأكيد هتتبعتلك تذكرتك كـ QR على رقم الواتساب اللي كتبته.
        </p>

        <a href="{{ route('shows.index') }}"
           class="inline-flex items-center justify-center mt-2 px-4 py-2 rounded-full bg-white/10 text-xs text-gray-200 hover:bg-white/20 transition">
            رجوع لصفحة العروض
        </a>
    </section>
@endsection
