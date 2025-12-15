@extends('layouts.app')

@section('title', 'إعداد صفحة عن الفريق')

@section('content')
<section class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">
            عن الفريق – إعداد المحتوى
        </h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع للوحة التحكم
        </a>
    </div>

    @if(session('status'))
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl px-3 py-2">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-xs rounded-xl px-3 py-2">
            <ul class="list-disc pr-4 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.about.update') }}" method="POST" class="space-y-4 text-sm">
        @csrf

        {{-- الوصف الرئيسي --}}
        <div>
            <label class="block text-xs mb-1">وصف عن الفريق</label>
            <textarea name="description" rows="6"
                      class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm
                             focus:outline-none focus:border-amber-400">{{ old('description', optional($about)->description) }}</textarea>
            <p class="text-[11px] text-gray-400 mt-1">
                النص اللي هيظهر في صفحة About قدام الجمهور.
            </p>
        </div>

        {{-- سنة التأسيس --}}
        <div class="grid md:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1">سنة تأسيس الفريق (اختياري)</label>
                <input type="number" name="founded_year"
                       value="{{ old('founded_year', optional($about)->founded_year) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm
                              focus:outline-none focus:border-amber-400">
            </div>
        </div>

        {{-- روابط السوشيال --}}
        <div class="grid md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs mb-1">رابط قناة YouTube</label>
                <input type="text" name="youtube"
                       value="{{ old('youtube', optional($about)->youtube) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-xs
                              focus:outline-none focus:border-amber-400"
                       placeholder="https://youtube.com/...">
            </div>

            <div>
                <label class="block text-xs mb-1">صفحة Facebook</label>
                <input type="text" name="facebook"
                       value="{{ old('facebook', optional($about)->facebook) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-xs
                              focus:outline-none focus:border-amber-400"
                       placeholder="https://facebook.com/...">
            </div>

            <div>
                <label class="block text-xs mb-1">حساب Instagram</label>
                <input type="text" name="instagram"
                       value="{{ old('instagram', optional($about)->instagram) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-xs
                              focus:outline-none focus:border-amber-400"
                       placeholder="https://instagram.com/...">
            </div>
        </div>

        <button type="submit"
                class="mt-2 inline-flex items-center justify-center px-4 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition">
            حفظ التعديلات
        </button>
    </form>
</section>
@endsection
