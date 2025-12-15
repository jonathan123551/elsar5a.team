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
            ← رجوع للوحة التحكم
        </a>
    </div>

    <p class="text-xs text-gray-400">
        افتح الصفحة من موبايل المسؤول على الباب، واسمح للكاميرا.
    </p>

    {{-- الكاميرا --}}
    <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-3">
        <h2 class="text-sm font-semibold mb-1">الكاميرا / قارئ الـ QR</h2>

        <div id="qr-wrapper" class="relative flex justify-center">
            <div id="qr-reader"
                 class="w-full max-w-xs mx-auto rounded-xl overflow-hidden border-4 border-white/10 transition-all duration-150"></div>
        </div>

        <p id="camera-hint" class="text-[11px] text-gray-500 mt-2 hidden">
            لو الكاميرا مش شغالة، اتأكد إن المتصفح واخد صلاحية الكاميرا.
        </p>
    </div>

    {{-- نتيجة الفحص --}}
    <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-4 text-sm">
        <h2 class="text-sm font-semibold">نتيجة آخر فحص</h2>

        <div id="scan-status"
             class="text-xs px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-gray-300">
            لسه مفيش كود متفحوص.
        </div>

        {{-- إدخال يدوي --}}
        <div class="space-y-2">
            <label class="block text-xs text-gray-400">
                إدخال يدوي احتياطي:
            </label>
            <form id="manual-form" class="flex gap-2">
                @csrf
                <input id="code-input" type="text" name="code"
                       placeholder="SRC-XXXX"
                       class="flex-1 rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-xs focus:outline-none focus:border-amber-400 font-mono">

                <button type="submit"
                        class="px-4 py-2 rounded-full bg-amber-400 text-black text-xs font-medium hover:bg-amber-300 transition">
                    فحص
                </button>
            </form>
        </div>

        <div id="booking-summary"
             class="hidden text-xs bg-white/5 border border-white/10 rounded-xl p-3 mt-2">
        </div>
    </div>

</section>

{{-- مكتبة QR --}}
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const statusBox   = document.getElementById('scan-status');
    const summaryBox  = document.getElementById('booking-summary');
    const codeInput   = document.getElementById('code-input');
    const manualForm  = document.getElementById('manual-form');
    const cameraHint  = document.getElementById('camera-hint');
    const qrReaderBox = document.getElementById('qr-reader');

    const csrfToken = '{{ csrf_token() }}';

    // تحكم في الضغط على السيرفر
    let isProcessing  = false;
    let lastScanTime  = 0;
    const SCAN_COOLDOWN_MS = 600; // يقدر يقرا كود جديد كل0.6 ثانية

    // علشان نتجاهل نفس التذكرة لو اتقرت في خلال 5 ثواني
    let lastCode = null;
    let lastCodeScanTime = 0;
    const SAME_CODE_COOLDOWN_MS = 5000;

    function setStatus(text, type = 'normal') {
        let base = 'text-xs px-3 py-2 rounded-xl ';

        if (type === 'ok') {
            base += 'bg-emerald-500/15 border border-emerald-500/40 text-emerald-200';
        } else if (type === 'warn') {
            base += 'bg-amber-500/15 border border-amber-400/40 text-amber-200';
        } else if (type === 'error') {
            base += 'bg-red-500/15 border border-red-500/40 text-red-200';
        } else {
            base += 'bg-white/5 border border-white/10 text-gray-300';
        }

        statusBox.className = base;
        statusBox.textContent = text;
    }

    function flashGreen() {
        qrReaderBox.classList.add('border-emerald-400');
        setTimeout(() => {
            qrReaderBox.classList.remove('border-emerald-400');
        }, 200);
    }

    // شكل البوكس اللي تحت (المرجع / الضيف / العرض .. إلخ)
    function renderSummary(data) {
        summaryBox.classList.remove('hidden');

        const statusColor =
            data.status === 'ok'
                ? 'text-emerald-300'
                : (data.status === 'used' ? 'text-amber-300' : 'text-red-300');

        const checkedInRow = data.checked_in_at ? `
            <div>
                <span class="text-gray-400">وقت استخدام التذكرة:</span>
                <span class="text-emerald-300 font-semibold">${data.checked_in_at}</span>
            </div>
        ` : '';

        summaryBox.innerHTML = `
            <div class="space-y-1">
                
                <div>
                    <span class="text-gray-400">الضيف:</span>
                    ${data.full_name}
                </div>

                <div>
                    <span class="text-gray-400">العرض:</span>
                    ${data.show_title}
                </div>

                <div>
                    <span class="text-gray-400">الموعد:</span>
                    ${data.date} • ${data.time}
                </div>

                

                ${checkedInRow}
            </div>
        `;
    }

    function clearSummary() {
        summaryBox.classList.add('hidden');
        summaryBox.innerHTML = '';
    }

    function checkCode(code) {
        code = code.trim();
        if (!code) return;

        setStatus('جارٍ الفحص...');
        clearSummary();

        return fetch("/admin/scanner/check", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ code })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'ok') {
                flashGreen();
                setStatus('✅ التذكرة صالحة', 'ok');
                renderSummary(data);
            } else if (data.status === 'used') {
                setStatus('⚠️ التذكرة مستخدمة قبل كده', 'warn');
                renderSummary(data);
            } else {
                setStatus('❌ الكود غير صالح', 'error');
                clearSummary();
            }
        })
        .catch(() => {
            setStatus('خطأ في الاتصال بالسيرفر', 'error');
            clearSummary();
        });
    }

    // إدخال يدوي
    manualForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (isProcessing) return;
        isProcessing = true;
        checkCode(codeInput.value).finally(() => {
            setTimeout(() => { isProcessing = false; }, 200);
        });
    });

    // بيتندّه من الكاميرا لما تقرأ كود
    function handleScan(decodedText) {
        const now = Date.now();
        const code = decodedText.trim();

        // 1) كول داون عام علشان ما يضربش السيرفر كل فريم
        if (isProcessing || (now - lastScanTime) < SCAN_COOLDOWN_MS) return;

        // 2) تجاهل نفس الكود لو اتقرا تاني خلال 3 ثواني
        if (code === lastCode && (now - lastCodeScanTime) < SAME_CODE_COOLDOWN_MS) {
            return;
        }

        lastScanTime      = now;
        lastCode          = code;
        lastCodeScanTime  = now;
        isProcessing      = true;

        codeInput.value = code;

        checkCode(code).finally(() => {
            setTimeout(() => { isProcessing = false; }, 200);
        });
    }

    // تشغيل الكاميرا
    const html5QrCode = new Html5Qrcode("qr-reader");

    Html5Qrcode.getCameras().then(devices => {
        if (!devices.length) {
            cameraHint.classList.remove('hidden');
            return;
        }

        const config = {
            fps: 25,
            qrbox: { width: 220, height: 220 },
            aspectRatio: 1,
            disableFlip: true
        };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            handleScan,
            function(errorMessage) {
                // ممكن تتجاهل الأخطاء اللحظية هنا
            }
        ).catch(() => {
            cameraHint.classList.remove('hidden');
            setStatus('فشل تشغيل الكاميرا', 'error');
        });
    }).catch(() => {
        cameraHint.classList.remove('hidden');
        setStatus('تعذر الوصول للكاميرا', 'error');
    });

});
</script>
@endsection
