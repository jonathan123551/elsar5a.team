@extends('layouts.app')

@section('title', 'تعديل العرض - ' . $show->title)

@section('content')

<section class="max-w-5xl space-y-6 mx-auto">

{{-- Header --}}

<div class="flex items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">تعديل العرض</h1>

```
<a href="{{ route('admin.shows.index') }}"
   class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
    ← رجوع
</a>
```

</div>

{{-- Errors --}}
@if ($errors->any()) <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-xs rounded-xl p-3"> <ul class="list-disc pr-4 space-y-1">
@foreach($errors->all() as $error) <li>{{ $error }}</li>
@endforeach </ul> </div>
@endif

<form action="{{ route('admin.shows.update', $show) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')

```
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
            <label class="text-xs mb-1">البوستر</label>

            @if($show->poster_path)
                @php
                    $posterUrl = str_starts_with($show->poster_path, 'http')
                        ? $show->poster_path
                        : $show->poster_path;
                @endphp

                <img id="posterPreview"
                     src="{{ $posterUrl }}"
                     class="w-full max-h-60 object-contain rounded-xl mb-2 border border-white/10 bg-black/40 p-2">
            @endif

            <input type="file" name="poster" id="posterInput" class="text-xs">
        </div>

    </div>

    {{-- RIGHT --}}
    <div class="space-y-4">

        <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-3 shadow-xl shadow-black/40">

            <h3 class="text-sm font-semibold">🎟️ تصميم التذكرة + QR</h3>

            <input type="file" name="ticket_template" id="ticketInput" class="text-xs">

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

<button type="submit"
        class="w-full sm:w-auto px-6 py-2 rounded-full bg-amber-400 text-black text-sm hover:bg-amber-300 transition">
    حفظ التعديلات
</button>
```

</form>

</section>

{{-- 🔥 SCRIPT (QR + LIVE PREVIEW) --}}

<script>
document.addEventListener('DOMContentLoaded', function () {

    const img      = document.getElementById('ticketTemplatePreview');
    const qrBox    = document.getElementById('qrBox');
    const handle   = document.getElementById('qrResizeHandle');

    const inputX   = document.getElementById('ticket_qr_x_input');
    const inputY   = document.getElementById('ticket_qr_y_input');
    const inputS   = document.getElementById('ticket_qr_size_input');

    if (img && qrBox && handle && inputX && inputY && inputS) {

        let scale = 1, isDragging = false, isResizing = false;
        let startX=0,startY=0,startLeft=0,startTop=0,startWidth=0;

        function recalc(){
            if (!img.naturalWidth) return;
            scale = img.clientWidth / img.naturalWidth;

            qrBox.style.left   = (inputX.value * scale)+'px';
            qrBox.style.top    = (inputY.value * scale)+'px';
            qrBox.style.width  = (inputS.value * scale)+'px';
            qrBox.style.height = (inputS.value * scale)+'px';
        }

        function updateInputs(){
            const imgRect = img.getBoundingClientRect();
            const boxRect = qrBox.getBoundingClientRect();

            inputX.value = Math.round((boxRect.left - imgRect.left)/scale);
            inputY.value = Math.round((boxRect.top  - imgRect.top )/scale);
            inputS.value = Math.round(boxRect.width/scale);
        }

        img.onload = recalc;
        window.addEventListener('resize', recalc);

        qrBox.onmousedown = e=>{
            if(e.target===handle) return;
            isDragging=true;
            const r=qrBox.getBoundingClientRect();
            startX=e.clientX;startY=e.clientY;
            startLeft=r.left;startTop=r.top;
        };

        handle.onmousedown = e=>{
            isResizing=true;
            startX=e.clientX;
            startWidth=qrBox.getBoundingClientRect().width;
            e.stopPropagation();
        };

        window.onmousemove = e=>{
            if(!isDragging && !isResizing) return;

            if(isDragging){
                const rect=img.getBoundingClientRect();
                let left=startLeft+(e.clientX-startX)-rect.left;
                let top =startTop +(e.clientY-startY)-rect.top;

                left=Math.max(0,Math.min(left,rect.width-qrBox.offsetWidth));
                top =Math.max(0,Math.min(top ,rect.height-qrBox.offsetHeight));

                qrBox.style.left=left+'px';
                qrBox.style.top =top +'px';
            }

            if(isResizing){
                let size=Math.max(40,startWidth+(e.clientX-startX));
                qrBox.style.width=size+'px';
                qrBox.style.height=size+'px';
            }

            updateInputs();
        };

        window.onmouseup=()=>{isDragging=false;isResizing=false;};

        recalc();
    }

    // 🔥 LIVE PREVIEW
    document.getElementById('posterInput')?.addEventListener('change', e=>{
        const f=e.target.files[0];
        if(!f) return;
        document.getElementById('posterPreview').src=URL.createObjectURL(f);
    });

    document.getElementById('ticketInput')?.addEventListener('change', e=>{
        const f=e.target.files[0];
        if(!f) return;

        const img=document.getElementById('ticketTemplatePreview');
        img.src=URL.createObjectURL(f);

        setTimeout(()=>img.dispatchEvent(new Event('load')),100);
    });

});
</script>

@endsection
