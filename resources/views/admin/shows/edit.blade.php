@extends('layouts.app')

@section('title', 'تعديل العرض - ' . $show->title)

@section('content')
<section class="space-y-5 max-w-4xl mx-auto">
<form action="{{ route('admin.shows.update', $show) }}" method="POST" enctype="multipart/form-data"
      class="space-y-5 single-submit-form">
    @csrf
    @method('PUT')

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold truncate">تعديل العرض</h1>

        <a href="{{ route('admin.shows.index') }}"
           class="text-[12px] px-3 py-2 rounded-full bg-white/5 border border-white/10
                  hover:bg-white/10 active:bg-white/15 transition shrink-0">
            ← رجوع
        </a>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-[13px] rounded-xl p-3">
            <ul class="list-disc pe-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

<div class="grid lg:grid-cols-2 gap-6">

    {{-- LEFT --}}
    <div class="space-y-4">

        <div>
            <label class="text-xs mb-1">اسم العرض</label>
            <input type="text" name="title"
                   value="{{ old('title', $show->title) }}"
                   class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:border-amber-400">
        </div>

        <div>
            <label class="text-xs mb-1">الوصف</label>
            <textarea name="description" rows="4"
                      class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:border-amber-400">{{ old('description', $show->description) }}</textarea>
        </div>

        {{-- Poster --}}
        <div>
            <label class="block text-xs mb-1.5 text-gray-300">البوستر</label>

            @php
                $posterUrl = $show->poster_path
                    ? (str_starts_with($show->poster_path, 'http')
                        ? $show->poster_path
                        : $show->poster_path)
                    : null;
            @endphp

            <label for="posterInput"
                   data-upload-dropzone
                   class="block cursor-pointer rounded-2xl border-2 border-dashed
                          border-white/15 hover:border-amber-400/60 active:border-amber-400/80
                          transition bg-black/40 p-4 text-center">

                <div data-upload-empty class="{{ $posterUrl ? 'hidden' : '' }}">
                    <p class="text-[28px] leading-none mb-1">🎭</p>
                    <p class="text-sm text-white font-semibold">اختر بوستر للعرض</p>
                    <p class="text-[11px] text-gray-400 mt-1">PNG / JPG حتى 12MB</p>
                </div>

                <div data-upload-preview class="{{ $posterUrl ? '' : 'hidden' }}">
                    <img id="posterPreview"
                         data-upload-preview-img
                         src="{{ $posterUrl }}"
                         alt=""
                         class="mx-auto max-h-56 rounded-xl border border-white/10 object-contain">
                    <p data-upload-filename class="text-[11px] text-gray-300 mt-2 truncate"></p>
                    <p class="text-[11px] text-amber-300 mt-1">اضغط لاستبدال الصورة</p>
                </div>

            </label>

            <input type="file"
                   name="poster"
                   id="posterInput"
                   accept="image/*"
                   data-max-mb="12"
                   class="hidden">

            <p data-upload-error class="hidden text-[12px] text-red-300 mt-1.5"></p>
        </div>

    </div>

    {{-- RIGHT --}}
    <div class="space-y-4">

        <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-3 shadow-xl shadow-black/40">

            <h3 class="text-sm font-semibold">🎟️ تصميم التذكرة + QR</h3>

            <label for="ticketInput"
                   data-upload-dropzone
                   class="block cursor-pointer rounded-2xl border-2 border-dashed
                          border-white/15 hover:border-amber-400/60 active:border-amber-400/80
                          transition bg-black/40 p-3 text-center">
                <div data-upload-empty class="{{ $show->ticket_template_path ? 'hidden' : '' }}">
                    <p class="text-[24px] leading-none mb-1">🎟️</p>
                    <p class="text-xs text-white font-semibold">اختر تصميم التذكرة</p>
                    <p class="text-[11px] text-gray-400 mt-1">PNG / JPG حتى 12MB</p>
                </div>
                <div data-upload-preview class="{{ $show->ticket_template_path ? '' : 'hidden' }}">
                    <p data-upload-filename class="text-[11px] text-gray-300 truncate"></p>
                    <p class="text-[11px] text-amber-300 mt-1">اضغط لاستبدال الصورة</p>
                </div>
            </label>

            <input type="file"
                   name="ticket_template"
                   id="ticketInput"
                   accept="image/*"
                   data-max-mb="12"
                   class="hidden">

            <p data-upload-error class="hidden text-[12px] text-red-300"></p>

            @if($show->ticket_template_path)

                @php
                    $ticketUrl = str_starts_with($show->ticket_template_path, 'http')
                        ? $show->ticket_template_path
                        : asset('storage/'.$show->ticket_template_path);
                @endphp

                <div class="relative border border-white/10 rounded-xl overflow-hidden bg-black/40">

                    <img id="ticketTemplatePreview"
                         src="{{ $ticketUrl }}"
                         class="w-full h-auto block select-none pointer-events-none">

                    <div id="qrBox"
                         class="absolute border-2 border-emerald-400 bg-emerald-400/10 cursor-move shadow-lg shadow-emerald-500/20"
                         style="width: 120px; height: 120px; left: 10px; top: 10px;">

                        <div id="qrResizeHandle"
                             class="absolute w-3 h-3 bg-emerald-400 bottom-0 right-0 cursor-nwse-resize"></div>

                    </div>

                </div>

                <div class="grid grid-cols-3 gap-2 text-xs">
                    <input type="number" name="ticket_qr_x" id="ticket_qr_x_input"
                           value="{{ old('ticket_qr_x', $show->ticket_qr_x ?? 0) }}"
                           class="rounded-lg bg-black/60 border border-white/15 px-2 py-1">

                    <input type="number" name="ticket_qr_y" id="ticket_qr_y_input"
                           value="{{ old('ticket_qr_y', $show->ticket_qr_y ?? 0) }}"
                           class="rounded-lg bg-black/60 border border-white/15 px-2 py-1">

                    <input type="number" name="ticket_qr_size" id="ticket_qr_size_input"
                           value="{{ old('ticket_qr_size', $show->ticket_qr_size ?? 220) }}"
                           class="rounded-lg bg-black/60 border border-white/15 px-2 py-1">
                </div>

            @endif

        </div>

    </div>

