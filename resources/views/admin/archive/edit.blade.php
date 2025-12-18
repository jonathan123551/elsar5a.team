@extends('layouts.app')

@section('title', 'تعديل عرض')

@section('content')
<section class="max-w-xl mx-auto space-y-6">

    <h1 class="text-2xl font-bold">✏️ تعديل عرض</h1>

    <form method="POST" enctype="multipart/form-data"
          class="space-y-4 bg-black/40 p-5 rounded-xl border border-white/10">
        @csrf
        @method('PUT')

        <input name="title" value="{{ $archive->title }}"
               class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

        <textarea name="description" rows="4"
                  class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">{{ $archive->description }}</textarea>

        <input name="video_url" value="{{ $archive->video_url }}"
               class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

        <input type="intger" name="year"
               value="{{ $archive->show_date }}"
               class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

        @if($archive->poster_path)
            <img src="{{ asset('storage/'.$archive->poster_path) }}"
                 class="w-full h-48 object-cover rounded-lg">
        @endif

        <input type="file" name="poster" accept="image/*"
               class="w-full text-xs text-gray-300">

        <button class="px-4 py-2 bg-amber-400 text-black rounded-full">
            حفظ التعديلات
        </button>
    </form>

</section>
@endsection
