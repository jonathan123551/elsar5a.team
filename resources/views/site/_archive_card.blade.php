{{-- ============================================================
     Single archive card — shared by both the mobile reel and the
     desktop magazine grid. Kept as a partial so the two layouts
     can't drift apart.

     Vars:
       $archive  : App\Models\Archive instance
       $index    : 0-based index, used to stagger entrance animations
============================================================ --}}
<a href="{{ route('archive.show', $archive) }}"
   class="arch-frame arch-reveal group block focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/70 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
   data-stagger="{{ ($index ?? 0) * 80 }}"
   aria-label="عرض تفاصيل: {{ $archive->title }}{{ $archive->year ? ' - ' . $archive->year : '' }}">

    {{-- Poster --}}
    @if(!empty($archive->poster_path))
        <img
            src="{{ $archive->poster_path }}"
            alt="{{ $archive->title }}"
            loading="lazy"
            decoding="async"
            class="arch-poster">
    @else
        <div class="arch-poster flex items-center justify-center text-gray-500 bg-gradient-to-br from-slate-800 to-slate-950">
            <span class="text-4xl" aria-hidden="true">🎭</span>
        </div>
    @endif

    {{-- Overlay: title + year + "discover" hint --}}
    <div class="arch-overlay">
        @if(!empty($archive->year))
            <span class="arch-year">
                {{ $archive->year }}
            </span>
        @endif

        <h3 class="mt-2 text-base sm:text-lg font-bold text-white leading-snug line-clamp-2">
            {{ $archive->title }}
        </h3>

        <div class="mt-2 flex items-center justify-between">
            <span class="arch-cta">
                اكتشف
                <svg xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 20 20"
                     class="w-3.5 h-3.5"
                     fill="currentColor"
                     aria-hidden="true">
                    <path fill-rule="evenodd"
                          d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                          clip-rule="evenodd" />
                </svg>
            </span>

            <span aria-hidden="true"
                  class="w-1.5 h-1.5 rounded-full bg-amber-300/70 shadow-[0_0_8px_rgba(250,204,21,0.7)]"></span>
        </div>
    </div>
</a>
