@extends('layouts.app')

@section('title', 'Scanner')

@section('content')

<section class="max-w-md mx-auto space-y-4 px-3 pb-10">

```
{{-- HEADER --}}
<div class="flex justify-between items-center">
    <h1 class="text-white text-lg font-bold">🎫 Gate Scanner</h1>

    <a href="{{ route('admin.dashboard') }}"
       class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10">
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

    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div class="scan-frame"></div>
    </div>

    <div class="scan-line"></div>

    <div id="status"
         class="absolute top-5 left-1/2 -translate-x-1/2 z-50 
                px-4 py-2 rounded-full 
                bg-black/80 backdrop-blur-md 
                text-sm text-white 
                border border-white/10">
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
.flash-ok{ color:#22c55e; text-shadow:0 0 50px #22c55e; }
.flash-used{ color:#facc15; text-shadow:0 0 50px #facc15; }
.flash-error{ color:#ef4444; text-shadow:0 0 50px #ef4444; }
</style>

<script>
const qr = new Html5Qrcode("qr-reader");

let busy = false;
let lastCode = null;
let lastScanTime = 0;

const COOLDOWN = 1500;

// 🔊 SOUND
let audioCtx;
function beep(type){
    try{
        if(!audioCtx){
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.frequency.value = type==='ok'?950:type==='used'?500:250;
        gain.gain.value = 0.25;
        osc.start();
        setTimeout(()=>osc.stop(),120);
    }catch(e){}
}

// 📳 vibration
function vibrate(type){
    if(type==='ok') navigator.vibrate?.(100);
    else if(type==='used') navigator.vibrate?.([80,40,80]);
    else navigator.vibrate?.(150);
}

// 💡 flash
function flash(type){
    const f = document.getElementById('flash');
    const i = document.getElementById('flashIcon');

    f.classList.remove('hidden');

    if(type==='ok'){ i.textContent='✓'; i.className='flash-ok'; }
    if(type==='used'){ i.textContent='!'; i.className='flash-used'; }
    if(type==='error'){ i.textContent='✕'; i.className='flash-error'; }

    setTimeout(()=>f.classList.add('hidden'),500);
}

// 🎯 status
function setStatus(text){
    document.getElementById('status').innerText = text;
}

// 📊 render
function render(d){
    const c = document.getElementById('card');
    c.classList.remove('hidden');

    c.innerHTML = `
        <div class="bg-black/60 rounded-xl p-3 space-y-1">
            <div class="text-white font-semibold">${d.name}</div>
            <div class="text-gray-300 text-xs">${d.show_title}</div>
            <div class="text-indigo-400 text-xs">${d.date} • ${d.time}</div>
        </div>
    `;
}

// 🚀 check
function check(code){

    fetch('/admin/scanner/check',{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':'{{ csrf_token() }}'
        },
        body:JSON.stringify({code})
    })
    .then(r=>r.json())
    .then(d=>{
        if(d.status==='ok'){
            setStatus('✅ دخول مسموح');
            beep('ok'); vibrate('ok'); flash('ok'); render(d);
        }
        else if(d.status==='used'){
            setStatus('⚠️ مستخدمة');
            beep('used'); vibrate('used'); flash('used'); render(d);
        }
        else{
            setStatus('❌ غير صالح');
            beep('error'); vibrate('error'); flash('error');
        }
    })
    .finally(()=>{
        setTimeout(()=>busy=false,300);
    });
}

// 🚀 START (optimized)
qr.start(
{ facingMode: "environment" },
{
    fps: 18,
    qrbox: (w,h)=>{
        const size = Math.min(w,h)*0.75;
        return { width:size, height:size };
    }
},
(text)=>{
    const now = Date.now();

    if(text === lastCode && now - lastScanTime < COOLDOWN) return;
    if(busy) return;

    busy = true;
    lastCode = text;
    lastScanTime = now;

    check(text);
}
);

// 🔥 CAMERA TUNING (iPhone friendly)
setTimeout(async ()=>{
    try{
        const track = qr.getRunningTrack();
        const cap = track.getCapabilities();

        let c = { advanced: [] };

        if(cap.focusMode){
            c.advanced.push({ focusMode:"continuous" });
        }

        if(cap.exposureMode){
            c.advanced.push({ exposureMode:"continuous" });
        }

        if(cap.zoom){
            c.advanced.push({ zoom: cap.zoom.max*0.5 });
        }

        await track.applyConstraints(c);

    }catch(e){}
},1200);

// 🔦 flash
let flashOn=false;
document.getElementById('flashBtn').onclick=async()=>{
    try{
        flashOn=!flashOn;
        await qr.applyVideoConstraints({
            advanced:[{torch:flashOn}]
        });
    }catch(e){
        alert('الفلاش غير مدعوم');
    }
};
</script>

@endsection
