@extends('layouts.app')

@section('title', 'حجز تذاكر - ' . $showTime->show->title)

@section('content')
    <section class="grid md:grid-cols-3 gap-6">
        {{-- معلومات العرض --}}
        <div class="md:col-span-1 bg-black/40 border border-white/10 rounded-2xl p-4 space-y-3">
            <h2 class="text-lg font-semibold">تفاصيل العرض</h2>

            <p class="text-sm text-gray-300">{{ $showTime->show->title }}</p>

            <div class="text-xs text-gray-400 space-y-1">
                <p>
                    التاريخ:
                    <span class="text-gray-200">
                        {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}
                    </span>
                </p>

                <p>
                    الساعة:
                    <span class="text-gray-200">
                        {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}
                        {{-- مثال: 5:00 PM --}}
                    </span>
                </p>

                <p>
                    سعر التذكرة:
                    <span class="text-amber-300 font-semibold">
                        {{ $showTime->ticket_price }} جنيه
                    </span>
                </p>

                {{-- شِلنا سطر "المتاح حالياً" من قدام اليوزر --}}
            </div>

            <div class="bg-black/40 border border-amber-400/20 rounded-xl p-4 shadow-[0_0_25px_rgba(250,204,21,0.08)] space-y-3">

    <h3 class="text-sm font-semibold text-amber-300 flex items-center gap-1">
        💳 بيانات التحويل
    </h3>

    {{-- رقم المحفظة --}}
    <div class="bg-white/5 border border-white/10 rounded-lg p-3 space-y-1">
        <p class="text-[11px] text-gray-400">رقم المحفظة</p>
        <p class="text-base font-bold tracking-wide text-white select-all">
            {{ $transferWallet }}
        </p>
    </div>

    {{-- InstaPay --}}
    <div class="bg-white/5 border border-white/10 rounded-lg p-3 space-y-1">
        <p class="text-[11px] text-gray-400">InstaPay</p>
        <p class="text-base font-bold tracking-wide text-white select-all">
            {{ $transferInsta }}
        </p>
    </div>

    <p class="text-[11px] text-gray-400 leading-relaxed">
        اكتب اسمك و رقم موبايلك يكون عليه واتساب.
        وبعد التحويل خد Screenshot وارفعه هنا.
    </p>
</div>

        </div>

        {{-- فورم الحجز --}}
        <div class="md:col-span-2 bg-black/40 border border-white/10 rounded-2xl p-5 space-y-4">
            <h2 class="text-lg font-semibold mb-1">بيانات الحجز</h2>

            @if ($errors->any())
                <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-xs rounded-xl p-3 mb-2">
                    <ul class="list-disc pr-4">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('bookings.store', $showTime) }}" method="post" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs mb-1">الاسم بالكامل</label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}"
                               class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
                    </div>

                    <div>
    <label class="block text-xs mb-1">
        رقم الموبايل 
        <span class="text-red-500">واتساب</span>
    </label>

    <input type="text" name="phone" value="{{ old('phone') }}"
           class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
</div>

                </div>

                {{-- عدد التذاكر ثابت = 1 ومخفي عن اليوزر --}}
                <input type="hidden" name="tickets_count" value="1">

                {{-- شِلنا بلوك "عدد التذاكر" + "إجمالي تقريبي" من الفورم --}}

                <div>
                    <label class="block text-xs mb-1">Screenshot لعملية التحويل</label>
                    <input type="file" name="payment_screenshot" accept="image/*"
                           class="w-full text-xs text-gray-300">
                </div>

                <button type="submit"
                        class="mt-2 inline-flex items-center justify-center px-4 py-2 rounded-full bg-amber-400 text-black text-sm font-medium hover:bg-amber-300 transition">
                    إرسال طلب الحجز
                </button>
            </form>
        </div>
    </section>
@endsection
