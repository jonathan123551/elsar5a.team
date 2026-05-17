@extends('layouts.app')

@section('title', 'حجز تذاكر - ' . $showTime->show->title)

@section('content')
{{--
    Public booking flow — mobile-first.

    UX rules:
    - Every tap target is ≥ 44×44 (Apple HIG / Material).
    - Inputs are at least 16px logical so iOS Safari doesn't auto-zoom.
    - The submit CTA is bound to the sticky-action-footer pattern so
      the operator/customer always has one tap away on long forms.
    - The submit button is disabled until ALL required fields are
      filled (name + phone for every ticket + screenshot), not just
      the screenshot like before.
    - Double-submit prevention is layered:
        1. Idempotency token (hidden input) generated client-side,
           validated server-side via Cache::add() so a refresh / back
           button / repeated POST is rejected.
        2. The form's submit handler immediately disables the button,
           swaps text to "جاري الإرسال…" with a spinner, and ignores
           further submit events.
        3. Server-side `Cache::add('booking_lock_…', true, 20)` lock
           (already in BookingController::store) catches anything that
           sneaks past the client guard, e.g. dual-tab submit.
--}}
<section class="max-w-5xl mx-auto px-4 sm:px-6">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-6">

        {{-- ======================
        | 🎭 DETAILS + PAYMENT
        ======================= --}}
        <aside class="md:col-span-1 relative
            bg-black/55
            border border-amber-400/40
            rounded-3xl p-5 space-y-4
            shadow-[0_0_60px_rgba(250,204,21,0.18)]
            md:sticky md:top-24 md:self-start">

            <h2 class="text-sm font-semibold text-amber-300">🎭 تفاصيل العرض</h2>

            <p class="text-base sm:text-sm text-white font-medium leading-snug">
                {{ $showTime->show->title }}
            </p>

            <div class="space-y-1.5 text-[13px] sm:text-xs text-gray-300">
                <p>📅 {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</p>
                <p>⏰ {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</p>
                <p class="text-amber-300 font-semibold">
                    🎟️ {{ $showTime->ticket_price }} جنيه / تذكرة
                </p>
            </div>

            {{-- خطوة 1: payment numbers --}}
            <div class="bg-black/40 border border-amber-400/20 rounded-2xl p-4 space-y-2.5">

                <h3 class="text-xs text-amber-300 font-semibold">
                    خطوة 1: حوّل قيمة التذكرة
                </h3>

                <p class="text-[12px] sm:text-[11px] text-gray-400 leading-relaxed">
                    حوّل المبلغ المطلوب على أحد الأرقام التالية، ثم ارفع لقطة شاشة من التحويل في الخطوة 2.
                </p>

                {{-- Each payment number is tappable: tap to copy. --}}
                <button type="button"
                        data-copy="{{ $transferWallet }}"
                        class="w-full text-right bg-white/5 hover:bg-white/10 active:bg-white/15
                               rounded-xl p-3 transition flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[10px] text-gray-400 mb-0.5">📱 محفظة</p>
                        <p class="text-sm font-bold text-white truncate" dir="ltr">{{ $transferWallet }}</p>
                    </div>
                    <span class="copy-hint text-[10px] text-amber-300 shrink-0">نسخ</span>
                </button>

                <button type="button"
                        data-copy="{{ $transferInsta }}"
                        class="w-full text-right bg-white/5 hover:bg-white/10 active:bg-white/15
                               rounded-xl p-3 transition flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[10px] text-gray-400 mb-0.5">⚡ InstaPay</p>
                        <p class="text-sm font-bold text-white truncate" dir="ltr">{{ $transferInsta }}</p>
                    </div>
                    <span class="copy-hint text-[10px] text-amber-300 shrink-0">نسخ</span>
                </button>

            </div>

        </aside>

        {{-- ======================
        | 📝 FORM
        ======================= --}}
        <div class="md:col-span-2 bg-black/55 border border-white/10 rounded-3xl p-5 sm:p-6 space-y-5">

            <h2 class="text-sm font-semibold text-amber-300">
                خطوة 2: ارفع Screenshot وكمّل البيانات
            </h2>

            @if ($errors->any())
                <div role="alert"
                     class="bg-red-500/10 border border-red-500/40 text-red-200 text-[13px] sm:text-xs rounded-xl p-3">
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
                  novalidate
                  class="space-y-4">
                @csrf

                {{-- Idempotency token (see comment block at the top of
                     this file). Generated client-side so a refresh
                     of an already-submitted form produces the same
                     token and is rejected by the server cache. --}}
                <input type="hidden" name="idempotency_token" id="idempotencyToken" value="">

                {{-- 👥 عدد التذاكر --}}
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <label class="text-sm font-semibold text-white">
                            👥 عدد التذاكر
                        </label>
                        <span id="maxHint" class="text-[11px] text-gray-400"></span>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button"
                                aria-label="إنقاص"
                                onclick="changeCount(-1)"
                                class="w-11 h-11 rounded-xl bg-white/10 hover:bg-white/15 active:bg-white/20
                                       text-white text-xl font-bold flex items-center justify-center transition">
                            −
                        </button>

                        <span id="ticketsCount"
                              class="flex-1 text-center text-white font-extrabold text-2xl tabular-nums">1</span>

                        <button type="button"
                                aria-label="زيادة"
                                onclick="changeCount(1)"
                                class="w-11 h-11 rounded-xl bg-white/10 hover:bg-white/15 active:bg-white/20
                                       text-white text-xl font-bold flex items-center justify-center transition">
                            +
                        </button>
                    </div>

                    <p id="totalPriceHint" class="text-[12px] text-amber-300 font-semibold"></p>

                    <input type="hidden" name="tickets_count" id="tickets_count" value="1">
                </div>

                {{-- 👤 الأشخاص --}}
                <div id="namesContainer" class="space-y-3"></div>

                {{-- Screenshot --}}
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-3">
                    <label for="screenshot" class="text-sm font-semibold text-white block">
                        📸 لقطة شاشة من التحويل
                    </label>

                    <label for="screenshot"
                           id="screenshotDropzone"
                           class="block cursor-pointer rounded-xl border-2 border-dashed
                                  border-white/20 hover:border-amber-400/60 transition
                                  bg-black/40 p-4 text-center">
                        <div id="screenshotEmptyState">
                            <p class="text-[28px] leading-none mb-1">📎</p>
                            <p class="text-sm text-white font-semibold">اختر صورة من معرض الصور</p>
                            <p class="text-[11px] text-gray-400 mt-1">PNG / JPG حتى 5MB</p>
                        </div>
                        <div id="screenshotPreviewWrap" class="hidden">
                            <img id="screenshotPreview" alt=""
                                 class="mx-auto max-h-48 rounded-xl border border-white/10 object-contain">
                            <p id="screenshotFileName" class="text-[11px] text-gray-300 mt-2 truncate"></p>
                            <p class="text-[11px] text-amber-300 mt-1">اضغط لاستبدال الصورة</p>
                        </div>
                    </label>

                    <input type="file"
                           name="payment_screenshot"
                           id="screenshot"
                           accept="image/*"
                           class="hidden">

                    <p id="screenshotError"
                       class="hidden text-[12px] text-red-300"></p>
                </div>

                {{-- Natural-position submit (also drives the sticky footer clone). --}}
                <div data-sticky-action class="pt-1">
                    <button type="submit"
                            id="submitBtn"
                            disabled
                            class="w-full px-6 py-3.5 rounded-2xl
                                   bg-gray-600 text-black/80 text-sm font-bold
                                   disabled:cursor-not-allowed transition
                                   shadow-[0_8px_30px_rgba(250,204,21,0.0)]
                                   flex items-center justify-center gap-2">
                        <span id="submitLabel">إرسال طلب الحجز</span>
                    </button>
                    <p id="submitHint"
                       class="mt-2 text-center text-[11px] text-gray-400">
                        أكمل البيانات وارفع لقطة الشاشة لتفعيل زر الإرسال
                    </p>
                </div>
            </form>
        </div>

    </div>
</section>


{{-- ======================
| SCRIPT
====================== --}}
<script>
(function () {
    // --- Tickets state ---------------------------------------------
    var count = {{ (int) old('tickets_count', 1) }};
    var maxTickets = {{ max(0, $showTime->total_tickets - $showTime->bookings()
        ->whereIn('status', ['approved', 'pending'])
        ->sum('tickets_count')) }};
    var ticketPrice = {{ (int) $showTime->ticket_price }};

    var namesContainer = document.getElementById('namesContainer');
    var ticketsInput   = document.getElementById('tickets_count');
    var countDisplay   = document.getElementById('ticketsCount');
    var maxHint        = document.getElementById('maxHint');
    var totalPriceHint = document.getElementById('totalPriceHint');

    // Preserve old() values when the server bounced the form back
    // with validation errors so the user doesn't have to retype.
    var oldNames  = @json(old('names', []));
    var oldPhones = @json(old('phones', []));

    function renderNames() {
        namesContainer.innerHTML = '';
        for (var i = 1; i <= count; i++) {
            var idx = i - 1;
            var nameVal  = String(oldNames[idx]  || '').replace(/"/g, '&quot;');
            var phoneVal = String(oldPhones[idx] || '').replace(/"/g, '&quot;');

            namesContainer.insertAdjacentHTML('beforeend',
                '<div class="space-y-2 bg-black/40 border border-white/10 rounded-2xl p-3">' +
                  '<p class="text-[11px] text-gray-400">شخص ' + i + '</p>' +
                  '<input type="text" name="names[]" value="' + nameVal + '"' +
                    ' placeholder="الاسم بالكامل"' +
                    ' autocomplete="name"' +
                    ' inputmode="text"' +
                    ' class="booking-input w-full rounded-xl bg-black/60 border border-white/15 px-3 py-3 text-white"' +
                    ' required>' +
                  '<input type="tel" name="phones[]" value="' + phoneVal + '"' +
                    ' placeholder="رقم واتساب (مثال 01012345678)"' +
                    ' autocomplete="tel"' +
                    ' inputmode="tel"' +
                    ' pattern="[0-9+\\s\\-]{8,16}"' +
                    ' class="booking-input w-full rounded-xl bg-black/60 border border-white/15 px-3 py-3 text-white"' +
                    ' required>' +
                '</div>'
            );
        }
        wireInputs();
        recomputeSubmitState();
    }

    function changeCount(val) {
        var prev = count;
        count += val;
        if (count < 1) count = 1;
        if (count > maxTickets) {
            count = Math.max(1, maxTickets);
            flashMax();
        }
        if (count === prev) {
            updatePriceHint();
            return;
        }

        // Stash what the user typed so re-rendering doesn't blow away
        // valid entries when they tap +/− mid-flow.
        var existingNames  = namesContainer.querySelectorAll('input[name="names[]"]');
        var existingPhones = namesContainer.querySelectorAll('input[name="phones[]"]');
        oldNames  = Array.prototype.map.call(existingNames,  function (n) { return n.value; });
        oldPhones = Array.prototype.map.call(existingPhones, function (n) { return n.value; });

        countDisplay.innerText = count;
        ticketsInput.value     = count;
        updatePriceHint();
        renderNames();
    }
    window.changeCount = changeCount;

    function updatePriceHint() {
        totalPriceHint.innerText =
            'الإجمالي: ' + (count * ticketPrice).toLocaleString('ar-EG') + ' جنيه';
        maxHint.innerText = maxTickets > 0
            ? 'المتاح: ' + maxTickets + ' تذكرة'
            : 'لا تذاكر متاحة حاليًا';
    }

    function flashMax() {
        if (!countDisplay.animate) return;
        countDisplay.animate(
            [{ transform: 'scale(1)' }, { transform: 'scale(1.18)' }, { transform: 'scale(1)' }],
            { duration: 260 }
        );
    }

    // --- Submit gate -----------------------------------------------
    var screenshotInput  = document.getElementById('screenshot');
    var previewWrap      = document.getElementById('screenshotPreviewWrap');
    var emptyState       = document.getElementById('screenshotEmptyState');
    var previewImg       = document.getElementById('screenshotPreview');
    var fileNameEl       = document.getElementById('screenshotFileName');
    var screenshotError  = document.getElementById('screenshotError');
    var submitBtn        = document.getElementById('submitBtn');
    var submitLabel      = document.getElementById('submitLabel');
    var submitHint       = document.getElementById('submitHint');
    var bookingForm      = document.getElementById('bookingForm');

    var isSubmitting = false;

    function fieldsValid() {
        var ok = true;
        namesContainer.querySelectorAll('input').forEach(function (el) {
            if (!el.value.trim()) ok = false;
        });
        return ok;
    }

    function screenshotReady() {
        return screenshotInput.files && screenshotInput.files.length > 0;
    }

    function recomputeSubmitState() {
        if (isSubmitting) return;
        var ready = fieldsValid() && screenshotReady() && maxTickets > 0;
        submitBtn.disabled = !ready;
        if (ready) {
            submitBtn.classList.remove('bg-gray-600', 'text-black/80');
            submitBtn.classList.add('bg-amber-400', 'text-black',
                'shadow-[0_10px_40px_rgba(250,204,21,0.35)]');
            submitHint.classList.add('hidden');
        } else {
            submitBtn.classList.add('bg-gray-600', 'text-black/80');
            submitBtn.classList.remove('bg-amber-400', 'text-black',
                'shadow-[0_10px_40px_rgba(250,204,21,0.35)]');
            submitHint.classList.remove('hidden');
        }
    }

    function wireInputs() {
        namesContainer.querySelectorAll('input').forEach(function (el) {
            el.addEventListener('input', recomputeSubmitState);
            el.addEventListener('blur',  recomputeSubmitState);
        });
    }

    screenshotInput.addEventListener('change', function () {
        screenshotError.classList.add('hidden');
        var file = screenshotInput.files[0];
        if (!file) {
            previewWrap.classList.add('hidden');
            emptyState.classList.remove('hidden');
            recomputeSubmitState();
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            screenshotError.innerText = 'حجم الصورة أكبر من 5MB — جرّب صورة أصغر.';
            screenshotError.classList.remove('hidden');
            screenshotInput.value = '';
            previewWrap.classList.add('hidden');
            emptyState.classList.remove('hidden');
            recomputeSubmitState();
            return;
        }
        var url = URL.createObjectURL(file);
        previewImg.src = url;
        previewImg.onload = function () { URL.revokeObjectURL(url); };
        fileNameEl.innerText = file.name;
        emptyState.classList.add('hidden');
        previewWrap.classList.remove('hidden');
        recomputeSubmitState();
    });

    // --- Idempotency token ----------------------------------------
    // We stash a random token per page load in sessionStorage,
    // scoped to this showtime. A refresh re-uses the same token,
    // which the server uses as a Cache::add() key to reject the
    // duplicate POST cleanly. Cleared on successful submit.
    var tokenKey = 'booking_token_show_{{ $showTime->id }}';
    var tokenEl  = document.getElementById('idempotencyToken');

    function makeToken() {
        try {
            var buf = new Uint8Array(16);
            (window.crypto || window.msCrypto).getRandomValues(buf);
            return Array.prototype.map.call(buf, function (b) {
                return ('0' + b.toString(16)).slice(-2);
            }).join('');
        } catch (e) {
            return Date.now() + '-' + Math.random().toString(36).slice(2);
        }
    }

    var token = null;
    try { token = sessionStorage.getItem(tokenKey); } catch (e) {}
    if (!token) {
        token = makeToken();
        try { sessionStorage.setItem(tokenKey, token); } catch (e) {}
    }
    tokenEl.value = token;

    // --- Submit handling ------------------------------------------
    bookingForm.addEventListener('submit', function (e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        if (submitBtn.disabled) {
            e.preventDefault();
            return false;
        }
        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.classList.add('is-loading');
        submitLabel.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span>جاري الإرسال…';
        submitHint.innerText = 'لا تغلق الصفحة حتى يكتمل الرفع';
        submitHint.classList.remove('hidden');
    });

    // Reset state if the user navigates back via bfcache (iOS Safari).
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            isSubmitting = false;
            submitBtn.classList.remove('is-loading');
            submitLabel.innerText = 'إرسال طلب الحجز';
            recomputeSubmitState();
        }
    });

    // --- Tap-to-copy on payment numbers ---------------------------
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var value = btn.getAttribute('data-copy') || '';
            var hint  = btn.querySelector('.copy-hint');
            var done = function () {
                if (!hint) return;
                var prev = hint.innerText;
                hint.innerText = 'تم النسخ ✓';
                hint.classList.add('text-emerald-300');
                setTimeout(function () {
                    hint.innerText = prev;
                    hint.classList.remove('text-emerald-300');
                }, 1200);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(done, done);
            } else {
                // iOS Safari fallback
                var ta = document.createElement('textarea');
                ta.value = value;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); } catch (err) {}
                document.body.removeChild(ta);
                done();
            }
        });
    });

    // --- Init ------------------------------------------------------
    if (maxTickets === 0) {
        count = 0;
        countDisplay.innerText = '0';
        ticketsInput.value = 0;
    } else {
        if (count > maxTickets) count = maxTickets;
        countDisplay.innerText = count;
        ticketsInput.value = count;
    }
    updatePriceHint();
    renderNames();
})();
</script>

@endsection
