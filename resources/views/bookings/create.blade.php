@extends('layouts.app')

@section('title', 'حجز تذاكر - ' . $showTime->show->title)

@section('content')
<section class="max-w-5xl mx-auto px-4">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- ======================
        | 🎭 DETAILS + PAYMENT
        ======================= --}}
        <div class="md:col-span-1 bg-black/40 border border-white/10 rounded-3xl p-5 space-y-4">

            <h2 class="text-sm font-semibold text-amber-300">🎭 تفاصيل العرض</h2>

            <p class="text-sm text-white font-medium">
                {{ $showTime->show->title }}
            </p>

            <div class="space-y-1 text-xs text-gray-300">
                <p>📅 {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</p>
                <p>⏰ {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</p>
                <p class="text-amber-300 font-semibold">
                    🎟️ {{ $showTime->ticket_price }} جنيه
                </p>
            </div>

            {{-- Step 1 --}}
            <div class="bg-black/50 border border-amber-400/30 rounded-2xl p-4 space-y-3">

                <h3 class="text-xs font-semibold text-amber-300">
                    خطوة 1: حوّل قيمة التذكرة
                </h3>

                <p class="text-[11px] text-gray-300">
                    حوّل <span class="text-white font-semibold">{{ $showTime->ticket_price }} جنيه</span>
                    على أحد الأرقام التالية:
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

                <p class="text-[10px] text-gray-400">
                    اكتب اسمك ورقمك أثناء التحويل.
                </p>
            </div>
        </div>

        {{-- ======================
        | 📝 FORM (STEP 2 & 3)
        ======================= --}}
        <div class="md:col-span-2 bg-black/40 border border-white/10 rounded-3xl p-6 space-y-4">

            <h2 class="text-sm font-semibold text-amber-300">
                خطوة 2: ارفع Screenshot وكمّل البيانات
            </h2>

            @if ($errors->any())
                <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-xs rounded-xl p-3">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('bookings.store', $showTime) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-4"
                  id="bookingForm">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <input type="text" name="full_name" id="full_name"
                           placeholder="الاسم بالكامل"
                           class="w-full rounded-xl bg-black/60 border border-white/15
                                  px-3 py-2 text-sm text-white
                                  focus:outline-none focus:border-amber-400">

                    <input type="text" name="phone" id="phone"
                           placeholder="رقم الموبايل (واتساب)"
                           class="w-full rounded-xl bg-black/60 border border-white/15
                                  px-3 py-2 text-sm text-white
                                  focus:outline-none focus:border-amber-400">
                </div>

                <input type="hidden" name="tickets_count" value="1">

                {{-- Screenshot --}}
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-1">
                    <label class="text-xs font-semibold text-white">
                        📸 Screenshot التحويل
                    </label>
                    <input type="file" name="payment_screenshot" id="screenshot"
                           accept="image/*"
                           class="w-full text-xs text-gray-300">
                </div>

                {{-- Submit --}}
                <button type="submit"
                        id="submitBtn"
                        disabled
                        class="w-full sm:w-auto px-6 py-2.5 rounded-full
                               bg-gray-600 text-black text-sm font-semibold
                               cursor-not-allowed transition">
                    إرسال طلب الحجز
                </button>

                <p class="text-[10px] text-gray-400">
                    الزر هيتفعّل تلقائي بعد استكمال البيانات.
                </p>
            </form>
        </div>

    </div>
</section>

{{-- ======================
| SMART ENABLE SCRIPT
====================== --}}
<script>
    const nameInput = document.getElementById('full_name');
    const phoneInput = document.getElementById('phone');
    const screenshotInput = document.getElementById('screenshot');
    const submitBtn = document.getElementById('submitBtn');

    function checkForm() {
        if (
            nameInput.value.trim() !== '' &&
            phoneInput.value.trim() !== '' &&
            screenshotInput.files.length > 0
        ) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('bg-gray-600', 'cursor-not-allowed');
            submitBtn.classList.add('bg-amber-400', 'hover:bg-amber-300');
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.add('bg-gray-600', 'cursor-not-allowed');
            submitBtn.classList.remove('bg-amber-400', 'hover:bg-amber-300');
        }
    }

    nameInput.addEventListener('input', checkForm);
    phoneInput.addEventListener('input', checkForm);
    screenshotInput.addEventListener('change', checkForm);
</script>
@endsection
