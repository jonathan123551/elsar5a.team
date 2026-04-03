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
let currentX = 0;

let velocity = 0;
let isDragging = false;

let initialDistance = 0;
let isPinching = false;

const viewer = document.getElementById('viewer');
const img = document.getElementById('viewer-img');

// 🔥 preload
images.forEach(src => {
const i = new Image();
i.src = src;
});

function openViewer(index){
current = index;
scale = 1;
currentX = 0;


img.src = images[current];
img.style.transform = `translateX(0px) scale(1)`;

viewer.classList.remove('hidden');
setTimeout(()=> viewer.classList.remove('opacity-0'), 10);

document.body.style.overflow = 'hidden';


}

function closeViewer(){
viewer.classList.add('opacity-0');
setTimeout(()=> viewer.classList.add('hidden'), 300);
document.body.style.overflow = '';
}

function changeImage(newIndex){
current = (newIndex + images.length) % images.length;
scale = 1;
currentX = 0;


img.style.transition = 'none';
img.style.opacity = 0;

setTimeout(()=>{
    img.src = images[current];
    img.style.transition = 'all 0.3s ease';
    img.style.opacity = 1;
    img.style.transform = `translateX(0px) scale(1)`;
},80);


}

function nextImg(){
if(scale > 1) return;
changeImage(current + 1);
}

function prevImg(){
if(scale > 1) return;
changeImage(current - 1);
}

// ================= PINCH =================

function getDistance(touches){
let dx = touches[0].clientX - touches[1].clientX;
let dy = touches[0].clientY - touches[1].clientY;
return Math.sqrt(dx*dx + dy*dy);
}

// ================= TOUCH =================

viewer.addEventListener('touchstart', e => {


if(e.touches.length === 2){
    isPinching = true;
    initialDistance = getDistance(e.touches);
    return;
}

isDragging = true;
startX = e.touches[0].clientX;
velocity = 0;


});

viewer.addEventListener('touchmove', e => {


// 🔥 pinch zoom
if(isPinching && e.touches.length === 2){
    let newDistance = getDistance(e.touches);
    let zoomFactor = newDistance / initialDistance;

    scale = Math.min(Math.max(1, scale * zoomFactor), 4);

    img.style.transform = `translateX(${currentX}px) scale(${scale})`;

    initialDistance = newDistance;
    return;
}

if(!isDragging) return;

let dx = e.touches[0].clientX - startX;

velocity = dx - currentX;
currentX = dx;

img.style.transform = `translateX(${dx}px) scale(${scale > 1 ? scale : 0.98})`;


});

viewer.addEventListener('touchend', () => {


// 🔥 لو كان pinch
if(isPinching){
    isPinching = false;
    return;
}

if(!isDragging) return;

// 🔥 inertia
let momentum = velocity * 3;
currentX += momentum;

img.style.transition = 'transform 0.3s ease-out';

// 🔥 لو الصورة مش متزوّمة → قلب
if(scale === 1 && Math.abs(currentX) > 100){
    currentX > 0 ? prevImg() : nextImg();
} else {
    currentX = 0;
    img.style.transform = `translateX(0px) scale(${scale})`;
}

setTimeout(()=>{
    img.style.transition = '';
},300);

isDragging = false;


});

// ================= DOUBLE TAP =================
let lastTap = 0;

img.addEventListener('touchend', () => {
let now = new Date().getTime();


if(now - lastTap < 250){
    scale = scale === 1 ? 2.5 : 1;
    img.style.transform = `translateX(0px) scale(${scale})`;
}

lastTap = now;


});

// ================= WHEEL =================
img.addEventListener('wheel', e => {
e.preventDefault();


scale += e.deltaY * -0.001;
scale = Math.min(Math.max(1, scale), 4);

img.style.transform = `translateX(${currentX}px) scale(${scale})`;


});

// ================= KEYBOARD =================
document.addEventListener('keydown', e => {
if(viewer.classList.contains('hidden')) return;


if(e.key === 'ArrowRight') nextImg();
if(e.key === 'ArrowLeft') prevImg();
if(e.key === 'Escape') closeViewer();


});

</script>


@endsection
