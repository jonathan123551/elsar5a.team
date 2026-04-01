@extends('layouts.app')

@section('title', 'Scanner')

@section('content')
<section class="max-w-md mx-auto space-y-4 px-3">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <h1 class="text-white text-lg font-bold">🎫 فحص التذاكر</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع
        </a>
    </div>

    {{-- SCANNER --}}
    <div class="relative bg-black/40 border border-white/10 rounded-2xl p-3">

        <div id="qr-reader"
             class="rounded-xl overflow-hidden border-2 border-white/10"></div>

        {{-- SCAN LINE --}}
        <div class="scan-line"></div>
    </div>

    {{-- STATUS --}}
    <div id="status"
         class="text-center py-3 rounded-xl bg-white/5 border border-white/10 text-sm transition-all">
        جاهز للفحص
    </div>

    {{-- RESULT --}}
    <div id="card"
         class="hidden bg-gradient-to-br from-white/10 to-white/5 border border-white/10 rounded-xl p-3 text-sm space-y-2">
    </div>

</section>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>
.scan-line{
    position:absolute;
    left:0;
    right:0;
    height:2px;
    background:linear-gradient(90deg,transparent,#22c55e,transparent);
    animation:scan 2s infinite;
}
@keyframes scan{
    0%{top:0}
    50%{top:90%}
    100%{top:0}
}

.glow-green{ box-shadow:0 0 15px rgba(34,197,94,.5); }
.glow-yellow{ box-shadow:0 0 15px rgba(250,204,21,.5); }
.glow-red{ box-shadow:0 0 15px rgba(239,68,68,.5); }
</style>

<script>
const qr = new Html5Qrcode("qr-reader");

let busy = false;
let last = null;
let lastTime = 0;

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

function setStatus(text, type){
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
            navigator.vibrate?.(80);
            render(d);
        }
        else if(d.status==='used'){
            setStatus('⚠️ مستخدمة قبل كده','used');
            navigator.vibrate?.([80,40,80]);
            render(d);
        }
        else{
            setStatus('❌ كود غير صالح','error');
        }

    })
    .finally(()=>setTimeout(()=>busy=false,150));
}

qr.start(
    {facingMode:'environment'},
    {fps:15, qrbox:250},
    text=>{
        const now = Date.now();

        if(text === last && now - lastTime < 1200) return;
        if(busy) return;

        busy = true;
        last = text;
        lastTime = now;

        check(text);
    }
);
</script>
@endsection