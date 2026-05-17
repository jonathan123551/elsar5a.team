{{-- resources/views/admin/shows/create.blade.php --}}
@extends('layouts.app')

@section('title', 'إضافة عرض جديد')

@section('content')
    <section class="max-w-xl space-y-5 mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 mb-2">
            <h1 class="text-xl sm:text-2xl font-bold">إضافة عرض جديد</h1>

            <a href="{{ route('admin.shows.index') }}"
               class="text-[12px] px-3 py-2 rounded-full bg-white/5 border border-white/10
                      hover:bg-white/10 active:bg-white/15 transition">
                ← رجوع
            </a>
        </div>

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-xs rounded-xl p-3 mb-2">
                <ul class="list-disc pr-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.shows.store') }}" method="POST" enctype="multipart/form-data"
              class="space-y-4 single-submit-form">
            @csrf

            {{-- اسم العرض --}}
            <div>
                <label class="block text-xs mb-1.5 text-gray-300">اسم العرض</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       autocomplete="off"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2.5 text-sm
                              focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400/40 transition">
            </div>

            {{-- وصف العرض --}}
            <div>
                <label class="block text-xs mb-1.5 text-gray-300">وصف العرض</label>
                <textarea name="description" rows="4"
                          class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2.5 text-sm
                                 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400/40 transition resize-y">{{ old('description') }}</textarea>
            </div>

            {{-- بوستر العرض --}}
            <div>
                <label class="block text-xs mb-1.5 text-gray-300">بوستر العرض (اختياري)</label>
                <input type="file" name="poster" accept="image/*"
                       class="w-full text-xs text-gray-300 file:me-3 file:px-3 file:py-2 file:rounded-full
                              file:border-0 file:bg-amber-400/10 file:text-amber-200 file:cursor-pointer
                              hover:file:bg-amber-400/20 transition">
            </div>

            {{-- تصميم التذكرة + إعداد موضع الـ QR --}}
            <div class="mt-4 space-y-2">
                <h3 class="text-sm font-semibold">تصميم التذكرة وموضع الـ QR</h3>

                <p class="text-xs text-gray-400">
                    ارفع تصميم التذكرة (PNG / JPG)، وبعدها حدد مكان مربع الـ QR بالسحب على الصورة أو بالأرقام.
                    لو ما رفعتش تصميم، النظام هيطلع QR لوحده بدون خلفية.
                </p>

                <div class="grid md:grid-cols-2 gap-4 items-start">

                    {{-- ملف تصميم التذكرة + المعاينة --}}
                    <div class="space-y-2">
                        <label class="block text-xs mb-1">ملف تصميم التذكرة</label>

                        <input type="file"
                               name="ticket_template"
                               id="ticket_template_input"
                               accept="image/*"
                               class="w-full text-xs text-gray-300">

                        <p class="text-[11px] text-gray-400 mt-1">
                            بعد ما تختار الملف، هتقدر تحرك مربع الـ QR وتغيّر حجمه على التصميم.
                        </p>

                        {{-- محرر موضع الـ QR (المعاينة) --}}
                        <div id="ticket-editor-wrapper"
                             class="mt-2 border border-white/10 rounded-lg overflow-hidden bg-black/40 hidden">
                            <div id="ticket-editor"
                                 class="relative mx-auto max-w-md">
                                <img id="ticketTemplatePreview"
                                     src=""
                                     alt="تصميم التذكرة"
                                     class="w-full h-auto block select-none pointer-events-none">

                                {{-- مربع الـ QR المتحرك --}}
                                <div id="qrBox"
                                     class="absolute border-2 border-emerald-400 bg-emerald-400/10 cursor-move"
                                     style="width: 120px; height: 120px; left: 10px; top: 10px;">
                                    <div id="qrResizeHandle"
                                         class="absolute w-3 h-3 bg-emerald-400 bottom-0 right-0 cursor-nwse-resize"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- إعدادات مكان الـ QR (أرقام) --}}
                    <div class="space-y-2 text-xs">
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block mb-1">X (من الشمال)</label>
                                <input type="number" min="0" name="ticket_qr_x"
                                       id="ticket_qr_x_input"
                                       value="{{ old('ticket_qr_x', 0) }}"
                                       class="w-full rounded-lg bg-black/60 border border-white/15 px-2 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="block mb-1">Y (من فوق)</label>
                                <input type="number" min="0" name="ticket_qr_y"
                                       id="ticket_qr_y_input"
                                       value="{{ old('ticket_qr_y', 0) }}"
                                       class="w-full rounded-lg bg-black/60 border border-white/15 px-2 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="block mb-1">حجم الـ QR</label>
                                <input type="number" min="50" name="ticket_qr_size"
                                       id="ticket_qr_size_input"
                                       value="{{ old('ticket_qr_size', 220) }}"
                                       class="w-full rounded-lg bg-black/60 border border-white/15 px-2 py-1.5 text-xs">
                            </div>
                        </div>

                        <p class="text-[11px] text-gray-500 mt-1 leading-relaxed">
                            حرّك مربع الـ QR على الصورة بالفأرة أو اللمس، واسحب المربع الصغير في الركن لتكبير/تصغير الحجم.
                            الأرقام دي بتتحوّل أوتوماتيك حسب مكانك على التصميم الأصلي (بالبكسل).
                        </p>
                    </div>
                </div>
            </div>

            {{-- حالة العرض --}}
            <label for="is_active"
                   class="flex items-center gap-2.5 text-sm bg-black/30 border border-white/10
                          rounded-xl px-3 py-2.5 cursor-pointer hover:bg-black/40 transition">
                <input type="checkbox"
                       name="is_active"
                       id="is_active"
                       value="1"
                       class="w-4 h-4 accent-amber-400"
                       {{ old('is_active', 1) ? 'checked' : '' }}>
                <span>عرض هذا العرض على الموقع</span>
            </label>

            {{-- Submit (sticky-action on mobile) --}}
            <div data-sticky-action class="pt-2">
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-2xl
                               bg-amber-400 text-black text-sm font-bold hover:bg-amber-300 active:bg-amber-500
                               transition disabled:opacity-60 disabled:cursor-progress
                               shadow-[0_8px_24px_rgba(251,191,36,0.25)]">
                    <span class="btn-label">إضافة العرض</span>
                    <span class="btn-spinner hidden" aria-hidden="true"></span>
                </button>
            </div>
        </form>

        <script>
        // Disable + show spinner on submit so the form can't be
        // accidentally posted twice (refresh / double-tap / Enter
        // hammering).
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
        </script>
    </section>

    {{-- سكربت محرر الـ QR --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const templateInput = document.getElementById('ticket_template_input');
            const wrapper  = document.getElementById('ticket-editor-wrapper');
            const img      = document.getElementById('ticketTemplatePreview');
            const qrBox    = document.getElementById('qrBox');
            const handle   = document.getElementById('qrResizeHandle');

            const inputX   = document.getElementById('ticket_qr_x_input');
            const inputY   = document.getElementById('ticket_qr_y_input');
            const inputS   = document.getElementById('ticket_qr_size_input');

            if (!templateInput || !wrapper || !img || !qrBox || !handle || !inputX || !inputY || !inputS) {
                return;
            }

            let scale = 1;
            let isDragging = false;
            let isResizing = false;
            let startX = 0, startY = 0;
            let startLeft = 0, startTop = 0;
            let startWidth = 0;

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

            // لما يختار ملف تصميم
            templateInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) {
                    wrapper.classList.add('hidden');
                    img.src = '';
                    return;
                }

                const url = URL.createObjectURL(file);
                img.src = url;
                wrapper.classList.remove('hidden');

                img.onload = function () {
                    recalcScaleAndPositionFromInputs();
                };
            });

            [inputX, inputY, inputS].forEach(function (el) {
                el.addEventListener('input', recalcScaleAndPositionFromInputs);
            });

            // Drag
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

            // Resize
            handle.addEventListener('mousedown', function (e) {
                isResizing = true;
                const rect = qrBox.getBoundingClientRect();
                startX = e.clientX;
                startY = e.clientY;
                startWidth  = rect.width;
                e.stopPropagation();
                e.preventDefault();
            });

            window.addEventListener('mousemove', function (e) {
                if (!isDragging && !isResizing) return;
                const dx = e.clientX - startX;

                if (isDragging) {
                    const imgRect = img.getBoundingClientRect();
                    let newLeft = startLeft + dx - imgRect.left;
                    let newTop  = startTop  + (e.clientY - startY) - imgRect.top;

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

            // دعم اللمس (موبايل)
            qrBox.addEventListener('touchstart', function (e) {
                const touch = e.touches[0];
                if (!touch) return;

                if (e.target === handle) {
                    isResizing = true;
                    const rect = qrBox.getBoundingClientRect();
                    startX = touch.clientX;
                    startWidth  = rect.width;
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
@endsection
