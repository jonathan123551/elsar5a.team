@extends('layouts.app')

@section('title', 'تعديل عرض سابق')

@section('content')
<section class="max-w-3xl mx-auto space-y-6">

    <h1 class="text-2xl font-bold">✏️ تعديل عرض سابق</h1>

    <form action="{{ route('admin.archive.update', $archive) }}"
          method="POST" enctype="multipart/form-data"
          class="space-y-5 bg-black/40 border border-white/10 rounded-2xl p-6">
        @csrf
        @method('PUT')

        {{-- اسم العرض --}}
        <div>
            <label class="text-sm text-gray-300">اسم العرض</label>
            <input type="text" name="title" value="{{ $archive->title }}" required
                   class="w-full mt-1 rounded-xl bg-black/60 border border-white/10 px-4 py-2">
        </div>

        {{-- السنة --}}
        <div>
            <label class="text-sm text-gray-300">سنة العرض</label>
            <input type="number" name="year" value="{{ $archive->year }}"
                   class="w-full mt-1 rounded-xl bg-black/60 border border-white/10 px-4 py-2">
        </div>

        {{-- الوصف --}}
        <div>
            <label class="text-sm text-gray-300">وصف العرض</label>
            <textarea name="description" rows="4"
                      class="w-full mt-1 rounded-xl bg-black/60 border border-white/10 px-4 py-2">{{ $archive->description }}</textarea>
        </div>

        {{-- لينك الفيديو --}}
        <div>
            <label class="text-sm text-gray-300">لينك الفيديو</label>
            <input type="url" name="video_url" value="{{ $archive->video_url }}"
                   class="w-full mt-1 rounded-xl bg-black/60 border border-white/10 px-4 py-2">
        </div>

        {{-- الصور الحالية --}}
        @if($archive->images)
            <div>
                <label class="text-sm text-gray-300 block mb-2">الصور الحالية</label>
                <div class="grid grid-cols-3 gap-3">
                    @foreach($archive->images as $img)
                        <img src="{{ asset('storage/'.$img) }}"
                             class="rounded-xl border border-white/10">
                    @endforeach
                </div>
            </div>
        @endif

        {{-- إضافة صور جديدة --}}
        <div>
            <label class="text-sm text-gray-300">إضافة صور جديدة</label>
            <input type="file" name="images[]" multiple
                   class="w-full mt-1 text-sm text-gray-300">
        </div>

        {{-- أزرار --}}
        <div class="flex gap-3 pt-4">
            <button type="submit"
                    class="px-5 py-2 rounded-full bg-amber-400 text-black hover:bg-amber-300">
                حفظ التعديلات
            </button>

            <a href="{{ route('admin.archive.index') }}"
               class="px-5 py-2 rounded-full bg-white/5 border border-white/10">
                رجوع
            </a>
        </div>

    </form>
</section>
@endsection
