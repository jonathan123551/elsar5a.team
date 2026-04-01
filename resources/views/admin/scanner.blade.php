@extends('layouts.app')

@section('title', 'فحص التذاكر')

@section('content')
<section class="space-y-6 max-w-3xl mx-auto px-3">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-white">🎫 فحص التذاكر</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع
        </a>
    </div>

    {{-- SCANNER --}}
    <div class="relative bg-black/40 border border-white/10 rounded-3xl p-4 shadow-[0_0_30px_rgba(0,0,0,0.5)]">

        <div id="qr-reader"
             class="w-full max-w-[280px] mx-auto rounded-2xl overflow-hidden border-4 border-white/10"></div>

        {{-- SCAN LINE --}}
        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
            <div class="scan-line"></div>
        </div>

    </div>

    {{-- STATUS --}}
    <div id="scan-status"
         class="text-sm bg-white/5 border border-white/10 rounded-xl px-3 py-3 text-center text-gray-300 transition-all">
        جاهز للفحص
    </div>

    {{-- RESULT CARD --}}
    <div id="booking-summary"
         class="hidden bg-gradient-to-br from-white/10 to-white/5 border border-white/10 rounded-2xl p-4 text-sm space-y-2 backdrop-blur">

    </div>

</section>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>
.scan-line{
    width: 80%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #22c55e, transparent);
    animation: scanMove 2s infinite;
}

@keyframes scanMove{
    0%{transform: translateY(-120px);}
    50%{transform: translateY(120px);}
    100%{transform: translateY(-120px);}
}

.glow-green{
    box-shadow: 0 0 20px rgba(34,197,94,0.6);
}

.glow-red{
    box-shadow: 0 0 20px rgba(239,68,68,0.6);
}

.glow-yellow{
    box-shadow: 0 0 20px rgba(250,204,21,0.6);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {

const qr = new Html5Qrcode("qr-reader");

let busy = false;
let lastCode = null;
let lastTime = 0;

const IGNORE_MS = 1200;

const status = document.getElementById('scan-status');
const summary = document.getElementById('booking-summary');

function render(d){
    summary.classList.remove('hidden');

    summary.innerHTML = `
        <div class="text-white font-bold text-base">${d.name}</div>
        <div class="text-gray-400 text-xs">${d.phone}</div>

        <div class="pt-2 border-t border-white/10"></div>

        <div>🎭 ${d.show_title}</div>
        <div>🕒 ${d.date} • ${d.time}</div>

        ${d.scanned_at ? `<div class="text-green-400">✅ ${d.scanned_at}</div>` : ''}
    `;
}

function setStatus(text, type){
    status.textContent = text;
    status.className = "text-sm rounded-xl px-3 py-3 text-center transition-all";

    if(type === 'ok'){
        status.classList.add('bg-green-500/10','text-green-400','glow-green');
    }
    else if(type === 'used'){
        status.classList.add('bg-yellow-500/10','text-yellow-400','glow-yellow');
    }
    else{
        status.classList.add('bg-red-500/10','text-red-400','glow-red');
    }
}

function beep(type){
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    osc.type = "sine";

    osc.frequency.value = type === 'ok' ? 800 : 300;

    osc.connect(ctx.destination);
    osc.start();
    setTimeout(()=>osc.stop(),120);
}

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
            setStatus('✅ دخول مسموح', 'ok');
            navigator.vibrate?.(100);
            beep('ok');
            render(d);
        }
        else if(d.status==='used'){
            setStatus('⚠️ مستخدمة قبل كده', 'used');
            navigator.vibrate?.([100,50,100]);
            beep('used');
            render(d);
        }
        else{
            setStatus('❌ كود غير صالح', 'error');
            beep('error');
        }

    })
    .finally(()=>setTimeout(()=>busy=false,120));
}

qr.start(
    {facingMode:'environment'},
    {
        fps:15,
        qrbox:260
    },
    text=>{
        const now = Date.now();

        if(text === lastCode && now - lastTime < IGNORE_MS) return;
        if(busy) return;

        busy = true;
        lastCode = text;
        lastTime = now;

        check(text);
    }
);

});
</script>
@endsection