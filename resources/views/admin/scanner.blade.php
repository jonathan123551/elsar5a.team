@extends('layouts.app')

@section('title', 'Scanner')

@section('content')

<section class="max-w-md mx-auto space-y-4 px-3 pb-10">

```
{{-- HEADER --}}
<div class="flex justify-between items-center">
    <h1 class="text-white text-lg font-bold">🎫 Gate Scanner</h1>

    <a href="{{ route('admin.dashboard') }}"
       class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
        ← رجوع
    </a>
</div>

{{-- RESULT --}}
<div id="card"
     class="hidden bg-gradient-to-br from-white/10 to-white/5 border border-white/10 rounded-xl p-3 text-sm space-y-2 backdrop-blur">
</div>

{{-- SCANNER --}}
<div class="relative bg-black/70 border border-white/10 rounded-3xl p-3 overflow-hidden">

    <div id="qr-reader"
         class="rounded-2xl overflow-hidden border border-white/10"></div>

    {{-- FRAME --}}
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div class="scan-frame"></div>
    </div>

    {{-- LINE --}}
    <div class="scan-line"></div>

    {{-- STATUS --}}
    <div id="status"
         class="absolute top-5 left-1/2 -translate-x-1/2 z-50 
                px-4 py-2 rounded-full 
                bg-black/80 backdrop-blur-md 
                text-sm text-white 
                shadow-lg border border-white/10
                transition-all duration-300">
        جاهز للفحص
    </div>

</div>

{{-- CONTROLS --}}
<div class="flex gap-2">

    <button id="flashBtn"
            class="flex-1 text-xs py-2 rounded-xl bg-white/5 border border-white/10">
        🔦 Flash
    </button>

    <button onclick="location.reload()"
            class="flex-1 text-xs py-2 rounded-xl bg-white/5 border border-white/10">
        🔄 Restart
    </button>

</div>
```

</section>

{{-- FLASH --}}

<div id="flash" class="fixed inset-0 flex items-center justify-center hidden z-50">
    <div id="flashIcon" class="text-8xl font-black"></div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>
.scan-frame{
    width:230px;
    height:230px;
    border:2px solid rgba(255,255,255,0.2);
    border-radius:20px;
}
.scan-line{
    position:absolute;
    left:10%;
    right:10%;
    height:2px;
    background:linear-gradient(90deg,transparent,#22c55e,transparent);
    animation:scan 1.2s infinite;
}
@keyframes scan{
    0%{top:10%}
    50%{top:85%}
    100%{top:10%}
}
.flash-ok{ color:#22c55e; }
.flash-used{ color:#facc15; }
.flash-error{ color:#ef4444; }
</style>

<script>
const qr = new Html5Qrcode("qr-reader");

let busy = false;
let lastCode = null;
let lastScanTime = 0;
let scanning = true;

const COOLDOWN = 3000;

// 🎯 TRACK BOX
const trackBox = document.createElement("div");
trackBox.className = "absolute border-2 border-emerald-400 rounded-xl pointer-events-none hidden";
document.querySelector("#qr-reader").parentElement.appendChild(trackBox);

// 🔥 TRACKING
function updateTracking(result){

    const video = document.querySelector("#qr-reader video");
    if(!video || !video.videoWidth) return;
    if(!result?.resultPoints) return;

    const rect = video.getBoundingClientRect();

    const scaleX = rect.width / video.videoWidth;
    const scaleY = rect.height / video.videoHeight;

    let minX = Infinity, minY = Infinity;
    let maxX = 0, maxY = 0;

    result.resultPoints.forEach(p=>{
        minX = Math.min(minX, p.x);
        minY = Math.min(minY, p.y);
        maxX = Math.max(maxX, p.x);
        maxY = Math.max(maxY, p.y);
    });

    trackBox.style.left = (minX * scaleX) + "px";
    trackBox.style.top = (minY * scaleY) + "px";
    trackBox.style.width = ((maxX-minX)*scaleX) + "px";
    trackBox.style.height = ((maxY-minY)*scaleY) + "px";

    trackBox.classList.remove("hidden");
}

// hide
setInterval(()=> trackBox.classList.add("hidden"),700);

// 🚀 START
qr.start(
{
    facingMode: "environment"
},
{
    fps: 30,
    qrbox: (w,h)=>{
        const size = Math.min(w,h)*0.7;
        return { width:size, height:size };
    },
    aspectRatio:1,
    experimentalFeatures:{
        useBarCodeDetectorIfSupported:true
    },
    videoConstraints:{
        facingMode:"environment",
        width:{ideal:1920},
        height:{ideal:1080}
    }
},
(decodedText, decodedResult)=>{

    updateTracking(decodedResult);

    if(!scanning) return;

    const now = Date.now();

    if(decodedText === lastCode && now - lastScanTime < COOLDOWN){
        return;
    }

    if(busy) return;

    scanning = false;
    busy = true;

    lastCode = decodedText;
    lastScanTime = now;

    navigator.vibrate?.(80);

    check(decodedText);

    setTimeout(()=> scanning = true,600);
}
);

// 🔥 AUTO FOCUS
setTimeout(async ()=>{
    try{
        const track = qr.getRunningTrack();
        const cap = track.getCapabilities();

        let constraints = { advanced: [] };

        if(cap.zoom){
            constraints.advanced.push({ zoom: cap.zoom.max*0.6 });
        }
        if(cap.focusMode){
            constraints.advanced.push({ focusMode:"continuous" });
        }

        await track.applyConstraints(constraints);

    }catch(e){}
},1200);

// 🔦 Flash
let flashOn=false;
document.getElementById('flashBtn').onclick=async()=>{
    try{
        flashOn=!flashOn;
        await qr.applyVideoConstraints({ advanced:[{ torch:flashOn }] });
    }catch(e){}
};
</script>

@endsection
