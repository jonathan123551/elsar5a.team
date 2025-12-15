@extends('layouts.app')

@section('title', 'العروض السابقة')

@section('content')
<section class="space-y-6">

    <h1 class="text-2xl font-bold text-amber-400 text-center">
        العروض السابقة
    </h1>

    @if($shows->isEmpty())
        <p class="text-center text-gray-400 text-sm">
            لا توجد عروض سابقة حتى الآن.
        </p>
    @else
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($shows as $show)
                <div class="bg-black/40 border border-white/10 rounded-xl overflow-hidden hover:scale-[1.02] transition">
                    
                    @if($show->poster_path)
                        <img src="{{ asset('storage/'.$show->poster_path) }}"
                             class="w-full h-56 object-cover">
                    @endif

                    <div class="p-3 space-y-2">
                        <h2 class="font-semibold text-sm">
                            {{ $show->title }}
                        </h2>

                        <p class="text-xs text-gray-400 line-clamp-3">
                            {{ $show->description }}
                        </p>

                        <a href="{{ route('shows.show', $show) }}"
                           class="inline-block mt-2 text-xs bg-amber-400 text-black px-3 py-1 rounded-full">
                            عرض التفاصيل
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</section>
@endsection
