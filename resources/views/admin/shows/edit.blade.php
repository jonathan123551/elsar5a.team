@extends('layouts.app')

@section('title', 'تعديل العرض - ' . $show->title)

@section('content')

<section class="max-w-5xl mx-auto space-y-6">

```
{{-- Header --}}
<div class="flex items-center justify-between">
    <h1 class="text-xl sm:text-2xl font-bold">تعديل العرض</h1>

    <a href="{{ route('admin.shows.index') }}"
       class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
        ← رجوع
    </a>
</div>

{{-- Errors --}}
@if ($errors->any())
    <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-xs rounded-xl p-3">
        @foreach($errors->all() as $error)
            <div>• {{ $error }}</div>
        @endforeach
    </div>
@endif

<form action="{{ route('admin.shows.update', $show) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="grid lg:grid-cols-2 gap-6">

        {{-- LEFT --}}
        <div class="space-y-4">

            <div>
                <label class="text-xs mb-1 block">اسم العرض</label>
                <input type="text" name="title" value="{{ old('title', $show->title) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:border-amber-400">
            </div>

            <div>
                <label class="text-xs mb-1 block">وصف العرض</label>
                <textarea name="description" rows="4"
                          class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:border-amber-400">{{ old('description', $show->description) }}</textarea>
            </div>

            <div>
                <label class="text-xs mb-1 block">البوستر</label>

                @if($show->poster_path)
                    <img src="{{ $show->poster_path }}"
                         class="w-full h-40 object-cover rounded-xl mb-2 border border-white/10">
                @endif

                <input type="file" name="poster" class="text-xs">
            </div>

            {{-- Switch --}}
            <div class="flex items-center justify-between bg-white/5 border border-white/10 rounded-xl p-3">
                <span class="text-xs">حالة العرض</span>

                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           class="sr-only peer"
                           {{ $show->is_active ? 'checked' : '' }}>

                    <div class="w-11 h-6 bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition"></div>

                    <div class="absolute top-0.5 right-5 w-5 h-5 bg-white rounded-full transition-all peer-checked:right-0.5"></div>
                </label>
            </div>

        </div>

        {{-- RIGHT --}}
        <div class="space-y-4">

            <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-3">

                <h3 class="text-sm font-semibold">🎟️ تصميم التذكرة + QR</h3>

                <input type="file" name="ticket_template" class="text-xs">

                @if($show->ticket_template_path)

                    <div class="relative w-full max-w-md mx-auto border border-white/10 rounded-xl overflow-hidden bg-black/40">

                        <img id="ticketTemplatePreview"
                             src="{{ asset('storage/'.$show->ticket_template_path) }}"
                             class="w-full h-auto block select-none pointer-events-none">

                        {{-- QR --}}
                        <div id="qrBox"
                             class="absolute border-2 border-emerald-400 bg-emerald-400/10 cursor-move shadow-lg shadow-emerald-500/20"
                             style="width: 120px; height: 120px; left: 10px; top: 10px;">

                            <div id="qrResizeHandle"
                                 class="absolute w-3 h-3 bg-emerald-400 bottom-0 right-0 cursor-nwse-resize"></div>

                        </div>
                    </div>

                    {{-- Inputs --}}
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

    <button type="submit"
            class="w-full sm:w-auto px-6 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition">
        حفظ التعديلات
    </button>

</form>
```

</section>

{{-- ✅ السكربت الأصلي الكامل (بدون تكرار) --}}

<script>
document.addEventListener('DOMContentLoaded', function () {
    const img      = document.getElementById('ticketTemplatePreview');
    const qrBox    = document.getElementById('qrBox');
    const handle   = document.getElementById('qrResizeHandle');

    const inputX   = document.getElementById('ticket_qr_x_input');
    const inputY   = document.getElementById('ticket_qr_y_input');
    const inputS   = document.getElementById('ticket_qr_size_input');

    if (!img || !qrBox || !handle || !inputX || !inputY || !inputS) return;

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

    img.addEventListener('load', recalcScaleAndPositionFromInputs);
    if (img.complete) recalcScaleAndPositionFromInputs();

    [inputX, inputY, inputS].forEach(el => {
        el.addEventListener('input', recalcScaleAndPositionFromInputs);
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

    window.addEventListener('resize', recalcScaleAndPositionFromInputs);
});
</script>

@endsection