</div>

    {{-- Submit (sticky-action on mobile) --}}
    <div data-sticky-action class="pt-2">
        <button type="submit"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3
                       rounded-2xl bg-amber-400 text-black text-sm font-bold
                       hover:bg-amber-300 active:bg-amber-500 transition
                       disabled:opacity-60 disabled:cursor-progress
                       shadow-[0_8px_24px_rgba(251,191,36,0.25)]">
            <span class="btn-label">حفظ التعديلات</span>
            <span class="btn-spinner hidden" aria-hidden="true"></span>
        </button>
    </div>

</form>
</section>

<script>
document.querySelectorAll('.single-submit-form').forEach(function (f) {
    f.addEventListener('submit', function () {
        requestAnimationFrame(function () {
            f.querySelectorAll('button[type=submit]').forEach(function (b) {
                if (b.disabled) return;
                b.disabled = true;
                b.classList.add('is-loading');
                var spin = b.querySelector('.btn-spinner');
                if (spin) spin.classList.remove('hidden');
            });
        });
    });
});

// Wire up every [data-upload-dropzone] in the page to its sibling
// <input type=file>. Provides a mobile-friendly tap target, inline
// preview, filename, and client-side size guard.
document.querySelectorAll('[data-upload-dropzone]').forEach(function (zone) {
    var input = document.getElementById(zone.getAttribute('for'));
    if (!input) return;

    var empty   = zone.querySelector('[data-upload-empty]');
    var preview = zone.querySelector('[data-upload-preview]');
    var img     = zone.querySelector('[data-upload-preview-img]');
    var name    = zone.querySelector('[data-upload-filename]');
    var err     = zone.parentElement.querySelector('[data-upload-error]');
    var maxMb   = parseFloat(input.getAttribute('data-max-mb') || '12');

    input.addEventListener('change', function () {
        if (err) err.classList.add('hidden');
        var file = input.files && input.files[0];
        if (!file) {
            if (preview) preview.classList.add('hidden');
            if (empty)   empty.classList.remove('hidden');
            return;
        }
        if (file.size > maxMb * 1024 * 1024) {
            if (err) {
                err.innerText = 'حجم الصورة أكبر من ' + maxMb + 'MB — جرّب صورة أصغر.';
                err.classList.remove('hidden');
            }
            input.value = '';
            if (preview) preview.classList.add('hidden');
            if (empty)   empty.classList.remove('hidden');
            return;
        }
        if (img) {
            var url = URL.createObjectURL(file);
            img.src = url;
            img.onload = function () { URL.revokeObjectURL(url); };
        }
        if (name) name.innerText = file.name;
        if (empty)   empty.classList.add('hidden');
        if (preview) preview.classList.remove('hidden');
    });
});
</script>


