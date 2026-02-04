@extends('layouts.app')

@section('title', 'فحص التذاكر')

@section('content')
<section class="space-y-6 max-w-3xl mx-auto">

    <h1 class="text-2xl font-bold">🎫 فحص التذاكر</h1>

    {{-- الكاميرا --}}
    <div class="bg-black/40 border border-white/10 rounded-2xl p-4">
        <div id="qr-wrapper" class="relative flex justify-center">
            <div id="qr-reader"
                 class="w-full max-w-[220px] rounded-xl overflow-hidden border-4 border-white/20"></div>

            {{-- OVERLAY --}}
            <div id="scan-overlay"
                 class="absolute inset-0 flex items-center justify-center hidden">
                <div id="scan-icon" class="scan-icon text-7xl font-black"></div>
            </div>
        </div>
    </div>

    {{-- الحالة --}}
    <div id="scan-status"
         class="text-sm bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-center text-gray-300">
        جاهز للفحص
    </div>
    {{-- ملخص --}}
    <div id="booking-summary"
         class="hidden bg-white/5 border border-white/10 rounded-xl p-3 text-xs"></div>

    {{-- إدخال يدوي --}}
    <form id="manual-form" class="flex gap-2">
        @csrf
        <input id="code-input"
               placeholder="SRC-XXXX"
               class="flex-1 bg-black/60 border border-white/15 rounded-xl px-3 py-2 text-xs font-mono">
        <button class="px-4 py-2 bg-amber-400 rounded-full text-xs font-medium">
            فحص
        </button>
    </form>

    
</section>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>
.scan-icon{
    width:140px;height:140px;border-radius:9999px;
    display:flex;align-items:center;justify-content:center;
    opacity:0;transform:scale(.3);color:white;
}
.scan-ok{
    background:#22c55e;
    box-shadow:0 0 0 10px rgba(34,197,94,.25),0 0 50px rgba(34,197,94,.9);
}
.scan-used{
    background:#facc15;color:black;
    box-shadow:0 0 0 10px rgba(250,204,21,.3),0 0 50px rgba(250,204,21,.9);
}
.scan-error{
    background:#ef4444;
    box-shadow:0 0 0 10px rgba(239,68,68,.3),0 0 50px rgba(239,68,68,.9);
}
@keyframes pop{
    0%{transform:scale(.3);opacity:0}
    60%{transform:scale(1.15);opacity:1}
    100%{transform:scale(1);opacity:1}
}
.pop{animation:pop .35s cubic-bezier(.2,.9,.3,1)}

.status-ok{
    color:#22c55e; /* أخضر */
}
.status-used{
    color:#facc15; /* أصفر */
}
.status-error{
    color:#ef4444; /* أحمر */
}

</style>

<script>
document.addEventListener('DOMContentLoaded', () => {

const qr = new Html5Qrcode("qr-reader");
const overlay = document.getElementById('scan-overlay');
const icon = document.getElementById('scan-icon');
const status = document.getElementById('scan-status');
const summary = document.getElementById('booking-summary');
const input = document.getElementById('code-input');

let busy = false;
let lastCode = null;
let lastTime = 0;

const IGNORE_MS = 30000;

function showAnim(type){
    overlay.classList.remove('hidden');
    icon.className = 'scan-icon pop';

    if(type==='ok'){ icon.textContent='✓'; icon.classList.add('scan-ok'); }
    if(type==='used'){ icon.textContent='!'; icon.classList.add('scan-used'); }
    if(type==='error'){ icon.textContent='✕'; icon.classList.add('scan-error'); }

    setTimeout(()=>{
        overlay.classList.add('hidden');
        icon.className='scan-icon';
    },10000);
}

function render(d){
    summary.classList.remove('hidden');
    summary.innerHTML=`
        <div>👤 ${d.full_name}</div>
        <div>🎭 ${d.show_title}</div>
        <div>🕒 ${d.date} • ${d.time}</div>
        ${d.checked_in_at?`<div>✅ ${d.checked_in_at}</div>`:''}
    `;
}

function check(code){
    status.textContent='جارٍ الفحص...';
    summary.classList.add('hidden');

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
    // امسح أي لون قديم
    status.classList.remove('status-ok','status-used','status-error');

    if(d.status==='ok'){
        status.textContent='تم الدخول';
        status.classList.add('status-ok');
        showAnim('ok');
        render(d);
    }
    else if(d.status==='used'){
        status.textContent='تذكرة مستخدمة قبل كده';
        status.classList.add('status-used');
        showAnim('used');
        render(d);
    }
    else{
        status.textContent='كود غير صالح';
        status.classList.add('status-error');
        showAnim('error');
    }
})

    .finally(()=>setTimeout(()=>busy=false,400));
}

qr.start(
    {facingMode:'environment'},
    {fps:30,qrbox:180},
    text=>{
        const now=Date.now();
        if(text===lastCode && now-lastTime<IGNORE_MS) return;
        if(busy) return;

        busy=true;
        lastCode=text;
        lastTime=now;
        input.value=text;
        check(text);
    }
);

document.getElementById('manual-form').addEventListener('submit',e=>{
    e.preventDefault();
    if(busy) return;
    busy=true;
    check(input.value.trim());
});

});
</script>
@endsection
