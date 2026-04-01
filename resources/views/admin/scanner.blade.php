@extends('layouts.app')

@section('title', 'Scanner')

@section('content')
<section class="max-w-md mx-auto space-y-4 px-3">

    <div class="flex justify-between items-center">
        <h1 class="text-white font-bold">🎫 فحص التذاكر</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10">
            ← رجوع
        </a>
    </div>

    <div class="bg-black/40 border border-white/10 rounded-2xl p-3">
        <div id="qr-reader" class="rounded-xl overflow-hidden"></div>
    </div>

    <div id="status"
         class="text-center py-3 rounded-xl bg-white/5 border border-white/10 text-sm">
        جاهز
    </div>

    <div id="card"
         class="hidden bg-white/5 border border-white/10 rounded-xl p-3 text-sm space-y-1">
    </div>

</section>

<script src="https://unpkg.com/html5-qrcode"></script>

<script>
const qr = new Html5Qrcode("qr-reader");

let busy = false;
let last = null;
let lastTime = 0;

function render(d){
    const c = document.getElementById('card');
    c.classList.remove('hidden');

    c.innerHTML = `
        <div class="font-bold text-white">${d.name}</div>
        <div class="text-gray-400 text-xs">${d.phone}</div>
        <div class="mt-2 border-t border-white/10 pt-2"></div>
        <div>${d.show_title}</div>
        <div>${d.date} • ${d.time}</div>
        ${d.scanned_at ? `<div class="text-green-400">🕒 ${d.scanned_at}</div>` : ''}
    `;
}

function setStatus(text, color){
    const s = document.getElementById('status');

    s.textContent = text;
    s.className = "text-center py-3 rounded-xl text-sm " + color;
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
            setStatus('✅ دخول مسموح','bg-green-500/10 text-green-400');
            navigator.vibrate?.(80);
            render(d);
        }
        else if(d.status==='used'){
            setStatus('⚠️ مستخدمة قبل كده','bg-yellow-500/10 text-yellow-400');
            navigator.vibrate?.([80,40,80]);
            render(d);
        }
        else{
            setStatus('❌ خطأ','bg-red-500/10 text-red-400');
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