@extends('layouts.app')

@section('title', 'إدارة العروض السابقة')

@section('content')
<section class="space-y-6">

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">العروض السابقة</h1>

        <a href="{{ route('admin.dashboard') }}"
           class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع للوحة التحكم
        </a>
    </div>

    @if($archivedShows->isEmpty())
        <p class="text-sm text-gray-400">
            لسه مفيش عروض متضافة كعروض سابقة (is_active = 0).
        </p>
    @else
        <div class="overflow-x-auto border border-white/10 rounded-2xl bg-black/40 text-sm">
            <table class="min-w-full text-gray-100">
                <thead class="bg-white/5 text-xs uppercase text-gray-400">
                    <tr>
                        <th class="px-3 py-2 text-right">اسم العرض</th>
                        <th class="px-3 py-2 text-right">تاريخ الإنشاء</th>
                        <th class="px-3 py-2 text-right">حالة الظهور</th>
                        <th class="px-3 py-2 text-center">إدارة</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($archivedShows as $show)
                    <tr class="border-t border-white/5 hover:bg-white/5">
                        <td class="px-3 py-2">
                            {{ $show->title }}
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-400">
                            {{ $show->created_at?->format('Y-m-d') }}
                        </td>
                        <td class="px-3 py-2 text-xs">
                            <span class="px-2 py-0.5 rounded-full bg-slate-500/20 text-slate-100 border border-slate-400/40">
                                غير معروض (is_active = 0)
                            </span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <a href="{{ route('admin.shows.edit', $show) }}"
                               class="text-xs px-3 py-1 rounded-full bg-amber-400 text-black hover:bg-amber-300 transition">
                                تعديل العرض
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

</section>
@endsection
