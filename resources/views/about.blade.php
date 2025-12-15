@extends('layouts.app')

@section('title', 'عن فريق الصرخة المسرحي')

@section('content')
<section class="space-y-6 max-w-3xl mx-auto">

    <h1 class="text-2xl md:text-3xl font-bold mb-2">عن فريق الصرخة المسرحي</h1>

    @if($about && $about->description)
        <p class="text-sm text-gray-200 leading-relaxed whitespace-pre-line">
            {{ $about->description }}
        </p>
    @else
        <p class="text-sm text-gray-400">
            لم يتم إضافة معلومات عن الفريق بعد. 👀
        </p>
    @endif

    @if($about && $about->founded_year)
        <p class="text-xs text-gray-400">
            بدأ الفريق نشاطه منذ عام
            <span class="text-amber-300 font-semibold">{{ $about->founded_year }}</span>.
        </p>
    @endif

    {{-- روابط السوشيال --}}
    @if($about && ($about->youtube || $about->facebook || $about->instagram))
        <div class="mt-4 flex flex-wrap gap-3 text-xs">
            @if($about->youtube)
                <a href="{{ $about->youtube }}" target="_blank"
                   class="px-3 py-1 rounded-full bg-red-500/10 border border-red-500/40 text-red-200 hover:bg-red-500/20 transition">
                    YouTube
                </a>
            @endif

            @if($about->facebook)
                <a href="{{ $about->facebook }}" target="_blank"
                   class="px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/40 text-blue-200 hover:bg-blue-500/20 transition">
                    Facebook
                </a>
            @endif

            @if($about->instagram)
                <a href="{{ $about->instagram }}" target="_blank"
                   class="px-3 py-1 rounded-full bg-pink-500/10 border border-pink-500/40 text-pink-200 hover:bg-pink-500/20 transition">
                    Instagram
                </a>
            @endif
        </div>
    @endif

    <a href="{{ route('shows.index') }}"
       class="inline-block mt-4 text-sm text-gray-300 hover:text-amber-300">
        ← رجوع للعروض
    </a>
</section>
@endsection
