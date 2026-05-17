@extends('layouts.app')

@section('title', 'الصفحة غير موجودة')

@section('content')

<section class="max-w-md mx-auto text-center space-y-5 my-10">
    <div class="scream-border scream-pulse">
        <div class="scream-card px-5 py-8 space-y-4">
            <div class="text-6xl">🎭</div>

            <h1 class="scream-title text-3xl font-extrabold text-amber-300">
                404
            </h1>

            <p class="text-sm text-gray-200 leading-relaxed">
                لقد ضاع المسرحُ في الظلام،
                <br>
                ولم نجد الصفحة التي تبحث عنها.
            </p>

            <p class="text-xs text-gray-400">
                ربما تم نقلها أو لم تعد متاحة.
            </p>

            <div class="pt-2">
                <a href="{{ url('/') }}"
                   class="inline-block px-5 py-2.5 rounded-full bg-amber-400 text-black text-sm font-semibold hover:bg-amber-300 transition">
                    ⬅️ العودة للرئيسية
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
