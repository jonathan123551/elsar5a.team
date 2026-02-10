@extends('layouts.app')

@section('title', 'حجز تذاكر - ' . $showTime->show->title)

@section('content')
<section class="max-w-5xl mx-auto px-4">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- ======================
        | 🎭 DETAILS + PAYMENT
        ======================= --}}
        <div
            class="md:col-span-1 relative bg-black/50 border border-white/10
                   rounded-3xl p-5 space-y-4
                   shadow-[0_20px_60px_rgba(0,0,0,0.55)]
                   before:absolute before:inset-0 before:rounded-3xl
                   before:bg-gradient-to-br before:from-amber-400/10 before:to-transparent
                   before:pointer-events-none">

            <h2 class="text-sm font-semibold text-amber-300 tracking-wide">
                🎭 تفاصيل العرض
            </h2>

            <p class="text-sm text-white font-medium leading-snug">
                {{ $showTime->show->title }}
            </p>

            <div class="space-y-1 text-xs text-gray-300">
                <p>📅 {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</p>
                <p>⏰ {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</p>
                <p class="text-amber-300 font-semibold">
                    🎟️ {{ $showTime->ticket_price }} جنيه
                </p>
            </div>

            <div class="h-px bg-white/10 my-2"></div>

            <div
                class="relative bg-black/60 border border-amber-400/30
                       rounded-2xl p-4 space-y-3
                       shadow-[0_0_35px_rgba(250,204,21,0.15)]">

                <h3 class="text-xs font-semibold text-amber-300">
                    خطوة 1: حوّل قيمة التذكرة
                </h3>

                <p class="text-[11px] text-gray-300">
                    حوّل <span class="text-white font-semibold">{{ $showTime->ticket_price }} جنيه</span>
                    على:
                </p>

                <div class="bg-white/5 rounded-xl p-2">
                    <p class="text-[10px] text-gray-400">📱 محفظة</p>
                    <p class="text-sm font-bold text-white select-all">
                        {{ $transferWallet }}
                    </p>
                </div>

                <div class="bg-white/5 rounded-xl p-2">
                    <p class="text-[10px] text-gray-400">⚡ InstaPay</p>
                    <p class="text-sm font-bold text-white select-all">
                        {{ $transferInsta }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ======================
        | 📝 FORM
        ======================= --}}
        <div
            class="md:col-span-2 relative bg-black/50 border border-white/10
                   rounded-3xl p-6 space-y-4
                   shadow-[0_20px_60px_rgba(0,0,0,0.55)]
                   before:absolute before:inset-0 before:rounded-3xl
                   before:bg-gradient-to-br before:from-amber-400/5 before:to-transparent
                   before:pointer-events-none">

            <h2 class="text-sm font-semibold text-amber-300">
                خطوة 2: ارفع Screenshot وكمّل البيانات
            </h2>

            <form action="{{ route('bookings.store', $showTime) }}"
                  method="POST"
                  class="space-y-4"
                  id="bookingForm">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <input type="text" name="full_name" id="full_name"
                           placeholder="الاسم بالكامل"
                           class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm text-white">

                    <input type="text" name="phone" id="phone"
                           placeholder="رقم الموبايل (واتساب)"
                           class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm text-white">
                </div>

                <input type="hidden" name="tickets_count" value="1">

                {{-- Screenshot (UI كما هو) --}}
                <div
                    class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-2
                           hover:border-amber-400/40 hover:shadow-[0_0_25px_rgba(250,204,21,0.25)] transition">

                    <label class="text-xs font-semibold text-white">
                        📸 Screenshot التحويل
                    </label>

                    <input type="file"
                           id="screenshot"
                           accept="image/*"
                           class="w-full text-xs text-gray-300">

                    <p class="text-[10px] text-gray-400">
                        يتم رفع الصورة مباشرة بدون تحميلها على الموقع.
                    </p>
                </div>

                {{-- Cloudinary URL --}}
                <input type="hidden" name="payment_screenshot_url" id="screenshot_url">

                <button type="submit"
                        id="submitBtn"
                        disabled
                        class="w-full sm:w-auto px-6 py-2.5 rounded-full
                               bg-gray-600 text-black text-sm font-semibold cursor-not-allowed">
                    إرسال طلب الحجز
                </button>
            </form>
        </div>

    </div>
</section>

{{-- ======================
| CLOUDINARY DIRECT UPLOAD
====================== --}}
<script>
const fileInput = document.getElementById('screenshot');
const urlInput = document.getElementById('screenshot_url');
const submitBtn = document.getElementById('submitBtn');

const CLOUD_NAME = 'PUT_CLOUD_NAME';
const UPLOAD_PRESET = 'unsigned_upload';

fileInput.addEventListener('change', async () => {
    const file = fileInput.files[0];
    if (!file) return;

    submitBtn.disabled = true;
    submitBtn.innerText = 'جاري رفع الصورة...';

    const fd = new FormData();
    fd.append('file', file);
    fd.append('upload_preset', UPLOAD_PRESET);

    const res = await fetch(
        `https://api.cloudinary.com/v1_1/${CLOUD_NAME}/image/upload`,
        { method: 'POST', body: fd }
    );

    const data = await res.json();
    urlInput.value = data.secure_url;

    submitBtn.disabled = false;
    submitBtn.innerText = 'إرسال طلب الحجز';
});
</script>
@endsection
