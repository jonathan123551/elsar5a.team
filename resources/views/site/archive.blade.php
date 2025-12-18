@extends('layouts.app')

@section('title', 'العروض السابقة')

@section('content')
<section class="grid md:grid-cols-3 gap-6">

@foreach($archives as $archive)
<div class="bg-black/40 border border-white/10 rounded-xl overflow-hidden hover:scale-[1.02] transition">

    @if($archive->poster_path)
        <img src="{{ asset('storage/'.$archive->poster_path) }}"
             class="h-56 w-full object-cover">
    @endif

    <div class="p-4 space-y-2">
        <h3 class="font-semibold">{{ $archive->title }}</h3>

        <p class="text-xs text-gray-400">
            {{ $archive->description }}
        </p>

        @if($archive->video_url)
            <a href="{{ $archive->video_url }}" target="_blank"
               class="inline-block text-xs text-amber-300">
                ▶️ مشاهدة الفيديو
            </a>
        @endif
    </div>
</div>
@endforeach

</section>
@endsection
