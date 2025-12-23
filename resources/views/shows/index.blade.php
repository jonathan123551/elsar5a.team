@extends('layouts.app')

@section('title', 'عروض فريق الصرخة المسرحي')

@section('content')

    {{-- Hero --}}
    <section class="scream-hero mb-10">
        <div class="scream-border scream-pulse">
            <div class="scream-card px-5 py-6 md:px-7 md:py-7 flex flex-col md:flex-row gap-6 items-start md:items-center">
                <div class="flex-1 space-y-3">
                    <p class="text-amber-300 text-[11px] font-semibold tracking-[0.35em] uppercase">
                        LIVE • THEATER • SCREAM
                    </p>
                    <h1 class="scream-title text-2xl md:text-3xl font-extrabold leading-relaxed">
                        كثيرًا ما فَسدت عقولُنا مما حملته لها مدخلاتُنا…  
                        ونحن هنا لنُغيِّر ذلك، فقط بالصُّراخ.
                    </h1>
                    <p class="text-sm md:text-base text-gray-200 leading-relaxed">
                        نَصرخ هنا وهناك، نَدعو الجميع للمجيء إلينا ومنحنا من وقتهم القليل؛
                        فنحن لا نريد سوى حواسِّكم. ثم نصرخ، نبحث في مدخلاتِكم لنُخرِج ما هو فاسد
                        ونزرع بدلاً منه ثمرًا صالحًا، لا نريد سوى عقولِكم.
                    </p>
                    <p class="text-xs md:text-sm text-gray-300 leading-relaxed">
                        والآن نصرخ بالتعاليم الصحيحة لنغيِّر ما فَسَد.  
                        وكل ما نحتاجه هو أن تأتوا إلى <span class="text-amber-300 font-semibold">مصدر الصراخ</span>؛
                        فدائمًا يكون على المسرح.
                        <span class="text-rose-300">❤ نجول، نصرخ… فيزداد العقل وعيًا ❤</span>
                    </p>
                </div>

                {{-- Logo --}}
                <div class="flex-1 flex justify-center md:justify-end">
                    <div class="relative w-40 h-40 md:w-52 md:h-52 rounded-full border border-amber-400/60 overflow-hidden shadow-[0_0_50px_rgba(250,204,21,0.65)] flex items-center justify-center">
                        <img src="{{ asset('images/sarkha-logo.png') }}"
                            alt="فريق الصرخة المسرحي"
                            class="w-28 h-28 md:w-36 md:h-36 object-contain filter invert brightness-125">
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- العروض --}}
    <section class="space-y-4">
        <div class="flex items-center justify-between mb-2 gap-2">
            <h2 class="text-xl font-semibold">العروض المتاحة</h2>
            @if(!$shows->isEmpty())
                <span class="text-[11px] px-3 py-1 rounded-full bg-white/5 border border-white/10 text-gray-300">
                    🎭 {{ $shows->count() }} عرض متاح للحجز
                </span>
            @endif
        </div>

        @if($shows->isEmpty())
            <div class="text-gray-400 text-sm bg-black/40 border border-white/10 rounded-2xl p-4">
                لسه مفيش عروض متاحة حاليًا انتظرنا قريبا❤️.
            </div>
        @else
            <div class="grid md:grid-cols-2 gap-5">
                @foreach($shows as $show)
                    <article
                        class="group bg-black/40 border border-white/10 rounded-2xl p-4 flex flex-col justify-between shadow-lg shadow-black/40
                               hover:border-amber-400/70 hover:shadow-[0_0_40px_rgba(250,204,21,0.35)] hover:-translate-y-1 transition-all duration-300">

                        {{-- لو أول عرض نديّه شارة "عرض مميز" --}}
                        @if($loop->first)
                            <div class="mb-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-400/10 border border-amber-400/60 text-[11px] text-amber-200">
                                    🔥 عرض مُميز
                                </span>
                            </div>
                        @endif

                     {{-- بوستر العرض --}}
@if($show->poster_path)
    <div class="relative mb-4 rounded-xl overflow-hidden border border-white/10">
        <img
            src="{{ $show->poster_path }}"
            alt="{{ $show->title }}"
            class="w-full h-auto object-contain
                   transition-transform duration-500 group-hover:scale-[1.02]">

        {{-- شريط الحجز --}}
        <div class="absolute bottom-2 left-2 text-[11px] px-2 py-1 rounded-full bg-black/70 border border-white/20 text-gray-200">
            🎫 احجز مقعدك قبل النفاد
        </div>
    </div>
@endif



                        <div class="space-y-2">
                            <h3 class="text-lg font-bold">{{ $show->title }}</h3>
                           <p class="text-sm text-gray-300 leading-relaxed whitespace-pre-line">
    {{ $show->description }}
</p>


                        <div class="mt-4 space-y-2 text-xs text-gray-400">
                            <p> المواعيد:</p>
                            <ul class="space-y-1">
                                @forelse($show->showTimes->take(2) as $time)
                                    <li class="flex items-center justify-between bg-white/5 rounded-lg px-2 py-1">
                                        <span>
                                            {{-- التاريخ d/m/Y و الساعة 12 ساعة g:i A --}}
                                            {{ $time->date->format('d/m/Y') }}
                                            •
                                            {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                                        </span>
                                        <span class="text-amber-300 font-medium">{{ $time->ticket_price }} ج</span>
                                    </li>
                                @empty
                                    <li class="text-[11px] text-gray-500">
                                        لا توجد مواعيد متاحة حاليًا لهذا العرض.
                                    </li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-xs text-gray-400 flex items-center gap-1">
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-400/70 animate-pulse"></span>
                                {{ $show->showTimes->count() }} موعد متاح
                            </span>
                            <a href="{{ route('shows.show', $show) }}"
                               class="inline-flex items-center gap-1 text-sm font-medium bg-amber-400 text-black px-3 py-1.5 rounded-full hover:bg-amber-300 transition">
                                تفاصيل &amp; حجز
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

    </section>
@endsection
