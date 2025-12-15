@extends('layouts.app')

@section('title', 'مواعيد العرض - ' . $show->title)

@section('content')
    <section class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold mb-1">مواعيد العرض</h1>
                <p class="text-xs text-gray-400">
                    {{ $show->title }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.shows.index') }}"
                   class="text-xs px-3 py-1.5 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
                    ← رجوع لإدارة العروض
                </a>

                <a href="{{ route('admin.shows.times.create', $show) }}"
                   class="inline-flex items-center px-4 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition">
                    + إضافة موعد جديد
                </a>
            </div>
        </div>

        @if(session('status'))
            <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3">
                {{ session('status') }}
            </div>
        @endif

        @if($times->isEmpty())
            <div class="text-sm text-gray-400 bg-black/40 border border-white/10 rounded-2xl p-4">
                لا توجد مواعيد لهذا العرض حتى الآن.
            </div>
        @else
            <div class="bg-black/40 border border-white/10 rounded-2xl overflow-hidden">
                <table class="w-full text-sm text-gray-200">
                    <thead class="bg-white/5 text-xs uppercase text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-right">التاريخ</th>
                            <th class="px-3 py-2 text-right">الساعة</th>
                            <th class="px-3 py-2 text-right">السعر</th>
                            <th class="px-3 py-2 text-right">المتاح / الإجمالي</th>
                            <th class="px-3 py-2 text-right">الحالة</th>
                            <th class="px-3 py-2 text-right">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($times as $time)
                        @php
                            $fewTickets = $time->available_tickets > 0 && $time->available_tickets <= 10;
                        @endphp
                        <tr class="border-t border-white/5">
                            {{-- التاريخ بصيغة d/m/Y --}}
                            <td class="px-3 py-2 text-xs">
                                {{ $time->date->format('d/m/Y') }}
                            </td>

                            {{-- الوقت بصيغة 12 ساعة g:i A --}}
                            <td class="px-3 py-2 text-xs">
                                {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                            </td>

                            <td class="px-3 py-2 text-xs text-amber-300 font-semibold">
                                {{ $time->ticket_price }} ج
                            </td>

                            <td class="px-3 py-2 text-xs">
                                {{ $time->available_tickets }} / {{ $time->total_tickets }}
                            </td>

                            <td class="px-3 py-2 text-xs">
                                @if($time->is_sold_out || $time->available_tickets <= 0)
                                    <span class="px-2 py-1 rounded-full bg-red-500/20 text-red-200 border border-red-500/40">
                                        Sold Out
                                    </span>
                                @elseif($fewTickets)
                                    <span class="px-2 py-1 rounded-full bg-amber-400/10 text-amber-200 border border-amber-400/40">
                                        مقاعد محدودة
                                    </span>
                                @else
                                    <span class="px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-200 border border-emerald-500/40">
                                        متاح للحجز
                                    </span>
                                @endif
                            </td>

                            <td class="px-3 py-2 text-xs">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.shows.times.edit', [$show, $time]) }}"
                                       class="px-2 py-1 rounded-full bg-white/10 hover:bg-white/20">
                                        تعديل
                                    </a>

                                    <form action="{{ route('admin.shows.times.destroy', [$show, $time]) }}"
                                          method="POST"
                                          onsubmit="return confirm('متأكد إنك عايز تحذف هذا الموعد؟');">
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