{{-- 🔥 نفس السكربت بدون أي تغيير --}}

 <script>
        document.addEventListener('DOMContentLoaded', function () {
            const img      = document.getElementById('ticketTemplatePreview');
            const qrBox    = document.getElementById('qrBox');
            const handle   = document.getElementById('qrResizeHandle');

            const inputX   = document.getElementById('ticket_qr_x_input');
            const inputY   = document.getElementById('ticket_qr_y_input');
            const inputS   = document.getElementById('ticket_qr_size_input');

            if (!img || !qrBox || !handle || !inputX || !inputY || !inputS) {
                return;
            }

            let scale = 1;
            let isDragging = false;
            let isResizing = false;
            let startX = 0, startY = 0;
            let startLeft = 0, startTop = 0;
            let startWidth = 0, startHeight = 0;

            function recalcScaleAndPositionFromInputs() {
                if (!img.naturalWidth) return;

                scale = img.clientWidth / img.naturalWidth;

                const xVal = parseInt(inputX.value || '0', 10);
                const yVal = parseInt(inputY.value || '0', 10);
                const sVal = parseInt(inputS.value || '220', 10);

                qrBox.style.left   = (xVal * scale) + 'px';
                qrBox.style.top    = (yVal * scale) + 'px';
                qrBox.style.width  = (sVal * scale) + 'px';
                qrBox.style.height = (sVal * scale) + 'px';
            }

            function updateInputsFromBox() {
                const imgRect = img.getBoundingClientRect();
                const boxRect = qrBox.getBoundingClientRect();

                const left = boxRect.left - imgRect.left;
                const top  = boxRect.top  - imgRect.top;
                const size = boxRect.width;

                inputX.value = Math.max(0, Math.round(left / scale));
                inputY.value = Math.max(0, Math.round(top  / scale));
                inputS.value = Math.max(10, Math.round(size / scale));
            }

            img.addEventListener('load', function () {
                recalcScaleAndPositionFromInputs();
            });

            if (img.complete) {
                recalcScaleAndPositionFromInputs();
            }

            [inputX, inputY, inputS].forEach(function (el) {
                el.addEventListener('input', function () {
                    recalcScaleAndPositionFromInputs();
                });
            });

            qrBox.addEventListener('mousedown', function (e) {
                if (e.target === handle) return;

                isDragging = true;
                const rect = qrBox.getBoundingClientRect();

                startX = e.clientX;
                startY = e.clientY;
                startLeft = rect.left;
                startTop  = rect.top;

                e.preventDefault();
            });

            handle.addEventListener('mousedown', function (e) {
                isResizing = true;
                const rect = qrBox.getBoundingClientRect();

                startX = e.clientX;
                startY = e.clientY;
                startWidth  = rect.width;
                startHeight = rect.height;

                e.stopPropagation();
                e.preventDefault();
            });

            window.addEventListener('mousemove', function (e) {
                if (!isDragging && !isResizing) return;

                const dx = e.clientX - startX;
                const dy = e.clientY - startY;

                if (isDragging) {
                    const imgRect = img.getBoundingClientRect();

                    let newLeft = startLeft + dx - imgRect.left;
                    let newTop  = startTop  + dy - imgRect.top;

                    const maxLeft = imgRect.width  - qrBox.offsetWidth;
                    const maxTop  = imgRect.height - qrBox.offsetHeight;

                    newLeft = Math.min(Math.max(0, newLeft), maxLeft);
                    newTop  = Math.min(Math.max(0, newTop ), maxTop);

                    qrBox.style.left = newLeft + 'px';
                    qrBox.style.top  = newTop  + 'px';
                } else if (isResizing) {
                    let newSize = Math.max(40, startWidth + dx);

                    const imgRect = img.getBoundingClientRect();
                    const boxRect = qrBox.getBoundingClientRect();

                    const maxSize = Math.min(
                        imgRect.width  - (boxRect.left - imgRect.left),
                        imgRect.height - (boxRect.top  - imgRect.top)
                    );

                    newSize = Math.min(newSize, maxSize);

                    qrBox.style.width  = newSize + 'px';
                    qrBox.style.height = newSize + 'px';
                }

                updateInputsFromBox();
            });

            window.addEventListener('mouseup', function () {
                isDragging = false;
                isResizing = false;
            });

            qrBox.addEventListener('touchstart', function (e) {
                const touch = e.touches[0];
                if (!touch) return;

                if (e.target === handle) {
                    isResizing = true;
                    const rect = qrBox.getBoundingClientRect();
                    startX = touch.clientX;
                    startY = touch.clientY;
                    startWidth  = rect.width;
                    startHeight = rect.height;
                } else {
                    isDragging = true;
                    const rect = qrBox.getBoundingClientRect();
                    startX = touch.clientX;
                    startY = touch.clientY;
                    startLeft = rect.left;
                    startTop  = rect.top;
                }

                e.preventDefault();
            }, { passive: false });

            window.addEventListener('touchmove', function (e) {
                const touch = e.touches[0];
                if (!touch || (!isDragging && !isResizing)) return;

                const dx = touch.clientX - startX;
                const dy = touch.clientY - startY;

                if (isDragging) {
                    const imgRect = img.getBoundingClientRect();

                    let newLeft = startLeft + dx - imgRect.left;
                    let newTop  = startTop  + dy - imgRect.top;

                    const maxLeft = imgRect.width  - qrBox.offsetWidth;
                    const maxTop  = imgRect.height - qrBox.offsetHeight;

                    newLeft = Math.min(Math.max(0, newLeft), maxLeft);
                    newTop  = Math.min(Math.max(0, newTop ), maxTop);

                    qrBox.style.left = newLeft + 'px';
                    qrBox.style.top  = newTop  + 'px';
                } else if (isResizing) {
                    let newSize = Math.max(40, startWidth + dx);

                    const imgRect = img.getBoundingClientRect();
                    const boxRect = qrBox.getBoundingClientRect();

                    const maxSize = Math.min(
                        imgRect.width  - (boxRect.left - imgRect.left),
                        imgRect.height - (boxRect.top  - imgRect.top)
                    );

                    newSize = Math.min(newSize, maxSize);

                    qrBox.style.width  = newSize + 'px';
                    qrBox.style.height = newSize + 'px';
                }

                updateInputsFromBox();
                e.preventDefault();
            }, { passive: false });

            window.addEventListener('touchend', function () {
                isDragging = false;
                isResizing = false;
            });

            window.addEventListener('resize', function () {
                recalcScaleAndPositionFromInputs();
            });
        });
        </script>
        <script>
