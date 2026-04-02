@extends('layouts.app')

@section('title', 'حجز تذاكر - ' . $showTime->show->title)

@section('content')
<section class="max-w-5xl mx-auto px-4">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- ======================
        | 🎭 DETAILS + PAYMENT
        ======================= --}}
<div class="md:col-span-1 relative
    bg-black/50
    border border-amber-400/40
    rounded-3xl p-5 space-y-4
    shadow-[0_0_100px_rgba(250,204,21,0.3)]">

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

    {{-- خطوة 1 --}}
    <div class="bg-black/40 border border-amber-400/20 rounded-2xl p-4 space-y-2">

        <h3 class="text-xs text-amber-300 font-semibold">
            خطوة 1: حوّل قيمة التذكرة
        </h3>

        <p class="text-[11px] text-gray-400">
            حوّل {{ $showTime->ticket_price }} جنيه على أحد الأرقام التالية:
        </p>

        <div class="bg-white/5 rounded-xl p-2">
            <p class="text-[10px] text-gray-400">📱 محفظة</p>
            <p class="text-sm font-bold text-white">{{ $transferWallet }}</p>
        </div>

        <div class="bg-white/5 rounded-xl p-2">
            <p class="text-[10px] text-gray-400">⚡ InstaPay</p>
            <p class="text-sm font-bold text-white">{{ $transferInsta }}</p>
        </div>

    </div>

</div>
        {{-- ======================
        | 📝 FORM
        ======================= --}}
        <div class="md:col-span-2 bg-black/50 border border-white/10 rounded-3xl p-6 space-y-4">

            <h2 class="text-sm font-semibold text-amber-300">
                خطوة 2: ارفع Screenshot وكمّل البيانات
            </h2>

            @if ($errors->any())
                <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-xs rounded-xl p-3">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('bookings.store', $showTime) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  id="bookingForm"
                  class="space-y-4">
                @csrf

                {{-- 👥 عدد التذاكر --}}
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-3">
                    <label class="text-xs font-semibold text-white">
                        👥 عدد التذاكر
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="changeCount(-1)" class="px-3 py-1 rounded bg-white/10">-</button>

                        <span id="ticketsCount" class="text-white font-bold">1</span>

                        <button type="button" onclick="changeCount(1)" class="px-3 py-1 rounded bg-white/10">+</button>
                    </div>

                    <input type="hidden" name="tickets_count" id="tickets_count" value="1">
                </div>

                {{-- 👤 الأشخاص --}}
                <div id="namesContainer" class="space-y-3"></div>

                {{-- Screenshot --}}
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-2">
                    <label class="text-xs font-semibold text-white">
                        📸 Screenshot التحويل
                    </label>

                    <input type="file"
                           name="payment_screenshot"
                           id="screenshot"
                           accept="image/*"
                           class="w-full text-xs text-gray-300">
                </div>

                <button type="submit"
                        id="submitBtn"
                        disabled
                        class="px-6 py-2.5 rounded-full
                               bg-gray-600 text-black text-sm font-semibold
                               cursor-not-allowed transition">
                    إرسال طلب الحجز
                </button>
            </form>
        </div>

    </div>
</section>


{{-- ======================
| SCRIPT
====================== --}}
<script>

let count = 1;
const maxTickets = {{ max(0, $showTime->total_tickets - $showTime->bookings()
    ->whereIn('status',['approved','pending'])
    ->sum('tickets_count')) }};

const namesContainer = document.getElementById('namesContainer');
const ticketsInput = document.getElementById('tickets_count');
const countDisplay = document.getElementById('ticketsCount');

function renderNames() {
    namesContainer.innerHTML = '';

    for (let i = 1; i <= count; i++) {
        namesContainer.innerHTML += `
            <div class="space-y-2 bg-black/40 border border-white/10 rounded-xl p-3">

                <input type="text"
                    name="names[]"
                    placeholder="اسم الشخص ${i}"
                    class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm text-white"
                    required>

                <input type="text"
                    name="phones[]"
                    placeholder="رقم موبايل واتساب ${i}"
                    class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm text-white"
                    required>

            </div>
        `;
    }
}

function changeCount(val) {
    count += val;

    if (count < 1) count = 1;

    if (count > maxTickets) {
        count = maxTickets;

        alert("❌ لا يوجد تذاكر متاحة، المتاح: " + maxTickets);
    }

    countDisplay.innerText = count;
    ticketsInput.value = count;

    renderNames();
}

renderNames();


// ===== زرار الإرسال =====
const screenshotInput = document.getElementById('screenshot');
const submitBtn = document.getElementById('submitBtn');
const bookingForm = document.getElementById('bookingForm');

let screenshotReady = false;
let isSubmitting = false;

function updateButton() {
    if (screenshotReady && !isSubmitting) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('bg-gray-600', 'cursor-not-allowed');
        submitBtn.classList.add('bg-amber-400');
    } else {
        submitBtn.disabled = true;
    }
}

screenshotInput.addEventListener('change', () => {
    screenshotReady = screenshotInput.files.length > 0;
    updateButton();
});

bookingForm.addEventListener('submit', function (e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }

    isSubmitting = true;
    submitBtn.disabled = true;
    submitBtn.innerText = 'جاري الإرسال...';
});

</script>

@endsection