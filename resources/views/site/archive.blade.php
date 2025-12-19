@extends('layouts.app')

@section('title', 'العروض السابقة')

@section('content')
<section class="space-y-6">

    <h1 class="text-2xl font-bold mb-4">🎭 العروض السابقة</h1>

    @if($archives->isEmpty())
        <div class="bg-black/40 border border-white/10 rounded-xl p-6 text-center text-sm text-gray-400">
            لا توجد عروض سابقة مضافة حتى الآن.
        </div>
    @else
        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-6">

            @foreach($archives as $archive)
                <div
                    class="bg-black/40 border border-white/10 rounded-xl overflow-hidden
                           hover:scale-[1.02] transition duration-300">

                    {{-- Poster --}}
                    @if(!empty($archive->poster_path))
                        <img
                            src="{{ asset('storage/' . $archive->poster_path) }}"
                            alt="{{ $archive->title }}"
                            class="h-56 w-full object-cover">
                    @endif

                    <div class="p-4 space-y-2">

                        {{-- Title --}}
                        <h3 class="font-semibold text-base">
                            {{ $archive->title }}
                        </h3>

                        {{-- Year --}}
                        @if(!empty($archive->year))
                            <p class="text-[11px] text-gray-400">
                                سنة العرض: {{ $archive->year }}
                            </p>
                        @endif

                        {{-- Description --}}
                        @if(!empty($archive->description))
                            <p class="text-xs text-gray-400 line-clamp-4">
                                {{ $archive->description }}
                            </p>
                        @endif

                        {{-- Video --}}
                        @if(!empty($archive->video_url))
                            <a href="{{ $archive->video_url }}"
                               target="_blank"
                               class="inline-block text-xs text-amber-300 hover:underline mt-1">
                                ▶️ مشاهدة الفيديو
                            </a>
                        @endif

                        {{-- Gallery Images --}}
                        @if(method_exists($archive, 'images') && $archive->images->count())
                            <div class="grid grid-cols-3 gap-2 mt-3">
                                @foreach($archive->images as $img)
                                    <img
                                        src="{{ asset('storage/' . $img->image_path) }}"
                                        class="h-20 w-full object-cover rounded-lg border border-white/10"
                                        loading="lazy">
                                @endforeach
                            </div>
                        @endif

                    </div>
                </div>
            @endforeach

        </div>
    @endif

</section>
@endsection
