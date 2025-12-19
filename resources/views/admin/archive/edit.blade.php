@extends('layouts.app')

@section('title', 'تعديل عرض سابق')

@section('content')
<section class="max-w-xl mx-auto space-y-6">

<h1 class="text-2xl font-bold">✏️ تعديل عرض</h1>

<form method="POST" enctype="multipart/form-data"
      action="{{ route('admin.archive.update', $archive) }}"
      class="space-y-4 bg-black/40 p-5 rounded-xl border border-white/10">
@csrf
@method('PUT')

<input name="title" value="{{ $archive->title }}"
       class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

<textarea name="description" rows="4"
          class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">{{ $archive->description }}</textarea>

<input name="video_url" value="{{ $archive->video_url }}"
       class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

<input type="number" name="year" value="{{ $archive->year }}"
       class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

{{-- بوستر العرض --}}
<div class="space-y-1">
    <label class="text-xs text-gray-300">
        🖼️ بوستر العرض (الصورة الأساسية)
    </label>

    @if($archive->poster_path)
        <img src="{{ asset('storage/'.$archive->poster_path) }}"
             class="w-full h-48 object-cover rounded-lg border border-white/10 mb-2">
    @endif

    <input type="file" name="poster" accept="image/*"
           class="w-full text-xs text-gray-300">
</div>

{{-- صور الجاليري --}}
<div class="space-y-2">
    <label class="text-xs text-gray-300">
        📸 صور من العرض (لقطات – مشاهد – بروفات)
    </label>

    @if($archive->images && $archive->images->count())
        <div class="grid grid-cols-3 gap-2">
            @foreach($archive->images as $img)
                <img src="{{ asset('storage/'.$img->image_path) }}"
                     class="h-24 w-full object-cover rounded border border-white/10">
            @endforeach
        </div>
    @endif

    <input type="file" name="images[]" multiple accept="image/*"
           class="w-full text-xs text-gray-300">
</div>
<button class="px-4 py-2 bg-amber-400 text-black rounded-full">
    حفظ التعديلات
</button>

</form>
</section>
@endsection
