@extends('layouts.app')

@section('title', 'فتح وضع الفحص')

@section('content')

<section class="max-w-sm mx-auto my-8">
    <div class="scream-border">
        <div class="scream-card px-5 py-7 space-y-5 text-center">

            <div class="text-5xl">🔐</div>

            <div class="space-y-1">
                <h1 class="text-xl font-extrabold text-amber-300">
                    تفعيل وضع الفحص
                </h1>
                <p class="text-xs text-gray-300">
                    أدخل رمز المنظِّم على الباب لتفعيل هذا الجهاز للمسح.
                </p>
                <p class="text-[11px] text-gray-500">
                    التفعيل لمرة واحدة فقط على هذا الجهاز.
                </p>
            </div>

            @if ($errors->any())
                <div class="text-xs text-red-300 bg-red-500/10 border border-red-400/40 rounded-lg px-3 py-2">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('scanner.pin.submit') }}" class="space-y-3">
                @csrf

                <input type="password"
                       name="pin"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       autofocus
                       placeholder="●●●●"
                       class="w-full text-center tracking-[0.5em] text-lg font-bold rounded-xl
                              bg-black/60 border border-white/15 px-3 py-3
                              focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30">

                <button type="submit"
                        class="w-full px-4 py-3 rounded-full bg-amber-400 text-black text-sm font-bold
                               hover:bg-amber-300 active:scale-[0.98] transition">
                    تفعيل الفحص
                </button>
            </form>

            <p class="text-[10px] text-gray-500 leading-relaxed pt-1">
                للمنظِّمين فقط. لا تشارك الرمز خارج الفريق.
            </p>
        </div>
    </div>
</section>

@endsection
