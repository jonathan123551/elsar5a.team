@extends('layouts.app')

@section('title', $archive->title)

@section('content')
<section class="space-y-10">

{{-- Hero --}}
<div class="relative rounded-3xl overflow-hidden border border-white/10">

    <img src="{{ asset('storage/'.$archive->poster_path) }}"
         class="w-full h-[60vh] object-cover">

    <div class="absolute inset-0 bg-black/60"></div>

    <div class="absolute bottom-6 right-6 left-6">
        <h1 class="text-3xl font-bold mb-2">{{ $archive->title }}</h1>
        <p class="text-sm text-gray-300">سنة العرض: {{ $archive->year }}</p>
    </div>
</div>

{{-- Description --}}
@if($archive->description)
<div class="bg-black/40 border border-white/10 rounded-2xl p-6">
    <h2 class="font-semibold mb-2">📖 وصف العرض</h2>
    <p class="text-sm text-gray-300 leading-relaxed">
        {{ $archive->description }}
    </p>
</div>
@endif

{{-- Video --}}
@if($archive->video_url)
<div class="bg-black/40 border border-white/10 rounded-2xl p-6">
    <h2 class="font-semibold mb-4">🎥 مشاهدة العرض</h2>
    <div class="aspect-video rounded-xl overflow-hidden">
        <iframe
            src="{{ str_replace('watch?v=', 'embed/', $archive->video_url) }}"
            class="w-full h-full"
            allowfullscreen>
        </iframe>
    </div>
</div>
@endif

{{-- Gallery --}}
@if($archive->images && $archive->images->count())
<div>
    <h2 class="font-semibold mb-4">🖼️ صور من العرض</h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($archive->images as $img)
            <img src="{{ asset('storage/'.$img->image_path) }}"
                 class="h-40 w-full object-cover rounded-xl
                        hover:scale-105 transition cursor-pointer">
        @endforeach
    </div>
</div>
@endif

{{-- Back --}}
<a href="{{ route('archive') }}"
   class="inline-block mt-6 text-xs px-4 py-2 rounded-full
          bg-white/10 hover:bg-white/20 transition">
    ← رجوع للعروض السابقة
</a>

</section>
@endsection
