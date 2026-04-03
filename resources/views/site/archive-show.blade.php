@extends('layouts.app')

@section('title', $archive->title)

@section('content')

<section class="space-y-10 max-w-5xl mx-auto px-4">

{{-- ================= Hero ================= --}}

<div class="relative rounded-3xl overflow-hidden border border-white/10">


@if($archive->poster_path)
    <img src="{{ $archive->poster_path }}"
         class="w-full object-contain">
@endif

<div class="absolute bottom-4 right-4 left-4 bg-black/50 backdrop-blur-sm rounded-xl p-4">
    <h1 class="text-2xl md:text-3xl font-bold">
        {{ $archive->title }}
    </h1>
</div>


</div>

{{-- ================= Gallery ================= --}}
@if($archive->images && $archive->images->count())

<div class="bg-black/40 border border-white/10 rounded-2xl p-6 space-y-4">


<h2 class="font-semibold text-lg">📸 صور من العرض</h2>

<div class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2">
    @foreach($archive->images as $i => $img)
        <img src="{{ $img->image_path }}"
             onclick="openViewer({{ $i }})"
             class="snap-center min-w-[85%] sm:min-w-[45%] md:min-w-[30%]
                    h-64 object-cover rounded-xl cursor-pointer
                    hover:scale-105 transition duration-300">
    @endforeach
</div>


</div>
@endif

</section>

{{-- ================= VIEWER ================= --}}

<div id="viewer"
     class="fixed inset-0 bg-black/95 hidden z-50 flex items-center justify-center
            transition-opacity duration-300 opacity-0">

<button onclick="closeViewer()"
        class="absolute top-5 right-5 text-white text-2xl z-50">✕</button>

<button onclick="prevImg()"
        class="absolute left-4 text-white text-4xl z-50">‹</button>

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

// 🔥 preload images
images.forEach(src => {
    const i = new Image();
    i.src = src;
});

function openViewer(index){
    current = index;
    scale = 1;

    img.src = images[current];
    img.style.transform = 'scale(1)';

    viewer.classList.remove('hidden');

    setTimeout(()=>{
        viewer.classList.remove('opacity-0');
    },10);
}

function closeViewer(){
    viewer.classList.add('opacity-0');

    setTimeout(()=>{
        viewer.classList.add('hidden');
    },300);
}

function changeImage(newIndex){
    current = (newIndex + images.length) % images.length;

    img.style.transition = 'none';
    img.style.opacity = 0;
    img.style.transform = 'scale(0.95)';

    setTimeout(()=>{
        img.src = images[current];
        img.style.transition = 'all 0.3s ease';
        img.style.opacity = 1;
        img.style.transform = 'scale(1)';
    },100);
}

function nextImg(){
    changeImage(current + 1);
}

function prevImg(){
    changeImage(current - 1);
}

// 🔥 swipe ultra smooth
viewer.addEventListener('touchstart', e => {
    startX = e.touches[0].clientX;
});

viewer.addEventListener('touchend', e => {
    let dx = e.changedTouches[0].clientX - startX;

    if(Math.abs(dx) > 40){
        if(dx > 0) prevImg();
        else nextImg();
    }
});

// 🔥 zoom
img.addEventListener('wheel', e => {
    e.preventDefault();

    scale += e.deltaY * -0.001;
    scale = Math.min(Math.max(1, scale), 4);

    img.style.transform = `scale(${scale})`;
});

// 🔥 double tap zoom
let lastTap = 0;
img.addEventListener('touchend', e => {
    let now = new Date().getTime();

    if(now - lastTap < 250){
        scale = scale === 1 ? 2.5 : 1;
        img.style.transform = `scale(${scale})`;
    }

    lastTap = now;
});

// 🔥 keyboard support
document.addEventListener('keydown', e => {
    if(viewer.classList.contains('hidden')) return;

    if(e.key === 'ArrowRight') nextImg();
    if(e.key === 'ArrowLeft') prevImg();
    if(e.key === 'Escape') closeViewer();
});
</script>

@endsection
