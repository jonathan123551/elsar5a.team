@extends('layouts.app')

@section('title', $archive->title)

@section('content')
<section class="space-y-10 max-w-5xl mx-auto px-4">
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
<div class="bg-black/40 border border-white/10 rounded-2xl p-6 space-y-4">
    <h2 class="font-semibold text-lg">📸 صور من العرض</h2>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        @foreach($archive->images as $img)
            <div
                class="relative group cursor-pointer overflow-hidden rounded-xl"
                onclick="openLightbox('{{ asset('storage/' . $img->image_path) }}')">

                <img
                    src="{{ asset('storage/' . $img->image_path) }}"
                    class="h-40 w-full object-cover
                           transition duration-500
                           group-hover:scale-110
                           group-hover:brightness-110"
                    loading="lazy">

                {{-- Overlay --}}
                <div
                    class="absolute inset-0 bg-black/40 opacity-0
                           group-hover:opacity-100
                           transition duration-300
                           flex items-center justify-center">

                    <span class="text-white text-3xl">🔍</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
<div
    id="lightbox"
    class="fixed inset-0 bg-black/80 hidden z-50
           flex items-center justify-center"
    onclick="closeLightbox()">

    <img
        id="lightbox-img"
        class="max-h-[90vh] max-w-[90vw]
               rounded-2xl shadow-2xl">
</div>
<script>
    function openLightbox(src) {
        const lightbox = document.getElementById('lightbox');
        const img = document.getElementById('lightbox-img');

        img.src = src;
        lightbox.classList.remove('hidden');
    }

    function closeLightbox() {
        document.getElementById('lightbox').classList.add('hidden');
    }
</script>



{{-- ================= Back ================= --}}
<a href="{{ route('archive') }}"
   class="inline-block mt-8 text-xs px-4 py-2 rounded-full
          bg-white/10 hover:bg-white/20 transition">
    ← رجوع للعروض السابقة
</a>

</section>
@endsection
