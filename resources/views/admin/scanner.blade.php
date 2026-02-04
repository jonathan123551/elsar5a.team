{{-- resources/views/admin/scanner.blade.php --}}
@extends('layouts.app')

@section('title', 'وضع فحص التذاكر')

@section('content')
<section class="space-y-6 max-w-3xl mx-auto">

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            🎫 وضع فحص التذاكر
        </h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع
        </a>
    </div>

    <p class="text-xs text-gray-400">
        افتح الصفحة من موبايل المسؤول واسمح للكاميرا.
    </p>

    {{-- الكاميرا --}}
    <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-3">
        <h2 class="text-sm font-semibold">فحص QR</h2>

        <div id="qr-wrapper" class="relative flex justify-center">
            <div id="qr-reader"
                 class="w-full max-w-[220px] mx-auto rounded-xl overflow-hidden border-4 border-white/10 transition-all"></div>

            {{-- Overlay --}}
            <div id="scan-overlay"
                 class="pointer-events-none absolute inset-0 flex items-center justify-center hidden">
                <div id="scan-icon"
                     class="w-20 h-20 rounded-full flex items-center justify-center text-4xl font-bold scale-50 opacity-0">
                </div>
            </div>
        </div>

        <p id="camera-hint" class="text-[11px] text-gray-500 hidden">
            تأكد إن المتصفح واخد صلاحية الكاميرا
        </p>
    </div>

    {{-- النتيجة --}}
    <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-4 text-sm">
        <h2 class="text-sm font-semibold">آخر فحص</h2>

        <div id="scan-status"
             class="text-xs px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-gray-300">
            لم يتم الفحص بعد
        </div>

        {{-- إدخال يدوي --}}
        <form id="manual-form" class="flex gap-2">
            @csrf
            <input id="code-input" type="text"
                   placeholder="SRC-XXXX"
                   class="flex-1 rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-xs font-mono">

            <button class="px-4 py-2 rounded-full bg-amber-400 text-black text-xs font-medium">
                فحص
            </button>
        </form>

        <div id="booking-summary"
             class="hidden text-xs bg-white/5 border border-white/10 rounded-xl p-3">
        </div>
    </div>

</section>

{{-- QR LIB --}}
<script src="https://unpkg.com/html5-qrcode"></script>

<style>
@keyframes pop {
    0% { transform: scale(.5); opacity: 0 }
    60% { transform: scale(1.1); opacity: 1 }
    100% { transform: scale(1); opacity: 1 }
}
.scan-pop {
    animation: pop .25s ease-out;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const statusBox  = document.getElementById('scan-status');
    const summaryBox = document.getElementById('booking-summary');
    const codeInput  = document.getElementById('code-input');
    const overlay    = document.getElementById('scan-overlay');
    const icon       = document.getElementById('scan-icon');
    const qrBox      = document.getElementById('qr-reader');

    const csrf = '{{ csrf_token() }}';

    let busy = false;
    let lastScan = 0;
    let lastCode = null;
    let lastCodeTime = 0;

    const SCAN_COOLDOWN = 400;
    const SAME_CODE_COOLDOWN = 3000;

    function vibrate(type) {
        if (!navigator.vibrate) return;
        if (type === 'ok') navigator.vibrate(40);
        else if (type === 'used') navigator.vibrate([30,40,30]);
        else navigator.vibrate([60,40,60]);
    }

    function showOverlay(type) {
        overlay.classList.remove('hidden');
        icon.className = 'scan-pop';

        if (type === 'ok') {
            icon.textContent = '✓';
            icon.classList.add('bg-emerald-500','text-white');
            vibrate('ok');
        } else if (type === 'used') {
            icon.textContent = '!';
            icon.classList.add('bg-amber-400','text-black');
            vibrate('used');
        } else {
            icon.textContent = '✕';
            icon.classList.add('bg-red-500','text-white');
            vibrate('error');
        }

        setTimeout(() => {
            overlay.classList.add('hidden');
            icon.className = '';
        }, 500);
    }

    function flash() {
        qrBox.classList.add('ring-4','ring-emerald-400');
        setTimeout(() => qrBox.classList.remove('ring-4','ring-emerald-400'), 180);
    }

    function setStatus(text, type='normal') {
        const map = {
            ok: 'bg-emerald-500/15 text-emerald-200 border-emerald-500/40',
            warn: 'bg-amber-500/15 text-amber-200 border-amber-400/40',
            error: 'bg-red-500/15 text-red-200 border-red-500/40',
            normal: 'bg-white/5 text-gray-300 border-white/10'
        };
        statusBox.className = `text-xs px-3 py-2 rounded-xl border ${map[type]}`;
        statusBox.textContent = text;
    }

    function renderSummary(d) {
        summaryBox.classList.remove('hidden');
        summaryBox.innerHTML = `
            <div class="space-y-1">
                <div><span class="text-gray-400">الضيف:</span> ${d.full_name}</div>
                <div><span class="text-gray-400">العرض:</span> ${d.show_title}</div>
                <div><span class="text-gray-400">الموعد:</span> ${d.date} • ${d.time}</div>
                ${d.checked_in_at ? `<div><span class="text-gray-400">الدخول:</span> ${d.checked_in_at}</div>` : ''}
            </div>
        `;
    }

    function check(code) {
        setStatus('جارٍ الفحص...');
        summaryBox.classList.add('hidden');

        return fetch('/admin/scanner/check', {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'X-CSRF-TOKEN':csrf
            },
            body:JSON.stringify({code})
        })
        .then(r=>r.json())
        .then(d=>{
            if (d.status==='ok') {
                flash(); showOverlay('ok');
                setStatus('تم الدخول ✓','ok');
                renderSummary(d);
            } else if (d.status==='used') {
                showOverlay('used');
                setStatus('تذكرة مستخدمة','warn');
                renderSummary(d);
            } else {
                showOverlay('error');
                setStatus('كود غير صالح','error');
            }
        })
        .finally(()=> setTimeout(()=>busy=false,200));
    }

    document.getElementById('manual-form').addEventListener('submit',e=>{
        e.preventDefault();
        if (busy) return;
        busy=true;
        check(codeInput.value.trim());
    });

    const qr = new Html5Qrcode("qr-reader");

    Html5Qrcode.getCameras().then(() => {
        qr.start(
            { facingMode:'environment' },
            { fps:30, qrbox:{width:180,height:180} },
            text=>{
                const now=Date.now();
                if (busy || now-lastScan<SCAN_COOLDOWN) return;
                if (text===lastCode && now-lastCodeTime<SAME_CODE_COOLDOWN) return;

                lastScan=now;
                lastCode=text;
                lastCodeTime=now;
                busy=true;
                codeInput.value=text;
                check(text);
            }
        );
    });

});
</script>
@endsection
