@extends('layouts.app')

@section('title', $archive->title)

@section('content')
<section class="space-y-10 max-w-5xl mx-auto px-4">

    {{-- ================= Hero ================= --}}
    <div class="relative rounded-3xl overflow-hidden border border-white/10">

        @if($archive->poster_path)
            <img
                src="{{ $archive->poster_path }}"
                class="h-72 w-full object-cover"
                onclick="openViewer(0)">
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

    {{-- ================= Facebook Reel ================= --}}
    @if($archive->facebook_reel)
    <div class="bg-black/40 border border-white/10 rounded-2xl p-6">
        <h2 class="font-semibold mb-4">🎬 مقطع من العرض</h2>
        <div class="aspect-video rounded-xl overflow-hidden">
            <iframe
                src="{{ $archive->facebook_reel }}"
                class="w-full h-full"
                allowfullscreen
                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture">
            </iframe>
        </div>
    </div>
    @endif

    {{-- ================= Description ================= --}}
    @if($archive->description)
    <div class="bg-black/40 border border-white/10 rounded-2xl p-6">
        <h2 class="font-semibold mb-2">📖 وصف العرض</h2>
        <p class="text-sm text-gray-300 leading-relaxed">
            {{ $archive->description }}
        </p>
    </div>
    @endif

    {{-- ================= YouTube ================= --}}
    @php
        function ytId($url){
            if(preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/))([^&]+)~', $url, $m)){
                return $m[1];
            }
            return null;
        }
        $yt = $archive->video_url ? ytId($archive->video_url) : null;
    @endphp

    @if($yt)
    <div class="bg-black/40 border border-white/10 rounded-2xl p-6">
        <h2 class="font-semibold mb-4">🎥 مشاهدة العرض</h2>
        <div class="aspect-video rounded-xl overflow-hidden">
            <iframe
                src="https://www.youtube.com/embed/{{ $yt }}"
                class="w-full h-full"
                allowfullscreen>
            </iframe>
        </div>
    </div>
    @endif

    {{-- ================= Gallery ================= --}}
    @if($archive->images && $archive->images->count())
    <div class="bg-black/40 border border-white/10 rounded-2xl p-6 space-y-4">
        <h2 class="font-semibold text-lg">📸 صور من العرض</h2>

        <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2">
            @foreach($archive->images as $i => $img)
                <img
                    src="{{ $img->image_path }}"
                    data-index="{{ $i }}"
                    loading="lazy"
                    onclick="openViewer({{ $i }})"
                    class="snap-center min-w-[85%] sm:min-w-[45%] md:min-w-[30%]
                           h-64 object-cover rounded-xl cursor-pointer
                           hover:scale-105 transition">
            @endforeach
        </div>

        <p class="text-xs text-gray-400 text-center">
            اسحب يمين / شمال أو اضغط للتكبير
        </p>
    </div>
    @endif

</section>

{{-- ================= Fullscreen Viewer ================= --}}
<div id="viewer"
     class="fixed inset-0 bg-black/95 hidden z-50 flex items-center justify-center">

    {{-- Close --}}
    <button onclick="closeViewer()"
            class="absolute top-5 right-5 text-white text-2xl">✕</button>

    {{-- Prev --}}
    <button onclick="prevImg()"
            class="absolute left-4 text-white text-4xl">‹</button>

    {{-- Next --}}
    <button onclick="nextImg()"
            class="absolute right-4 text-white text-4xl">›</button>

    <img id="viewer-img"
         class="max-w-[95vw] max-h-[90vh]
                transition-transform duration-300">
</div>

<script>
const images = @json($archive->images->pluck('image_path'));
let current = 0;
let scale = 1;
let startX = 0;

function openViewer(index){
    current = index;
    scale = 1;
    const img = document.getElementById('viewer-img');
    img.src = images[current];
    img.style.transform = 'scale(1)';
    document.getElementById('viewer').classList.remove('hidden');
}

function closeViewer(){
    document.getElementById('viewer').classList.add('hidden');
}

function nextImg(){
    current = (current + 1) % images.length;
    openViewer(current);
}

function prevImg(){
    current = (current - 1 + images.length) % images.length;
    openViewer(current);
}

// Swipe
const viewer = document.getElementById('viewer');
viewer.addEventListener('touchstart', e => {
    startX = e.touches[0].clientX;
});

viewer.addEventListener('touchend', e => {
    let dx = e.changedTouches[0].clientX - startX;
    if(dx > 50) prevImg();
    if(dx < -50) nextImg();
});

// Zoom (Pinch + Wheel)
const img = document.getElementById('viewer-img');
img.addEventListener('wheel', e => {
    e.preventDefault();
    scale += e.deltaY * -0.001;
    scale = Math.min(Math.max(1, scale), 3);
    img.style.transform = `scale(${scale})`;
});

// Double Tap Zoom
let lastTap = 0;
img.addEventListener('touchend', e => {
    let now = new Date().getTime();
    if(now - lastTap < 300){
        scale = scale === 1 ? 2 : 1;
        img.style.transform = `scale(${scale})`;
    }
    lastTap = now;
});
</script>
@endsection
