@extends('layouts.app')

@section('title', 'إدارة العروض السابقة')

@section('content')
<section class="space-y-6">

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">العروض السابقة</h1>

        <div class="flex items-center gap-2">
            {{-- إضافة عرض --}}
            <a href="{{ route('admin.archive.create') }}"
               class="text-xs px-4 py-2 rounded-full bg-emerald-500 text-black hover:bg-emerald-400 transition">
                ➕ إضافة عرض سابق
            </a>

            {{-- رجوع --}}
            <a href="{{ route('admin.dashboard') }}"
               class="text-xs px-3 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
                ← رجوع للوحة التحكم
            </a>
        </div>
    </div>

    @if($archives->isEmpty())
        <p class="text-sm text-gray-400">
            لسه مفيش عروض سابقة متضافة.
        </p>
    @else
        <div class="overflow-x-auto border border-white/10 rounded-2xl bg-black/40 text-sm">
            <table class="min-w-full text-gray-100">
                <thead class="bg-white/5 text-xs uppercase text-gray-400">
                    <tr>
                        <th class="px-3 py-2 text-right">البوستر</th>
                        <th class="px-3 py-2 text-right">اسم العرض</th>
                        <th class="px-3 py-2 text-right">الوصف</th>
                        <th class="px-3 py-2 text-right">السنة</th>
                        <th class="px-3 py-2 text-center">إدارة</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($archives as $archive)
                    <tr class="border-t border-white/5 hover:bg-white/5 align-middle">

                        {{-- Poster (Cloudinary URL مباشر) --}}
                        <td class="px-3 py-2">
                            @if($archive->poster_path)
                                <img
                                    src="{{ $archive->poster_path }}"
                                    alt="{{ $archive->title }}"
                                    class="w-14 h-20 object-cover rounded-lg border border-white/10">
                            @else
                                <span class="text-xs text-gray-500">—</span>
                            @endif
                        </td>

                        <td class="px-3 py-2 font-medium">
                            {{ $archive->title }}
                        </td>

                        <td class="px-3 py-2 text-xs text-gray-400 max-w-xs truncate">
                            {{ $archive->description }}
                        </td>

                        <td class="px-3 py-2 text-xs text-gray-300">
                            {{ $archive->year ?? '—' }}
                        </td>

                        <td class="px-3 py-2 text-center space-x-1 space-x-reverse">
                            <a href="{{ route('admin.archive.edit', $archive) }}"
                               class="text-xs px-3 py-1 rounded-full bg-amber-400 text-black hover:bg-amber-300 transition">
                                تعديل
                            </a>

                            <form action="{{ route('admin.archive.destroy', $archive) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('متأكد إنك عايز تحذف العرض ده؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-xs px-3 py-1 rounded-full bg-red-500 text-white hover:bg-red-400 transition">
                                    حذف
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>

            </table>
        </div>
    @endif

</section>
@endsection
