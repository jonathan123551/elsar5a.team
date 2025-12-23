@extends('layouts.app')

@section('title', 'إدارة العروض')

@section('content')
    <section class="space-y-6">

        {{-- الهيدر + زر إضافة عرض + زر رجوع للوحة التحكم --}}
        <div class="flex items-center justify-between gap-3">

            <h1 class="text-2xl font-bold">إدارة العروض</h1>

            <div class="flex items-center gap-2">

                {{-- إضافة عرض جديد --}}
                <a href="{{ route('admin.shows.create') }}"
                   class="inline-flex items-center px-4 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition">
                    + إضافة عرض جديد
                </a>

                {{-- زر الرجوع للوحة التحكم --}}
                <a href="{{ route('admin.dashboard') }}"
                   class="inline-flex items-center px-3 py-2 rounded-full bg-white/5 border border-white/10 text-xs hover:bg-white/10 transition">
                    ← رجوع للوحة التحكم
                </a>

            </div>

        </div>

        @if(session('status'))
            <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3">
                {{ session('status') }}
            </div>
        @endif

        @if($shows->isEmpty())
            <p class="text-sm text-gray-400">لا يوجد عروض حالياً.</p>
        @else
            <div class="bg-black/40 border border-white/10 rounded-2xl overflow-hidden">
                <table class="w-full text-sm text-gray-200">
                    <thead class="bg-white/5 text-xs uppercase text-gray-400">
                    <tr>
                        <th class="px-3 py-2 text-right">#</th>
                        <th class="px-3 py-2 text-right">العرض</th>
                        <th class="px-3 py-2 text-right">الحالة</th>
                        <th class="px-3 py-2 text-right">تاريخ الإضافة</th>
                        <th class="px-3 py-2 text-right">إجراءات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($shows as $show)
                        <tr class="border-t border-white/5">
                            <td class="px-3 py-2">{{ $show->id }}</td>

                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    @if($show->poster_path)
                                        <img src="{{ $show->poster_path}}"
                                             alt=""
                                             class="w-10 h-10 rounded object-cover">
                                    @endif
                                    <div>
                                        <div class="font-semibold">{{ $show->title }}</div>
                                        <div class="text-[11px] text-gray-400 line-clamp-1">
                                            {{ $show->description }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-3 py-2">
                                <form action="{{ route('admin.shows.toggle', $show) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="text-[11px] px-2 py-1 rounded-full
                                            {{ $show->is_active ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-gray-600/30 text-gray-300 border border-gray-500/40' }}">
                                        {{ $show->is_active ? 'فعال' : 'مخفي' }}
                                    </button>
                                </form>
                            </td>

                            <td class="px-3 py-2 text-xs text-gray-400">
                                {{ $show->created_at?->format('Y-m-d') }}
                            </td>

                            <td class="px-3 py-2">
                                <div class="flex flex-wrap items-center gap-2 text-xs">

                                    <a href="{{ route('admin.shows.times.index', $show) }}"
                                       class="px-2 py-1 rounded-full bg-purple-500/20 text-purple-100 hover:bg-purple-500/30">
                                        المواعيد
                                    </a>

                                    <a href="{{ route('admin.shows.edit', $show) }}"
                                       class="px-2 py-1 rounded-full bg-white/10 hover:bg-white/20">
                                        تعديل
                                    </a>

                                    <form action="{{ route('admin.shows.destroy', $show) }}" method="POST"
                                          onsubmit="return confirm('متأكد إنك عايز تحذف العرض؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-1 rounded-full bg-red-500/20 text-red-200 hover:bg-red-500/30">
                                            حذف
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
