@extends('layouts.app')

@section('title', 'إدارة العروض')

@section('content')
<section class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

        <h1 class="text-xl sm:text-2xl font-bold">إدارة العروض</h1>

        <div class="flex flex-wrap gap-2">

            <a href="{{ route('admin.shows.create') }}"
               class="flex-1 sm:flex-none text-center px-4 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition">
                + إضافة عرض
            </a>

            <a href="{{ route('admin.dashboard') }}"
               class="flex-1 sm:flex-none text-center px-3 py-2 rounded-full bg-white/5 border border-white/10 text-xs hover:bg-white/10 transition">
                ← رجوع
            </a>

        </div>
    </div>

    {{-- Status --}}
    @if(session('status'))
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3">
            {{ session('status') }}
        </div>
    @endif

    {{-- Empty --}}
    @if($shows->isEmpty())
        <p class="text-sm text-gray-400">لا يوجد عروض حالياً.</p>
    @else

        {{-- Cards --}}
        <div class="grid gap-4">

            @foreach($shows as $show)
                <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-3">

                    {{-- Top --}}
                    <div class="flex items-center gap-3">

                        @if($show->poster_path)
                            <img src="{{ $show->poster_path }}"
                                 class="w-14 h-14 rounded-xl object-cover">
                        @endif

                        <div class="flex-1">
                            <div class="font-semibold text-sm sm:text-base">
                                {{ $show->title }}
                            </div>

                            <div class="text-xs text-gray-400 line-clamp-2">
                                {{ $show->description }}
                            </div>
                        </div>

                        {{-- Status --}}
                        <form action="{{ route('admin.shows.toggle', $show) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="text-[10px] px-2 py-1 rounded-full whitespace-nowrap
                                {{ $show->is_active 
                                    ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' 
                                    : 'bg-gray-600/30 text-gray-300 border border-gray-500/40' }}">
                                {{ $show->is_active ? 'فعال' : 'مخفي' }}
                            </button>
                        </form>

                    </div>

                    {{-- Date --}}
                    <div class="text-[11px] text-gray-500">
                        📅 {{ $show->created_at?->format('Y-m-d') }}
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap gap-2 text-xs">

                        <a href="{{ route('admin.shows.times.index', $show) }}"
                           class="flex-1 text-center px-3 py-2 rounded-xl bg-purple-500/20 text-purple-100 hover:bg-purple-500/30">
                            المواعيد
                        </a>

                        <a href="{{ route('admin.shows.edit', $show) }}"
                           class="flex-1 text-center px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20">
                            تعديل
                        </a>

                        <form action="{{ route('admin.shows.destroy', $show) }}" method="POST"
                              class="flex-1"
                              onsubmit="return confirm('متأكد إنك عايز تحذف العرض؟');">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="w-full px-3 py-2 rounded-xl bg-red-500/20 text-red-200 hover:bg-red-500/30">
                                حذف
                            </button>
                        </form>

                    </div>

                </div>
            @endforeach

        </div>

    @endif
</section>
@endsection