@extends('layouts.app')

@section('title', 'تسجيل دخول الأدمن')

@section('content')
    <section class="max-w-sm mx-auto space-y-4 bg-black/40 border border-white/10 rounded-xl p-6 mt-10">
        <h2 class="font-bold text-xl mb-2 text-center">دخول الأدمن</h2>

        @if ($errors->any())
            <div class="text-red-400 text-xs mb-2">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST" class="space-y-3">
            @csrf

            <div>
                <label class="text-xs mb-1 block">البريد الإلكتروني</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="w-full bg-black/60 border border-white/15 rounded-lg px-2 py-2 text-sm focus:outline-none focus:border-amber-400">
            </div>

            <div>
                <label class="text-xs mb-1 block">كلمة المرور</label>
                <input type="password" name="password"
                       class="w-full bg-black/60 border border-white/15 rounded-lg px-2 py-2 text-sm focus:outline-none focus:border-amber-400">
            </div>

            <button
                class="w-full bg-amber-400 text-black rounded-full py-2 text-sm font-medium hover:bg-amber-300 transition mt-2">
                دخول
            </button>
        </form>
    </section>
@endsection
