@extends('layouts.app')

@section('title', 'Scanner')

@section('content')
<section class="max-w-md mx-auto space-y-4 px-3">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <h1 class="text-white text-lg font-bold">🎫 Scanner</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10">
            ← رجوع
        </a>
    </div>

    {{-- SCANNER --}}
    <div class="relative rounded-3xl overflow-hidden border border-white/10 bg-black/50 p-3">

        <div id="qr-reader" class="rounded-2xl overflow-hidden"></div>

        <div class="scan-line"></div>
    </div>

    {{-- STATUS --}}
    <div id="status"
         class="text-center py-3 rounded-xl text-sm bg-white/5 border border-white/10">
        جاهز
    </div>

    {{-- RESULT --}}
    <div id="card"
         class="hidden p-4 rounded-2xl bg-white/5 border border-white/10 text-sm space-y-2">
    </div>

</section>

{{-- FLASH --}}
<div id="flash" class="fixed inset-0 flex items-center justify-center hidden z-50">
    <div id="flashIcon" class="text-8xl"></div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>
.scan-line{
    position:absolute;
    top:0;
    left:0;
    right:0;
    height:2px;
    background:#22c55e;
    animation:scan 2s infinite;
}
@keyframes scan{
    0%{top:0}
    50%{top:90%}
    100%{top:0}
}
.flash-ok{color:#22c55e}
.flash-used{color:#facc15}
.flash-error{color:#ef4444}
</style>

<script>
const qr = new Html5Qrcode("qr-reader");

let busy=false;
let last=null;
let lastTime=0;

function flash(type){
    const f=document.getElementById('flash');
    const i=document.getElementById('flashIcon');

    f.classList.remove('hidden');

    if(type==='ok'){ i.textContent='✓'; i.className='flash-ok'; }
    if(type==='used'){ i.textContent='!'; i.className='flash-used'; }
    if(type==='error'){ i.textContent='✕'; i.className='flash-error'; }

    setTimeout(()=>f.classList.add('hidden'),700);
}

function beep(type){
    const ctx=new AudioContext();
    const o=ctx.createOscillator();
    o.frequency.value=type==='ok'?900:300;
    o.connect(ctx.destination);
    o.start();
    setTimeout(()=>o.stop(),100);
}

function render(d){
    const c=document.getElementById('card');
    c.classList.remove('hidden');

    c.innerHTML=`
        <div class="text-white font-bold">${d.name}</div>
        <div class="text-gray-400">${d.phone}</div>
        <div class="border-t border-white/10 pt-2"></div>
        <div>${d.show_title}</div>
        <div>${d.date} • ${d.time}</div>
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

        const s=document.getElementById('status');

        if(d.status==='ok'){
            s.textContent='✅ دخول';
            s.className='text-center py-3 rounded-xl bg-green-500/10 text-green-400';
            flash('ok'); beep('ok'); render(d);
        }
        else if(d.status==='used'){
            s.textContent='⚠️ مستخدمة';
            s.className='text-center py-3 rounded-xl bg-yellow-500/10 text-yellow-400';
            flash('used'); beep('used'); render(d);
        }
        else{
            s.textContent='❌ خطأ';
            s.className='text-center py-3 rounded-xl bg-red-500/10 text-red-400';
            flash('error'); beep('error');
        }

    })
    .finally(()=>setTimeout(()=>busy=false,120));
}

qr.start(
    {facingMode:'environment'},
    {fps:15, qrbox:250},
    text=>{
        const now=Date.now();

        if(text===last && now-lastTime<1200) return;
        if(busy) return;

        busy=true;
        last=text;
        lastTime=now;

        check(text);
    }
);
</script>
@endsection