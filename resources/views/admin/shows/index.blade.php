@extends('layouts.app')

@section('title', 'إدارة العروض')

@section('content')

<section class="space-y-5">


{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

    <h1 class="text-xl sm:text-2xl font-bold">إدارة العروض</h1>

    <div class="flex gap-2">

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

{{-- Status Message --}}
@if(session('status'))
    <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3">
        {{ session('status') }}
    </div>
@endif

{{-- Empty --}}
@if($shows->isEmpty())
    <p class="text-sm text-gray-400">لا يوجد عروض حالياً.</p>
@else

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

    </div>

    {{-- Bottom Row (🔥 هنا الجديد) --}}
    <div class="flex items-center justify-between">

        

        {{-- Switch --}}
        <form action="{{ route('admin.shows.toggle', $show) }}" method="POST">
            @csrf

            <button type="submit" class="flex items-center gap-2">

                <span class="text-[10px]
                    {{ $show->is_active ? 'text-emerald-400' : 'text-gray-400' }}">
                    {{ $show->is_active ? 'فعال' : 'مخفي' }}
                </span>

                <div class="relative w-11 h-6 rounded-full transition-all duration-300
                    {{ $show->is_active ? 'bg-emerald-500' : 'bg-gray-600' }}">

                    <div class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow-md
                        transition-all duration-300
                        {{ $show->is_active ? 'right-0.5' : 'right-5' }}">
                    </div>

                </div>

            </button>
        </form>

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
