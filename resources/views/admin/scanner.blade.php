{{-- resources/views/admin/scanner.blade.php --}}
@extends('layouts.app')

@section('title', 'فحص التذاكر')

@section('content')
<section class="space-y-6 max-w-3xl mx-auto">

    <h1 class="text-2xl font-bold">🎫 فحص التذاكر</h1>

    <div class="bg-black/40 border border-white/10 rounded-2xl p-4">
        <div id="qr-wrapper" class="relative flex justify-center">
            <div id="qr-reader"
                 class="w-full max-w-[220px] rounded-xl overflow-hidden border-4 border-white/20"></div>

            {{-- BIG OVERLAY --}}
            <div id="scan-overlay"
                 class="absolute inset-0 flex items-center justify-center hidden">
                <div id="scan-icon"
                     class="scan-icon text-7xl font-black">
                </div>
            </div>
        </div>
    </div>

    <div id="scan-status"
         class="text-center text-sm bg-white/5 border border-white/10 rounded-xl py-2">
        جاهز للمسح
    </div>

</section>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>
/* === BIG APPLE PAY STYLE === */
.scan-icon {
    width: 140px;
    height: 140px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transform: scale(.3);
}

/* SUCCESS */
.scan-ok {
    background: #22c55e;
    box-shadow:
        0 0 0 10px rgba(34,197,94,.25),
        0 0 50px rgba(34,197,94,.9);
}

/* USED */
.scan-used {
    background: #facc15;
    color: black;
    box-shadow:
        0 0 0 10px rgba(250,204,21,.3),
        0 0 50px rgba(250,204,21,.9);
}

/* ERROR */
.scan-error {
    background: #ef4444;
    box-shadow:
        0 0 0 10px rgba(239,68,68,.3),
        0 0 50px rgba(239,68,68,.9);
}

/* POP ANIMATION */
@keyframes bigPop {
    0%   { transform: scale(.3); opacity: 0 }
    60%  { transform: scale(1.15); opacity: 1 }
    100% { transform: scale(1); opacity: 1 }
}

.pop {
    animation: bigPop .35s cubic-bezier(.2,.9,.3,1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const overlay = document.getElementById('scan-overlay');
    const icon    = document.getElementById('scan-icon');
    const status  = document.getElementById('scan-status');

    let busy = false;
    let lastCode = null;
    let lastTime = 0;

    const SAME_CODE_COOLDOWN = 30000;

    function show(type) {
        overlay.classList.remove('hidden');
        icon.className = 'scan-icon pop';

        if (type === 'ok') {
            icon.textContent = '✓';
            icon.classList.add('scan-ok');
            status.textContent = 'تم الدخول';
        }
        else if (type === 'used') {
            icon.textContent = '!';
            icon.classList.add('scan-used');
            status.textContent = 'تذكرة مستخدمة';
        }
        else {
            icon.textContent = '✕';
            icon.classList.add('scan-error');
            status.textContent = 'كود غير صالح';
        }

        setTimeout(() => {
            overlay.classList.add('hidden');
            icon.className = 'scan-icon';
        }, 600);
    }

    const qr = new Html5Qrcode("qr-reader");

    qr.start(
        { facingMode: "environment" },
        { fps: 30, qrbox: 180 },
        text => {
            const now = Date.now();

            if (text === lastCode && now - lastTime < SAME_CODE_COOLDOWN) {
                show('ok'); // feedback بس من غير سيرفر
                return;
            }

            if (busy) return;
            busy = true;

            lastCode = text;
            lastTime = now;

            fetch('/admin/scanner/check', {
                method: 'POST',
                headers: {
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':'{{ csrf_token() }}'
                },
                body: JSON.stringify({ code: text })
            })
            .then(r=>r.json())
            .then(d=>{
                show(d.status === 'ok' ? 'ok' : d.status === 'used' ? 'used' : 'error');
            })
            .finally(()=> setTimeout(()=>busy=false,400));
        }
    );
});
</script>
@endsection
