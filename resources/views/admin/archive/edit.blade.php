@extends('layouts.app')

@section('title', 'تعديل عرض سابق')

@section('content')
<section class="max-w-xl mx-auto space-y-6">

    <h1 class="text-2xl font-bold">✏️ تعديل عرض سابق</h1>

    <form method="POST"
          action="{{ route('admin.archive.update', $archive) }}"
          enctype="multipart/form-data"
          class="space-y-4 bg-black/40 p-5 rounded-xl border border-white/10">
        @csrf
        @method('PUT')

        {{-- Title --}}
        <input
            type="text"
            name="title"
            value="{{ old('title', $archive->title) }}"
            required
            class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

        {{-- Description --}}
        <textarea
            name="description"
            rows="4"
            class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">{{ old('description', $archive->description) }}</textarea>

        {{-- Facebook Reel --}}
        <input
            type="text"
            name="facebook_reel"
            value="{{ old('facebook_reel', $archive->facebook_reel) }}"
            placeholder="لينك Facebook Reel (Embed URL)"
            class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

        {{-- YouTube --}}
        <input
            type="text"
            name="video_url"
            value="{{ old('video_url', $archive->video_url) }}"
            placeholder="لينك يوتيوب"
            class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

        {{-- Year --}}
        <input
            type="number"
            name="year"
            value="{{ old('year', $archive->year) }}"
            min="1900"
            max="2100"
            class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

        {{-- Poster --}}
        <div class="space-y-2">
            <label class="text-xs text-gray-300">🖼️ بوستر العرض</label>

            @if($archive->poster_path)
                <img
                    src="{{ $archive->poster_path }}"
                    alt="Poster"
                    class="w-full h-48 object-cover rounded-lg border border-white/10">
            @endif

            <input
                type="file"
                name="poster"
                accept="image/*"
                class="w-full text-xs text-gray-300">
        </div>

        {{-- Gallery --}}
        <div class="space-y-2">
            <label class="text-xs text-gray-300">📸 صور من العرض</label>

            @if($archive->images && $archive->images->count())
                <div class="grid grid-cols-3 gap-2">
                    @foreach($archive->images as $img)
                        <img
                            src="{{ $img->image_path }}"
                            alt="Gallery image"
                            class="h-24 w-full object-cover rounded border border-white/10">
                    @endforeach
                </div>
            @endif

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
            حفظ التعديلات
        </button>

    </form>

</section>
@endsection
