@extends('layouts.app')

@section('title', 'عن فريق الصرخة المسرحي')

@section('content')
<section class="min-h-[70vh] flex items-start justify-center px-4">

    <div class="w-full max-w-3xl bg-black/40 backdrop-blur
                border border-white/10 rounded-3xl
                p-6 md:p-10 space-y-8">

        {{-- العنوان --}}
        <div class="text-center space-y-2">
            <h1 class="text-2xl md:text-3xl font-bold tracking-wide">
                🎭 عن فريق الصرخة المسرحي
            </h1>
            <div class="w-16 h-1 mx-auto rounded-full bg-amber-400"></div>
        </div>

        {{-- الوصف --}}
        @if($about && $about->description)

        @php
            $lines = preg_split('/\r\n|\r|\n/', $about->description);
        @endphp

        <div class="space-y-3 text-sm md:text-base text-gray-200 leading-relaxed">

            @foreach($lines as $line)

                @if(trim($line) !== '')

                    <div class="bg-white/5 border border-white/10 rounded-xl px-4 py-3
                                hover:bg-white/10 transition text-center md:text-right">
                        {{ $line }}
                    </div>

                @endif

            @endforeach

        </div>

        @else 
        <p class="text-sm text-gray-400 text-center">
        لم يتم إضافة معلومات عن الفريق بعد. </p>
        @endif


        {{-- سنة التأسيس --}}
        @if($about && $about->founded_year)
            <div class="text-center text-sm text-gray-300">
                بدأ الفريق نشاطه منذ عام
                <span class="text-amber-300 font-semibold">
                    {{ $about->founded_year }}
                </span>
            </div>
        @endif

        {{-- السوشيال --}}
        @if($about && ($about->youtube || $about->facebook || $about->instagram))
            <div class="flex justify-center gap-3 flex-wrap">

                @if($about->youtube)
                    <a href="{{ $about->youtube }}" target="_blank"
                       class="px-4 py-2 rounded-full text-xs font-medium
                              bg-red-500/10 border border-red-500/40 text-red-200
                              hover:bg-red-500/20 hover:scale-105 transition">
                        YouTube
                    </a>
                @endif

                @if($about->facebook)
                    <a href="{{ $about->facebook }}" target="_blank"
                       class="px-4 py-2 rounded-full text-xs font-medium
                              bg-blue-500/10 border border-blue-500/40 text-blue-200
                              hover:bg-blue-500/20 hover:scale-105 transition">
                        Facebook
                    </a>
                @endif

                @if($about->instagram)
                    <a href="{{ $about->instagram }}" target="_blank"
                       class="px-4 py-2 rounded-full text-xs font-medium
                              bg-pink-500/10 border border-pink-500/40 text-pink-200
                              hover:bg-pink-500/20 hover:scale-105 transition">
                        Instagram
                    </a>
                @endif

            </div>
        @endif

        {{-- زر الرجوع --}}
        <div class="text-center pt-4">
            <a href="{{ route('shows.index') }}"
               class="inline-flex items-center gap-2 text-sm text-gray-300
                      hover:text-amber-300 transition">
                ← رجوع للعروض
            </a>
        </div>

    </div>

</section>
@endsection
