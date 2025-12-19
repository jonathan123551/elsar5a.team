@extends('layouts.app')

@section('title', $archive->title)

@section('content')
<section class="space-y-10">

{{-- ================= Hero ================= --}}
<div class="relative rounded-3xl overflow-hidden border border-white/10">

    @if(!empty($archive->poster_path))
        <img
            src="{{ asset('storage/'.$archive->poster_path) }}"
            class="w-full h-[60vh] object-cover">
    @else
        <div class="w-full h-[60vh] bg-black/40 flex items-center justify-center text-gray-400">
            لا يوجد بوستر
        </div>
    @endif

    <div class="absolute inset-0 bg-black/60"></div>

    <div class="absolute bottom-6 right-6 left-6">
        <h1 class="text-3xl font-bold mb-2">{{ $archive->title }}</h1>

        @if($archive->year)
            <p class="text-sm text-gray-300">سنة العرض: {{ $archive->year }}</p>
        @endif
    </div>
</div>

{{-- ================= Description ================= --}}
@if(!empty($archive->description))
<div class="bg-black/40 border border-white/10 rounded-2xl p-6">
    <h2 class="font-semibold mb-2">📖 وصف العرض</h2>
    <p class="text-sm text-gray-300 leading-relaxed">
        {{ $archive->description }}
    </p>
</div>
@endif

{{-- ================= Video ================= --}}
@php
    function youtubeEmbed($url) {
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/))([^&]+)~', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    $videoId = $archive->video_url ? youtubeEmbed($archive->video_url) : null;
@endphp

@if($videoId)
<div class="bg-black/40 border border-white/10 rounded-2xl p-6">
    <h2 class="font-semibold mb-4">🎥 مشاهدة العرض</h2>

    <div class="aspect-video rounded-xl overflow-hidden border border-white/10">
        <iframe
            class="w-full h-full"
            src="https://www.youtube.com/embed/{{ $videoId }}"
            allowfullscreen
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
        </iframe>
    </div>
</div>
@endif


{{-- ================= Gallery ================= --}}
@if($archive->images && $archive->images->count())
<section class="space-y-4">
    <h2 class="text-lg font-semibold">📸 صور العرض</h2>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        @foreach($archive->images as $img)
            <img
                src="{{ asset('storage/' . $img->image_path) }}"
                class="rounded-xl object-cover h-40 w-full
                       hover:scale-105 hover:shadow-xl hover:shadow-amber-400/20
                       transition duration-300 cursor-pointer"
                loading="lazy">
        @endforeach
    </div>
</section>
@endif

{{-- ================= Back ================= --}}
<a href="{{ route('archive') }}"
   class="inline-block mt-8 text-xs px-4 py-2 rounded-full
          bg-white/10 hover:bg-white/20 transition">
    ← رجوع للعروض السابقة
</a>

</section>
@endsection
