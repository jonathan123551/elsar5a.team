@extends('layouts.app')

@section('title', $archive->title)

@section('content')
<section class="space-y-10 max-w-5xl mx-auto px-4">

   {{-- ================= Hero ================= --}}
<div class="relative rounded-3xl overflow-hidden border border-white/10">

    @if($archive->poster_path)
        <img
            src="{{ $archive->poster_path }}"
            alt="{{ $archive->title }}"
            class="w-full h-auto object-contain ">
    @else
        <div class="w-full h-[60vh] bg-black/40 flex items-center justify-center text-gray-400">
            لا يوجد بوستر
        </div>
    @endif

    {{-- العنوان بدون سواد --}}
    <div class="absolute bottom-4 right-4 left-4 bg-black/50 backdrop-blur-sm rounded-xl p-4">
        <h1 class="text-2xl md:text-3xl font-bold mb-1">
            {{ $archive->title }}
        </h1>

        @if($archive->year)
            <p class="text-sm text-gray-300">
                سنة العرض: {{ $archive->year }}
            </p>
        @endif
    </div>

</div>


    {{-- ================= Facebook Reel ================= --}}
    @if($archive->facebook_reel)
    <div class="bg-black/40 border border-white/10 rounded-2xl p-6">
        <h2 class="font-semibold mb-4">🎬 برومو العرض</h2>
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
            loading="lazy"
            onclick="openViewer({{ $i }})"
            class="snap-center min-w-[85%] sm:min-w-[45%] md:min-w-[30%]
                   h-64 object-cover rounded-xl cursor-pointer
                   hover:scale-105 transition duration-300">
    @endforeach
</div>

<p class="text-xs text-gray-400 text-center">
    اسحب أو اضغط للتكبير
</p>


</div>
@endif

</section>

{{-- ================= VIEWER ================= --}}

<div id="viewer"
     class="fixed inset-0 bg-black/95 hidden z-[9999]
            flex items-center justify-center
            opacity-0 transition-opacity duration-300">


{{-- Close --}}
<button onclick="closeViewer()"
        class="absolute top-5 right-5 text-white text-2xl z-50">✕</button>

{{-- Prev --}}
<button onclick="prevImg()"
        class="absolute left-4 text-white text-4xl z-50">‹</button>

{{-- Next --}}
<button onclick="nextImg()"
        class="absolute right-4 text-white text-4xl z-50">›</button>

<img id="viewer-img"
     class="max-w-[95vw] max-h-[90vh]
            transition-all duration-300 ease-in-out will-change-transform">


</div>

<script>
const images = @json($archive->images->pluck('image_path'));

let current = 0;
let scale = 1;
let startX = 0;

const viewer = document.getElementById('viewer');
const img = document.getElementById('viewer-img');

// 🔥 preload (مفيش lag)
images.forEach(src => {
    const i = new Image();
    i.src = src;
});

// 🔥 open viewer (isolated mode)
function openViewer(index){
    current = index;
    scale = 1;

    img.src = images[current];
    img.style.transform = 'scale(1)';

    viewer.classList.remove('hidden');

    setTimeout(()=>{
        viewer.classList.remove('opacity-0');
    },10);

    // 🚫 منع scroll الموقع
    document.body.style.overflow = 'hidden';
}

// 🔥 close
function closeViewer(){
    viewer.classList.add('opacity-0');

    setTimeout(()=>{
        viewer.classList.add('hidden');
    },300);

    document.body.style.overflow = '';
}

// 🔥 smooth change (بدون reopen)
function changeImage(newIndex){
    current = (newIndex + images.length) % images.length;
    scale = 1;

    img.style.opacity = 0;
    img.style.transform = 'scale(0.96)';

    setTimeout(()=>{
        img.src = images[current];
        img.style.opacity = 1;
        img.style.transform = 'scale(1)';
    },120);
}

function nextImg(){
    changeImage(current + 1);
}

function prevImg(){
    changeImage(current - 1);
}

// 🔥 swipe ناعم
viewer.addEventListener('touchstart', e => {
    startX = e.touches[0].clientX;
});

viewer.addEventListener('touchend', e => {

    // 🚫 لو الصورة متزوّمة → متقلبش
    if(scale > 1) return;

    let dx = e.changedTouches[0].clientX - startX;

    if(Math.abs(dx) > 40){
        dx > 0 ? prevImg() : nextImg();
    }
});

// 🔥 zoom wheel
img.addEventListener('wheel', e => {
    e.preventDefault();

    scale += e.deltaY * -0.001;
    scale = Math.min(Math.max(1, scale), 4);

    img.style.transform = `scale(${scale})`;
});

// 🔥 double tap
let lastTap = 0;
img.addEventListener('touchend', () => {
    let now = new Date().getTime();

    if(now - lastTap < 250){
        scale = scale === 1 ? 2.5 : 1;
        img.style.transform = `scale(${scale})`;
    }

    lastTap = now;
});

// 🔥 keyboard
document.addEventListener('keydown', e => {
    if(viewer.classList.contains('hidden')) return;

    if(e.key === 'ArrowRight') nextImg();
    if(e.key === 'ArrowLeft') prevImg();
    if(e.key === 'Escape') closeViewer();
});
</script>


@endsection
