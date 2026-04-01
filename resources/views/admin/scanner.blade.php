@extends('layouts.app')

@section('title', 'Scanner')

@section('content')
<section class="max-w-md mx-auto space-y-4 px-3 pb-10">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <h1 class="text-white text-lg font-bold">🎫 Gate Scanner</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع
        </a>
    </div>
    {{-- RESULT --}}
    <div id="card"
         class="hidden bg-gradient-to-br from-white/10 to-white/5 border border-white/10 rounded-xl p-3 text-sm space-y-2 backdrop-blur">
    </div>
       {{-- STATUS --}}
     <div id="status"
         class="text-center py-3 rounded-xl bg-white/5 border border-white/10 text-sm">
        جاهز للفحص
    </div>
    {{-- SCANNER --}}
    <div class="relative bg-black/70 border border-white/10 rounded-3xl p-3 overflow-hidden">

        <div id="qr-reader"
             class="rounded-2xl overflow-hidden border border-white/10"></div>

        {{-- FRAME --}}
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="scan-frame"></div>
        </div>

        {{-- LINE --}}
        <div class="scan-line"></div>

    </div>

    {{-- CONTROLS --}}
    <div class="flex gap-2">

        <button id="flashBtn"
                class="flex-1 text-xs py-2 rounded-xl bg-white/5 border border-white/10">
            🔦 Flash
        </button>

        <button onclick="location.reload()"
                class="flex-1 text-xs py-2 rounded-xl bg-white/5 border border-white/10">
            🔄 Restart
        </button>

    </div>

 
   

    

</section>

{{-- FLASH EFFECT --}}
<div id="flash" class="fixed inset-0 flex items-center justify-center hidden z-50">
    <div id="flashIcon" class="text-8xl font-black"></div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>
.scan-frame{
    width:230px;
    height:230px;
    border:2px solid rgba(255,255,255,0.2);
    border-radius:20px;
    box-shadow:0 0 30px rgba(255,255,255,0.1);
}

.scan-line{
    position:absolute;
    left:10%;
    right:10%;
    height:2px;
    background:linear-gradient(90deg,transparent,#22c55e,transparent);
    animation:scan 1.2s infinite;
}
@keyframes scan{
    0%{top:10%}
    50%{top:85%}
    100%{top:10%}
}

.flash-ok{ color:#22c55e; text-shadow:0 0 50px #22c55e; }
.flash-used{ color:#facc15; text-shadow:0 0 50px #facc15; }
.flash-error{ color:#ef4444; text-shadow:0 0 50px #ef4444; }

.glow-green{ box-shadow:0 0 25px rgba(34,197,94,.6); }
.glow-yellow{ box-shadow:0 0 25px rgba(250,204,21,.6); }
.glow-red{ box-shadow:0 0 25px rgba(239,68,68,.6); }
</style>
<script src="https://docs.opencv.org/4.x/opencv.js"></script>
<script src="https://unpkg.com/@zxing/library@latest"></script></script>
<script>
let video = document.createElement('video');
let canvas = document.createElement('canvas');
let ctx = canvas.getContext('2d');

let codeReader = new ZXing.BrowserQRCodeReader();

let busy = false;
let lastCode = null;
let lastTime = 0;

const COOLDOWN = 3000;

/* =========================
   🔊 SOUND
========================= */
let audioCtx;
function beep(){
    try{
        if(!audioCtx){
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        const osc = audioCtx.createOscillator();
        osc.frequency.value = 900;
        osc.connect(audioCtx.destination);
        osc.start();
        setTimeout(()=>osc.stop(),120);
    }catch(e){}
}

/* =========================
   📳 VIBRATION
========================= */
function vibrate(){
    navigator.vibrate?.(120);
}

/* =========================
   🎯 SEND TO BACKEND
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
        console.log("RESULT:", d);

        beep();
        vibrate();

        // ممكن تربط هنا UI بتاعك
    })
    .finally(()=>{
        setTimeout(()=>busy=false,700);
    });
}

/* =========================
   🚀 START CAMERA
========================= */
async function startCamera(){
    const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: "environment" }
    });

    video.srcObject = stream;
    video.setAttribute("playsinline", true);
    video.play();

    requestAnimationFrame(processFrame);
}

/* =========================
   🧠 AI PROCESSING
========================= */
function processFrame(){

    if(video.readyState === video.HAVE_ENOUGH_DATA){

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        ctx.drawImage(video, 0, 0);

        let src = cv.imread(canvas);

        // 🧠 grayscale
        let gray = new cv.Mat();
        cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);

        // 🧠 blur fix
        let blur = new cv.Mat();
        cv.GaussianBlur(gray, blur, new cv.Size(5,5), 0);

        // 🧠 contrast boost
        let thresh = new cv.Mat();
        cv.adaptiveThreshold(
            blur,
            thresh,
            255,
            cv.ADAPTIVE_THRESH_GAUSSIAN_C,
            cv.THRESH_BINARY,
            11,
            2
        );

        // 🔍 detect QR
        let detector = new cv.QRCodeDetector();
        let points = new cv.Mat();

        let data = detector.detectAndDecode(thresh, points);

        if(data){

            console.log("RAW QR:", data);

            // 🔥 لو مائل → نصلحه
            if(points.rows === 4){

                let dst = new cv.Mat();

                let srcTri = cv.matFromArray(4,1,cv.CV_32FC2, [
                    points.data32F[0], points.data32F[1],
                    points.data32F[2], points.data32F[3],
                    points.data32F[4], points.data32F[5],
                    points.data32F[6], points.data32F[7],
                ]);

                let dstTri = cv.matFromArray(4,1,cv.CV_32FC2, [
                    0,0,
                    300,0,
                    300,300,
                    0,300
                ]);

                let M = cv.getPerspectiveTransform(srcTri, dstTri);

                cv.warpPerspective(src, dst, M, new cv.Size(300,300));

                // 🧠 decode بعد التصحيح
                let tempCanvas = document.createElement('canvas');
                cv.imshow(tempCanvas, dst);

                codeReader.decodeFromCanvas(tempCanvas)
                    .then(result=>{
                        handleScan(result.text);
                    })
                    .catch(()=>{});

                dst.delete();
            }
            else{
                handleScan(data);
            }
        }

        src.delete();
        gray.delete();
        blur.delete();
        thresh.delete();
        points.delete();
    }

    requestAnimationFrame(processFrame);
}

/* =========================
   🔥 INIT
========================= */
window.onload = () => {
    startCamera();
};
</script>
@endsection