@extends('layouts.app')

@section('title', 'Scanner')

@section('content')
<section class="max-w-md mx-auto space-y-4 px-3 pb-10">

    <div class="flex justify-between items-center">
        <h1 class="text-white text-lg font-bold">🎫 Hybrid Scanner</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10">
            ← رجوع
        </a>
    </div>

    <div id="qr-reader"
         class="rounded-2xl overflow-hidden border border-white/10"></div>

    <div id="status"
         class="text-center py-3 rounded-xl bg-white/5 border border-white/10 text-sm">
        جاهز للفحص
    </div>

</section>

{{-- LIBS --}}
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://docs.opencv.org/4.x/opencv.js"></script>
<script src="https://unpkg.com/@zxing/library@latest"></script>

<script>

/* =========================
   ⚡ FAST SCANNER
========================= */
const qr = new Html5Qrcode("qr-reader");

/* =========================
   🧠 AI SETUP
========================= */
let video = document.createElement('video');
let canvas = document.createElement('canvas');
let ctx = canvas.getContext('2d');
let detector;

/* =========================
   STATE
========================= */
let busy = false;
let lastCode = null;
let lastTime = 0;

const COOLDOWN = 2500;

/* =========================
   🔊 SOUND + VIBRATION
========================= */
function feedback(type){
    navigator.vibrate?.(type==='ok'?120:200);

    try{
        const ctx = new (window.AudioContext||window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        osc.frequency.value = type==='ok'?900:300;
        osc.connect(ctx.destination);
        osc.start();
        setTimeout(()=>osc.stop(),120);
    }catch(e){}
}

/* =========================
   🎯 UI
========================= */
function setStatus(text,type){
    const s = document.getElementById('status');
    s.textContent = text;

    if(type==='ok') s.style.color='#22c55e';
    else if(type==='used') s.style.color='#facc15';
    else s.style.color='#ef4444';
}

/* =========================
   🚀 SEND
========================= */
function handleScan(code){

    const now = Date.now();

    if(code === lastCode && now - lastTime < COOLDOWN){
        return;
    }

    if(busy) return;

    busy = true;
    lastCode = code;
    lastTime = now;

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
            setStatus('✅ دخول','ok');
        }else if(d.status==='used'){
            setStatus('⚠️ مستخدمة','used');
        }else{
            setStatus('❌ خطأ','error');
        }

        feedback(d.status);

    })
    .finally(()=>{
        setTimeout(()=>busy=false,500);
    });
}

/* =========================
   🧠 AI FALLBACK
========================= */
let lastAI = 0;

function processFrame(){

    const now = Date.now();
    if(now - lastAI < 200) return requestAnimationFrame(processFrame);
    lastAI = now;

    if(video.readyState === video.HAVE_ENOUGH_DATA){

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        ctx.drawImage(video,0,0);

        let src = cv.imread(canvas);

        let gray = new cv.Mat();
        cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);

        let data = detector.detectAndDecode(gray);

        if(data){
            handleScan(data);
        }

        src.delete();
        gray.delete();
    }

    requestAnimationFrame(processFrame);
}

/* =========================
   🚀 INIT
========================= */
window.onload = () => {

    // ⚡ fast scanner
    qr.start(
        { facingMode: "environment" },
        {
            fps: 15,
            qrbox: 260,
            experimentalFeatures:{
                useBarCodeDetectorIfSupported:true
            }
        },
        text=>{
            handleScan(text);
        }
    );

    // 🧠 AI init
    cv['onRuntimeInitialized'] = async () => {

        detector = new cv.QRCodeDetector();

        const stream = await navigator.mediaDevices.getUserMedia({
            video:{facingMode:"environment"}
        });

        video.srcObject = stream;
        video.play();

        processFrame();
    };
};

</script>
@endsection