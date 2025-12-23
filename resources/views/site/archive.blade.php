
@extends('layouts.app')

@section('title', 'العروض السابقة')

@section('content')
<section class="space-y-6">

    <h1 class="text-2xl font-bold mb-6">🎭 العروض السابقة</h1>

    @if($archives->isEmpty())
        <div class="bg-black/40 border border-white/10 rounded-xl p-6 text-center text-sm text-gray-400">
            لا توجد عروض سابقة مضافة حتى الآن.
        </div>
    @else
        <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

            @foreach($archives as $archive)
                <a href="{{ route('archive.show', $archive) }}"
                   class="group bg-black/40 border border-white/10 rounded-2xl overflow-hidden
                          hover:border-amber-400/60 transition duration-300">

                    {{-- Poster --}}
                    @if(!empty($archive->poster_path))
                        <div class="w-full aspect-[3/4] bg-black flex items-center justify-center">
                            <img
                                src="{{ $archive->poster_path }}"
                                alt="{{ $archive->title }}"
                                class="max-h-full max-w-full object-contain
                                       group-hover:scale-105 transition duration-500">
                        </div>
                    @endif

                    {{-- Content --}}
                    <div class="p-4 space-y-1">
                        <h3 class="font-semibold text-sm text-white">
                            {{ $archive->title }}
                        </h3>

                        @if(!empty($archive->year))
                            <p class="text-[11px] text-gray-400">
                                سنة العرض: {{ $archive->year }}
                            </p>
                        @endif

                        <span
                            class="inline-block mt-2 text-xs px-4 py-1.5 rounded-full
                                   bg-amber-400 text-black font-medium">
                            المزيد
                        </span>
                    </div>

                </a>
            @endforeach

        </div>
    @endif

</section>
@endsection
