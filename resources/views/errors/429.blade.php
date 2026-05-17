@extends('layouts.app')

@section('title', 'محاولات كثيرة')

@section('content')

<section class="max-w-md mx-auto text-center space-y-5 my-10">
    <div class="scream-border">
        <div class="scream-card px-5 py-8 space-y-4">
            <div class="text-5xl">⏳</div>

            <h1 class="text-2xl font-extrabold text-amber-300">
                محاولات كثيرة
            </h1>

            <p class="text-sm text-gray-200 leading-relaxed">
                لقد قمت بعدد كبير من المحاولات في وقت قصير.
                <br>
                انتظر لحظة وحاول مرة أخرى.
            </p>

            <div class="pt-2">
                <a href="{{ url('/') }}"
                   class="inline-block px-5 py-2.5 rounded-full bg-amber-400 text-black text-sm font-semibold hover:bg-amber-300 transition">
                    ⬅️ الرئيسية
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
