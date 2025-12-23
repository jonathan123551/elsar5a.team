{{-- resources/views/admin/shows/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'تعديل العرض - ' . $show->title)

@section('content')
    <section class="max-w-xl space-y-4 mx-auto">

        {{-- العنوان + زر الرجوع --}}
        <div class="flex items-center justify-between gap-3 mb-2">
            <h1 class="text-2xl font-bold">تعديل العرض</h1>

            <a href="{{ route('admin.shows.index') }}"
               class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
                ← رجوع لقائمة العروض
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

        <form action="{{ route('admin.shows.update', $show) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- اسم العرض --}}
            <div>
                <label class="block text-xs mb-1">اسم العرض</label>
                <input type="text" name="title" value="{{ old('title', $show->title) }}"
                       class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
            </div>

            {{-- وصف العرض --}}
            <div>
                <label class="block text-xs mb-1">وصف العرض</label>
                <textarea name="description" rows="4"
                          class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:outline-none focus:border-amber-400">{{ old('description', $show->description) }}</textarea>
            </div>

            {{-- بوستر العرض --}}
            <div>
                <label class="block text-xs mb-1">بوستر العرض (اختياري)</label>
                @if($show->poster_path)
    @php
        $posterUrl = str_starts_with($show->poster_path, 'http')
            ? $show->poster_path
            :  $show->poster_path;
    @endphp

    <div class="mb-2">
        <img src="{{ $posterUrl }}"
             class="w-24 h-24 object-cover rounded"
             alt="بوستر العرض">
    </div>
@endif

                <input type="file" name="poster" accept="image/*"
                       class="w-full text-xs text-gray-300">
            </div>

            {{-- تصميم التذكرة + إعداد مكان الـ QR --}}
            <div class="mt-4 space-y-2">
                <h3 class="text-sm font-semibold">تصميم التذكرة وموضع الـ QR</h3>

                <p class="text-xs text-gray-400">
                    ارفع تصميم التذكرة (PNG / JPG)، وبعدها حدد مكان مربع الـ QR بالسحب على الصورة أو بالأرقام.
                </p>

                <div class="grid md:grid-cols-2 gap-4 items-start">

                    {{-- ملف تصميم التذكرة --}}
                    <div class="space-y-2">
                        <label class="block text-xs mb-1">ملف تصميم التذكرة</label>
                        <input type="file" name="ticket_template" accept="image/*"
                               class="w-full text-xs text-gray-300">

                        @if($show->ticket_template_path)
                            <p class="text-[11px] text-gray-400 mt-1">
                                تم رفع تصميم سابقًا:
                                <a href="{{ asset('storage/'.$show->ticket_template_path) }}"
                                   target="_blank"
                                   class="underline text-amber-300">
                                    فتح تصميم التذكرة في تبويب جديد
                                </a>
                            </p>

                            {{-- محرر موضع الـ QR --}}
                            <div class="mt-2 border border-white/10 rounded-lg overflow-hidden bg-black/40">
                                <div id="ticket-editor"
                                     class="relative mx-auto max-w-md">
                                    <img id="ticketTemplatePreview"
                                         src="{{ asset('storage/'.$show->ticket_template_path) }}"
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
                        @endif
                    </div>

                    {{-- إعدادات مكان الـ QR (أرقام) --}}
                    <div class="space-y-2 text-xs">
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block mb-1">X (من الشمال)</label>
                                <input type="number" min="0" name="ticket_qr_x"
                                       id="ticket_qr_x_input"
                                       value="{{ old('ticket_qr_x', $show->ticket_qr_x ?? 0) }}"
                                       class="w-full rounded-lg bg-black/60 border border-white/15 px-2 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="block mb-1">Y (من فوق)</label>
                                <input type="number" min="0" name="ticket_qr_y"
                                       id="ticket_qr_y_input"
                                       value="{{ old('ticket_qr_y', $show->ticket_qr_y ?? 0) }}"
                                       class="w-full rounded-lg bg-black/60 border border-white/15 px-2 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="block mb-1">حجم الـ QR</label>
                                <input type="number" min="50" name="ticket_qr_size"
                                       id="ticket_qr_size_input"
                                       value="{{ old('ticket_qr_size', $show->ticket_qr_size ?? 220) }}"
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
            <div class="flex items-center gap-2 text-xs">
                <input type="checkbox"
                       name="is_active"
                       id="is_active"
                       value="1"
                       class="scale-90"
                       {{ $show->is_active ? 'checked' : '' }}>
                <label for="is_active">عرض هذا العرض على الموقع</label>
            </div>

            {{-- زر الحفظ --}}
            <button type="submit"
                    class="mt-2 inline-flex items-center justify-center px-4 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition">
                حفظ التعديلات
            </button>
        </form>

        {{-- مواعيد العرض وعدد التذاكر --}}
        @if($show->showTimes->count())
            <hr class="border-white/10 my-6">

            <div class="space-y-3">
                <h2 class="text-sm font-semibold">مواعيد العرض وعدد التذاكر 🎟️</h2>
                <p class="text-[11px] text-gray-400">
                    عدّل عدد التذاكر لكل ميعاد. المتبقي = إجمالي التذاكر - (pending + approved)
                </p>

                <div class="overflow-x-auto border border-white/10 rounded-xl bg-black/40">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/5 text-[11px] text-gray-300">
                        <tr>
                            <th class="px-3 py-2 text-right">اليوم</th>
                            <th class="px-3 py-2 text-right">الساعة</th>
                            <th class="px-3 py-2 text-right">إجمالي التذاكر</th>
                            <th class="px-3 py-2 text-right">المحجوز (pending + approved)</th>
                            <th class="px-3 py-2 text-right">المتبقي</th>
                            <th class="px-3 py-2 text-right">الحالة</th>
                        </tr>
                        </thead>

                        <tbody class="divide-y divide-white/5">
                        @foreach($show->showTimes as $time)
                            @php
                                $reserved = $time->bookings()
                                    ->whereIn('status', ['pending', 'approved'])
                                    ->sum('tickets_count');

                                $remaining = max(0, $time->total_tickets - $reserved);
                            @endphp

                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ $time->date->format('Y-m-d') }}
                                </td>

                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ $time->time }}
                                </td>

                                <td class="px-3 py-2">
                                    <form action="{{ route('admin.show-times.update-tickets', $time) }}"
                                          method="POST" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')

                                        <input type="number"
                                               name="total_tickets"
                                               min="{{ $reserved }}"
                                               value="{{ old('total_tickets', $time->total_tickets) }}"
                                               class="w-20 rounded-lg bg-black/60 border border-white/20 px-2 py-1 text-xs">

                                        <button type="submit"
                                                class="text-[11px] px-3 py-1 rounded-full bg-amber-400 text-black hover:bg-amber-300">
                                            حفظ
                                        </button>
                                    </form>
                                </td>

                                <td class="px-3 py-2 text-amber-200">
                                    {{ $reserved }}
                                </td>

                                <td class="px-3 py-2 text-emerald-300">
                                    {{ $remaining }}
                                </td>

                                <td class="px-3 py-2 text-[11px]">
                                    @if($time->is_sold_out)
                                        <span class="px-2 py-1 rounded-full bg-red-500/20 text-red-200">
                                            مكتمل
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-300">
                                            متاح
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        @endif

    </section>

    {{-- سكربت محرر الـ QR --}}
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
@endsection
