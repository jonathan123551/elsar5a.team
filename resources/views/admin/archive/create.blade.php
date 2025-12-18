@extends('layouts.app')

@section('title', 'إضافة عرض سابق')

@section('content')
<section class="max-w-3xl mx-auto space-y-6">

    <h1 class="text-2xl font-bold">➕ إضافة عرض سابق</h1>

    <form action="{{ route('admin.archive.store') }}" method="POST" enctype="multipart/form-data"
          class="space-y-5 bg-black/40 border border-white/10 rounded-2xl p-6">
        @csrf

        {{-- اسم العرض --}}
        <div>
            <label class="text-sm text-gray-300">اسم العرض</label>
            <input type="text" name="title" required
                   class="w-full mt-1 rounded-xl bg-black/60 border border-white/10 px-4 py-2">
        </div>

        {{-- السنة --}}
        <div>
            <label class="text-sm text-gray-300">سنة العرض</label>
            <input type="number" name="year" min="1900" max="2100"
                   class="w-full mt-1 rounded-xl bg-black/60 border border-white/10 px-4 py-2">
        </div>

        {{-- الوصف --}}
        <div>
            <label class="text-sm text-gray-300">وصف العرض</label>
            <textarea name="description" rows="4"
                      class="w-full mt-1 rounded-xl bg-black/60 border border-white/10 px-4 py-2"></textarea>
        </div>

        {{-- لينك الفيديو --}}
        <div>
            <label class="text-sm text-gray-300">لينك فيديو (YouTube)</label>
            <input type="url" name="video_url"
                   class="w-full mt-1 rounded-xl bg-black/60 border border-white/10 px-4 py-2">
        </div>

        {{-- صور العرض --}}
        <div>
            <label class="text-sm text-gray-300">صور من العرض</label>
            <input type="file" name="images[]" multiple accept="image/*"
                   class="w-full mt-1 text-sm text-gray-300">
        </div>

        {{-- أزرار --}}
        <div class="flex gap-3 pt-4">
            <button type="submit"
                    class="px-5 py-2 rounded-full bg-emerald-500 text-black hover:bg-emerald-400">
                حفظ العرض
            </button>

            <a href="{{ route('admin.archive.index') }}"
               class="px-5 py-2 rounded-full bg-white/5 border border-white/10">
                إلغاء
            </a>
        </div>

    </form>
</section>
@endsection
