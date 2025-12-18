@extends('layouts.app')

@section('title', 'العروض السابقة')

@section('content')
<section class="grid md:grid-cols-3 gap-6">

@foreach($archives as $archive)
<div class="bg-black/40 border border-white/10 rounded-xl overflow-hidden">

@if($archive->poster_path)
<img src="{{ asset('storage/'.$archive->poster_path) }}"
 class="h-56 w-full object-cover">
@endif

<div class="p-4 space-y-2">
<h3 class="font-semibold">{{ $archive->title }}</h3>

<p class="text-xs text-gray-400">{{ $archive->description }}</p>

@if($archive->video_url)
<a href="{{ $archive->video_url }}" target="_blank"
 class="text-xs text-amber-300">▶️ مشاهدة الفيديو</a>
@endif

@if($archive->images->count())
<div class="grid grid-cols-3 gap-2 mt-2">
@foreach($archive->images as $img)
<img src="{{ asset('storage/'.$img->image_path) }}"
 class="h-20 object-cover rounded">
@endforeach
</div>
@endif

</div>
</div>
@endforeach

</section>
@endsection
