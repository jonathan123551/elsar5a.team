@extends('layouts.app')

@section('title', 'فحص التذاكر')

@section('content')
<section class="space-y-6 max-w-3xl mx-auto">

    <h1 class="text-2xl font-bold">🎫 فحص التذاكر</h1>

    <div class="bg-black/40 border border-white/10 rounded-2xl p-4">
        <div id="qr-reader" class="w-full max-w-[260px] mx-auto rounded-xl overflow-hidden"></div>
    </div>

    <div id="scan-status"
         class="text-sm bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-center text-gray-300">
        جاهز للفحص
    </div>

    <div id="booking-summary"
         class="hidden bg-white/5 border border-white/10 rounded-xl p-3 text-xs"></div>

</section>

<script src="https://unpkg.com/html5-qrcode"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {

const qr = new Html5Qrcode("qr-reader");

let busy = false;
let lastCode = null;
let lastTime = 0;

const IGNORE_MS = 1500; // 🔥 سريع

function render(d){
    const summary = document.getElementById('booking-summary');
    summary.classList.remove('hidden');

    summary.innerHTML = `
        <div>👤 ${d.name}</div>
        <div>📱 ${d.phone}</div>
        <div>🎭 ${d.show_title}</div>
        <div>🕒 ${d.date} • ${d.time}</div>
        ${d.scanned_at ? `<div>✅ ${d.scanned_at}</div>` : ''}
    `;
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

        const status = document.getElementById('scan-status');
        status.classList.remove('text-green-400','text-yellow-400','text-red-400');

        if(d.status==='ok'){
            status.textContent='✅ دخول مسموح';
            status.classList.add('text-green-400');
            navigator.vibrate?.(100);
            render(d);
        }
        else if(d.status==='used'){
            status.textContent='⚠️ مستخدمة قبل كده';
            status.classList.add('text-yellow-400');
            navigator.vibrate?.([100,50,100]);
            render(d);
        }
        else{
            status.textContent='❌ كود غير صالح';
            status.classList.add('text-red-400');
        }

    })
    .finally(()=>setTimeout(()=>busy=false,150));
}

qr.start(
    {facingMode:'environment'},
    {
        fps:15,
        qrbox:250
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