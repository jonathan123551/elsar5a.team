@extends('layouts.app')

@section('title', 'إعدادات التحويلات')

@section('content')
<section class="max-w-lg mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">إعدادات التحويلات 💳</h1>
        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10">
            ← رجوع للوحة التحكم
        </a>
    </div>

    @if (session('status'))
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3 mb-2">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.payments.update') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs mb-1">رقم المحفظة (اختياري)</label>
            <input type="text" name="transfer_wallet" value="{{ old('transfer_wallet', $transferWallet) }}"
                   class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
            <p class="text-[11px] text-gray-400 mt-1">
                مثلاً: 0100xxxxxxx
            </p>
        </div>

        <div>
            <label class="block text-xs mb-1">حساب InstaPay (اختياري)</label>
            <input type="text" name="transfer_insta" value="{{ old('transfer_insta', $transferInsta) }}"
                   class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
            <p class="text-[11px] text-gray-400 mt-1">
                مثلاً: EGxxxxxxxxxx أو email@domain.com
            </p>
        </div>

        <button type="submit"
                class="mt-2 inline-flex items-center justify-center px-4 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition">
            حفظ الإعدادات
        </button>
    </form>
</section>
@endsection
