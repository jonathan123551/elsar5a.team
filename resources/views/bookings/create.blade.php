@extends('layouts.app')

@section('title', 'حجز تذاكر - ' . $showTime->show->title)

@section('content')
<section class="max-w-5xl mx-auto px-4">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- 🎭 DETAILS --}}
        <div class="md:col-span-1 relative bg-black/50 border border-white/10 rounded-3xl p-5 space-y-4">

            <h2 class="text-sm font-semibold text-amber-300">🎭 تفاصيل العرض</h2>

            <p class="text-sm text-white font-medium">
                {{ $showTime->show->title }}
            </p>

            <div class="text-xs text-gray-300 space-y-1">
                <p>📅 {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</p>
                <p>⏰ {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</p>
                <p class="text-amber-300 font-semibold">
                    🎟️ {{ $showTime->ticket_price }} جنيه
                </p>
            </div>

            <div class="h-px bg-white/10"></div>

            <div class="bg-black/60 border border-amber-400/30 rounded-2xl p-4 space-y-3">
                <h3 class="text-xs font-semibold text-amber-300">
                    خطوة 1: حوّل قيمة التذكرة
                </h3>

                <p class="text-[11px] text-gray-300">
                    حوّل <span class="text-white font-semibold">{{ $showTime->ticket_price }} جنيه</span> على:
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

        {{-- 📝 FORM --}}
        <div class="md:col-span-2 bg-black/50 border border-white/10 rounded-3xl p-6 space-y-4">

            <h2 class="text-sm font-semibold text-amber-300">
                خطوة 2: ارفع Screenshot وكمّل البيانات
            </h2>

            <form action="{{ route('bookings.store', $showTime) }}"
                  method="POST"
                  id="bookingForm"
                  class="space-y-4">
                @csrf

                <div class="grid sm:grid-cols-2 gap-4">
                    <input type="text" name="full_name" id="full_name"
                           placeholder="الاسم بالكامل"
                           class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm text-white">

                    <input type="text" name="phone" id="phone"
                           placeholder="رقم الموبايل (واتساب)"
                           class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm text-white">
                </div>

                <input type="hidden" name="tickets_count" value="1">

                {{-- Screenshot --}}
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-2">
                    <label class="text-xs font-semibold text-white">
                        📸 Screenshot التحويل
                    </label>

                    <input type="file" id="screenshot" accept="image/*"
                           class="w-full text-xs text-gray-300">

                    <p class="text-[10px] text-gray-400">
                        سيتم ضغط ورفع الصورة تلقائيًا.
                    </p>
                </div>

                {{-- Cloudinary URL --}}
                <input type="hidden"
                       name="payment_screenshot_url"
                       id="screenshot_url">

                <button type="submit"
                        id="submitBtn"
                        disabled
                        class="px-6 py-2.5 rounded-full bg-gray-600 text-black text-sm font-semibold cursor-not-allowed">
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
const screenshot = document.getElementById('screenshot');
const urlInput   = document.getElementById('screenshot_url');
const submitBtn  = document.getElementById('submitBtn');
const form       = document.getElementById('bookingForm');

const CLOUD_NAME = 'YOUR_CLOUD_NAME';
const PRESET     = 'unsigned_upload';

screenshot.addEventListener('change', async () => {
    const file = screenshot.files[0];
    if (!file) return;

    submitBtn.disabled = true;
    submitBtn.innerText = 'جاري رفع الصورة...';

    const fd = new FormData();
    fd.append('file', file);
    fd.append('upload_preset', PRESET);

    const res  = await fetch(
        `https://api.cloudinary.com/v1_1/${CLOUD_NAME}/image/upload`,
        { method: 'POST', body: fd }
    );

    const data = await res.json();

    urlInput.value = data.secure_url;

    submitBtn.disabled = false;
    submitBtn.innerText = 'إرسال طلب الحجز';
});

form.addEventListener('submit', function (e) {
    if (!urlInput.value) {
        e.preventDefault();
        alert('⚠️ من فضلك انتظر حتى يتم رفع الصورة قبل الإرسال');
    }
});
</script>
@endsection
