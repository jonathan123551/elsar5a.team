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
                   class="group relative bg-black/40 border border-white/10 rounded-2xl overflow-hidden
                          hover:border-amber-400/60 transition duration-300">

                    {{-- Poster --}}
                    @if(!empty($archive->poster_path))
                        <img
                            src="{{ asset('storage/' . $archive->poster_path) }}"
                            alt="{{ $archive->title }}"
                            class="h-72 w-full object-cover
                                   group-hover:scale-105 transition duration-500">
                    @endif

                    {{-- Overlay --}}
                    <div
                        class="absolute inset-0 bg-gradient-to-t
                               from-black/90 via-black/40 to-transparent
                               opacity-0 group-hover:opacity-100 transition">
                    </div>

                    {{-- Content --}}
                    <div
                        class="absolute bottom-0 w-full p-4
                               translate-y-6 group-hover:translate-y-0 transition">

                        <h3 class="font-semibold text-sm text-white mb-1">
                            {{ $archive->title }}
                        </h3>

                        @if(!empty($archive->year))
                            <p class="text-[11px] text-gray-300 mb-2">
                                سنة العرض: {{ $archive->year }}
                            </p>
                        @endif

                        <span
                            class="inline-block text-xs px-4 py-1.5 rounded-full
                                   bg-amber-400 text-black font-medium">
                            المزيد →
                        </span>
                    </div>

                </a>
            @endforeach

        </div>
    @endif

</section>
@endsection
