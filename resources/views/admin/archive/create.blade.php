@extends('layouts.app')

@section('title', 'إضافة عرض سابق')

@section('content')
<section class="max-w-xl mx-auto space-y-6">

    <h1 class="text-2xl font-bold">➕ إضافة عرض سابق</h1>

    <form method="POST"
          action="{{ route('admin.archive.store') }}"
          enctype="multipart/form-data"
          class="space-y-4 bg-black/40 p-5 rounded-xl border border-white/10">
        @csrf

        {{-- Title --}}
        <input
            type="text"
            name="title"
            value="{{ old('title') }}"
            placeholder="اسم العرض"
            required
            class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

        {{-- Description --}}
        <textarea
            name="description"
            rows="4"
            placeholder="وصف العرض"
            class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">{{ old('description') }}</textarea>

        {{-- Facebook Reel --}}
        <input
            type="text"
            name="facebook_reel"
            value="{{ old('facebook_reel') }}"
            placeholder="لينك Facebook Reel (promo)"
            class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

        {{-- YouTube --}}
        <input
            type="text"
            name="video_url"
            value="{{ old('video_url') }}"
            placeholder="لينك يوتيوب"
            class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

        {{-- Year --}}
        <input
            type="number"
            name="year"
            value="{{ old('year') }}"
            placeholder="سنة العرض"
            min="1900"
            max="2100"
            class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

        {{-- Poster --}}
        <div class="space-y-1">
            <label class="text-xs text-gray-300">🖼️ بوستر العرض</label>
            <input
                type="file"
                name="poster"
                accept="image/*"
                class="w-full text-xs text-gray-300">
        </div>

        {{-- Gallery --}}
        <div class="space-y-1">
            <label class="text-xs text-gray-300">📸 صور من العرض</label>
            <input
                type="file"
                name="images[]"
                multiple
                accept="image/*"
                class="w-full text-xs text-gray-300">
        </div>

        <button
            type="submit"
            class="px-4 py-2 bg-amber-400 text-black rounded-full hover:bg-amber-300 transition">
            حفظ العرض
        </button>

    </form>

</section>
@endsection
