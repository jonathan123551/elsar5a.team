@extends('layouts.app')

@section('title', 'Scanner')

@section('content')
<section class="max-w-md mx-auto space-y-4 px-3 pb-10">

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

    {{-- 🔥 STATUS OVERLAY TOP --}}
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

 
   

    

</section>

{{-- FLASH EFFECT --}}
<div id="flash" class="fixed inset-0 flex items-center justify-center hidden z-50">
    <div id="flashIcon" class="text-8xl font-black"></div>
</div>

<script src="https://unpkg.com/@zxing/browser@latest"></script>
<style>
.scan-frame{
    width:230px;
    height:230px;
    border:2px solid rgba(255,255,255,0.2);
    border-radius:20px;
    box-shadow:0 0 30px rgba(255,255,255,0.1);
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

.glow-green{ box-shadow:0 0 25px rgba(34,197,94,.6); }
.glow-yellow{ box-shadow:0 0 25px rgba(250,204,21,.6); }
.glow-red{ box-shadow:0 0 25px rgba(239,68,68,.6); }
</style>


<script>
const codeReader = new ZXing.BrowserQRCodeReader();

let busy = false;
let lastCode = null;
let lastScanTime = 0;

const COOLDOWN = 3000;

// 🔊 SOUND (زي ما هو)
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

        osc.frequency.value =
            type === 'ok' ? 950 :
            type === 'used' ? 500 : 250;

        gain.gain.value = 0.25;

        osc.start();
        setTimeout(()=>osc.stop(),150);

    }catch(e){}
}

// 📳 vibration (زي ما هو)
function vibrate(type){
    if(type==='ok') navigator.vibrate?.(120);
    else if(type==='used') navigator.vibrate?.([100,50,100]);
    else navigator.vibrate?.(200);
}

// 💡 flash overlay (زي ما هو)
function flash(type){
    const f = document.getElementById('flash');
    const i = document.getElementById('flashIcon');

    f.classList.remove('hidden');

    if(type==='ok'){ i.textContent='✓'; i.className='flash-ok'; }
    if(type==='used'){ i.textContent='!'; i.className='flash-used'; }
    if(type==='error'){ i.textContent='✕'; i.className='flash-error'; }

    setTimeout(()=>f.classList.add('hidden'),700);
}

// 🎯 UI (زي ما هو)
function setStatus(text,type){
    const s = document.getElementById('status');

    s.innerText = text;

    s.className = "absolute top-5 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-full text-sm border transition-all duration-300";

    if(type==='ok'){
        s.classList.add('bg-green-500/90','text-white','border-green-300');
    }
    else if(type==='used'){
        s.classList.add('bg-yellow-400/90','text-black','border-yellow-200');
    }
    else{
        s.classList.add('bg-red-500/90','text-white','border-red-300');
    }

    s.style.transform = "translate(-50%, -5px) scale(1.1)";
    setTimeout(()=>{
        s.style.transform = "translate(-50%, 0) scale(1)";
    },150);
}

// 📊 render (زي ما هو)
function render(d){
    const c = document.getElementById('card');
    c.classList.remove('hidden');

    c.innerHTML = `
        <div class="bg-black/60 backdrop-blur border border-white/10 rounded-xl p-3 space-y-2">
            <div class="text-white font-semibold text-sm tracking-wide">
                ${d.name}
            </div>

            <div class="border-t border-white/10 my-1"></div>

            <div class="text-gray-300 text-[12px]">
                🎭 ${d.show_title}
            </div>

            <div class="text-[12px] font-medium text-indigo-400">
                🕒 ${d.date} • ${d.time}
            </div>

            ${
                d.scanned_at 
                ? `<div class="text-emerald-400 text-[12px] font-semibold">
                        ✅ دخل: ${d.scanned_at}
                   </div>`
                : ''
            }
        </div>
    `;
}

// 🚀 request (زي ما هو)
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
            setStatus('✅ دخول مسموح','ok');
            vibrate('ok');
            beep('ok');
            flash('ok');
            render(d);
        }
        else if(d.status==='used'){
            setStatus('⚠️ مستخدمة','used');
            vibrate('used');
            beep('used');
            flash('used');
            render(d);
        }
        else{
            setStatus('❌ غير صالح','error');
            vibrate('error');
            beep('error');
            flash('error');
        }

    })
    .finally(()=>{
        setTimeout(()=>busy=false,800);
    });
}

// ================= ZXING START =================

    async function startScanner(){
try {


    const video = document.createElement('video');
    video.setAttribute("playsinline", true);
    video.autoplay = true;
    video.muted = true;
    video.className = "w-full rounded-2xl";

    const container = document.getElementById('qr-reader');
    container.innerHTML = '';
    container.appendChild(video);

    const stream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: "environment",
            width: { ideal: 1280 },
            height: { ideal: 720 }
        }
    });

    video.srcObject = stream;

    codeReader.decodeFromVideoElement(video, (result, err) => {

        if(result){

            const text = result.getText();
            const now = Date.now();

            if(text === lastCode && now - lastScanTime < COOLDOWN){
                return;
            }

            if(busy) return;

            busy = true;
            lastCode = text;
            lastScanTime = now;

            check(text);
        }

    });

} catch (err) {

    console.error(err);

    setStatus('❌ الكاميرا مش شغالة','error');

    alert("افتح إذن الكاميرا من المتصفح ❗");
}


}


startScanner();

// 🔦 Flash control (نفسه)
document.getElementById('flashBtn').onclick = async () => {
    const track = document.querySelector('video')?.srcObject?.getVideoTracks()[0];

    if(!track) return;

    const capabilities = track.getCapabilities();

    if(!capabilities.torch){
        alert('الفلاش غير مدعوم');
        return;
    }

    const torchOn = !track.getSettings().torch;

    await track.applyConstraints({
        advanced: [{ torch: torchOn }]
    });
};
</script>

@endsection
