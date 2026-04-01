@extends('layouts.app')

@section('title', 'Scanner')

@section('content')
<section class="max-w-md mx-auto space-y-4 px-3">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <h1 class="text-white text-lg font-bold">🎫 Scanner</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع
        </a>
    </div>

    {{-- SCANNER --}}
    <div class="relative bg-black/60 border border-white/10 rounded-3xl p-3 overflow-hidden">

        <div id="qr-reader"
             class="rounded-2xl overflow-hidden border border-white/10"></div>

        {{-- SCAN FRAME --}}
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="scan-box"></div>
        </div>

        {{-- SCAN LINE --}}
        <div class="scan-line"></div>

    </div>

    {{-- STATUS --}}
    <div id="status"
         class="text-center py-3 rounded-xl bg-white/5 border border-white/10 text-sm">
        جاهز للفحص
    </div>

    {{-- RESULT --}}
    <div id="card"
         class="hidden bg-gradient-to-br from-white/10 to-white/5 border border-white/10 rounded-xl p-3 text-sm space-y-2">
    </div>

</section>

{{-- FLASH --}}
<div id="flash" class="fixed inset-0 flex items-center justify-center hidden z-50">
    <div id="flashIcon" class="text-8xl font-black"></div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>
.scan-box{
    width: 220px;
    height: 220px;
    border: 2px solid rgba(255,255,255,0.2);
    border-radius: 16px;
    box-shadow: 0 0 20px rgba(255,255,255,0.1);
}

.scan-line{
    position:absolute;
    left:10%;
    right:10%;
    height:2px;
    background:linear-gradient(90deg,transparent,#22c55e,transparent);
    animation:scan 1.5s infinite;
}
@keyframes scan{
    0%{top:10%}
    50%{top:85%}
    100%{top:10%}
}

.flash-ok{ color:#22c55e; text-shadow:0 0 40px #22c55e; }
.flash-used{ color:#facc15; text-shadow:0 0 40px #facc15; }
.flash-error{ color:#ef4444; text-shadow:0 0 40px #ef4444; }

.glow-green{ box-shadow:0 0 20px rgba(34,197,94,.6); }
.glow-yellow{ box-shadow:0 0 20px rgba(250,204,21,.6); }
.glow-red{ box-shadow:0 0 20px rgba(239,68,68,.6); }
</style>

<script>
const qr = new Html5Qrcode("qr-reader");

let busy = false;
let last = null;
let lastTime = 0;

// 🔊 SOUND PRO
function beep(type){
    const ctx = new (window.AudioContext || window.webkitAudioContext)();

    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.connect(gain);
    gain.connect(ctx.destination);

    if(type==='ok'){
        osc.frequency.value = 900;
    } else if(type==='used'){
        osc.frequency.value = 500;
    } else {
        osc.frequency.value = 250;
    }

    gain.gain.value = 0.2;

    osc.start();
    setTimeout(()=>osc.stop(),120);
}

// 💡 FLASH EFFECT
function flash(type){
    const f = document.getElementById('flash');
    const i = document.getElementById('flashIcon');

    f.classList.remove('hidden');

    if(type==='ok'){ i.textContent='✓'; i.className='flash-ok'; }
    if(type==='used'){ i.textContent='!'; i.className='flash-used'; }
    if(type==='error'){ i.textContent='✕'; i.className='flash-error'; }

    setTimeout(()=>f.classList.add('hidden'),700);
}

// 📊 RENDER
function render(d){
    const c = document.getElementById('card');
    c.classList.remove('hidden');

    c.innerHTML = `
        <div class="text-white font-bold text-base">${d.name}</div>
        <div class="text-gray-400 text-xs">${d.phone}</div>

        <div class="border-t border-white/10 pt-2"></div>

        <div>🎭 ${d.show_title}</div>
        <div>🕒 ${d.date} • ${d.time}</div>

        ${d.scanned_at ? `<div class="text-green-400">✅ دخل: ${d.scanned_at}</div>` : ''}
    `;
}

// 🎯 STATUS
function setStatus(text,type){
    const s = document.getElementById('status');

    s.textContent = text;
    s.className = "text-center py-3 rounded-xl text-sm transition-all";

    if(type==='ok'){
        s.classList.add('bg-green-500/10','text-green-400','glow-green');
    }
    else if(type==='used'){
        s.classList.add('bg-yellow-500/10','text-yellow-400','glow-yellow');
    }
    else{
        s.classList.add('bg-red-500/10','text-red-400','glow-red');
    }
}

// 🚀 CHECK
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
            navigator.vibrate?.(120);
            flash('ok');
            beep('ok');
            render(d);
        }
        else if(d.status==='used'){
            setStatus('⚠️ مستخدمة قبل كده','used');
            navigator.vibrate?.([100,50,100]);
            flash('used');
            beep('used');
            render(d);
        }
        else{
            setStatus('❌ كود غير صالح','error');
            flash('error');
            beep('error');
        }

    })
    .finally(()=>setTimeout(()=>busy=false,120));
}

// 📸 START SCAN (زاوية أقوى)
qr.start(
    { facingMode: { exact: "environment" } },
    {
        fps: 20,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    },
    text=>{
        const now = Date.now();

        if(text === last && now - lastTime < 1000) return;
        if(busy) return;

        busy = true;
        last = text;
        lastTime = now;

        check(text);
    }
);
</script>
@endsection