/* 🔥 LIVE PREVIEW */

// Poster Preview
document.getElementById('posterInput')?.addEventListener('change', function(e){
    const file = e.target.files[0];
    if(!file) return;

    const url = URL.createObjectURL(file);
    const img = document.getElementById('posterPreview');

    if(img){
        img.src = url;
    }
});

// Ticket Preview
document.getElementById('ticketInput')?.addEventListener('change', function(e){
    const file = e.target.files[0];
    if(!file) return;

    const url = URL.createObjectURL(file);
    const img = document.getElementById('ticketTemplatePreview');
    const qrBox = document.getElementById('qrBox');

    if(img && qrBox){
        img.src = url;

        // 🔥 رجّع QR لنقطة البداية
        qrBox.style.left = "10px";
        qrBox.style.top = "10px";
        qrBox.style.width = "120px";
        qrBox.style.height = "120px";

        // 🔥 حدّث inputs كمان
        document.getElementById('ticket_qr_x_input').value = 0;
        document.getElementById('ticket_qr_y_input').value = 0;
        document.getElementById('ticket_qr_size_input').value = 220;

        // 🔥 أهم حاجة: recalc بعد تحميل الصورة
        img.onload = function(){
            img.dispatchEvent(new Event('load'));
        };
    }
});

    </script>

@endsection
