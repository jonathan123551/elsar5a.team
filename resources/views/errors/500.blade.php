@extends('layouts.app')

@section('title', 'حدث خطأ')

@section('content')

<section class="max-w-md mx-auto text-center space-y-5 my-10">
    <div class="scream-border">
        <div class="scream-card px-5 py-8 space-y-4">
            <div class="text-5xl">⚠️</div>

            <h1 class="text-2xl font-extrabold text-amber-300">
                حدث خطأ غير متوقع
            </h1>

            <p class="text-sm text-gray-200 leading-relaxed">
                نعتذر، حدث خطأ تقني أثناء معالجة طلبك.
                <br>
                فريقنا تم إخطاره — جرب مرة أخرى بعد قليل.
            </p>

            <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-2">
                <a href="{{ url('/') }}"
                   class="inline-block px-5 py-2.5 rounded-full bg-amber-400 text-black text-sm font-semibold hover:bg-amber-300 transition">
                    ⬅️ الرئيسية
                </a>
                <button onclick="history.back()"
                        class="inline-block px-5 py-2.5 rounded-full bg-white/5 border border-white/10 text-sm hover:bg-white/10 transition">
                    🔄 الرجوع
                </button>
            </div>
        </div>
    </div>
</section>

@endsection
