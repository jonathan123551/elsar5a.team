@extends('layouts.app')

@section('title', 'حجز تذاكر - ' . $showTime->show->title)

@section('content')
<section class="max-w-6xl mx-auto px-4">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- 🎭 Show Details --}}
        <div class="md:col-span-1 bg-black/40 border border-white/10 rounded-3xl p-5 space-y-4
                    shadow-[0_0_40px_rgba(0,0,0,0.4)]">

            <h2 class="text-base font-semibold text-amber-300">🎭 تفاصيل العرض</h2>

            <p class="text-sm text-white font-medium">
                {{ $showTime->show->title }}
            </p>

            <div class="space-y-2 text-xs text-gray-300">

                <div class="flex justify-between">
                    <span>📅 التاريخ</span>
                    <span class="text-gray-100">
                        {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span>⏰ الساعة</span>
                    <span class="text-gray-100">
                        {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span>🎟️ سعر التذكرة</span>
                    <span class="text-amber-300 font-semibold">
                        {{ $showTime->ticket_price }} جنيه
                    </span>
                </div>
            </div>

            {{-- 💳 Payment Info --}}
            <div class="bg-black/50 border border-amber-400/30 rounded-2xl p-4 space-y-3
                        shadow-[0_0_25px_rgba(250,204,21,0.15)]">

                <h3 class="text-sm font-semibold text-amber-300 flex items-center gap-1">
                    💳 بيانات التحويل
                </h3>

                <div class="bg-white/5 rounded-xl p-3">
                    <p class="text-[11px] text-gray-400 mb-1">رقم المحفظة</p>
                    <p class="text-sm font-bold tracking-wide text-white select-all">
                        {{ $transferWallet }}
                    </p>
                </div>

                <div class="bg-white/5 rounded-xl p-3">
                    <p class="text-[11px] text-gray-400 mb-1">InstaPay</p>
                    <p class="text-sm font-bold tracking-wide text-white select-all">
                        {{ $transferInsta }}
                    </p>
                </div>

                <p class="text-[11px] text-gray-400 leading-relaxed">
                    اكتب اسمك ورقم موبايل عليه واتساب،
                    وبعد التحويل خد Screenshot وارفعه في الفورم.
                </p>
            </div>
        </div>

        {{-- 📝 Booking Form --}}
        <div class="md:col-span-2 bg-black/40 border border-white/10 rounded-3xl p-6 space-y-5
                    shadow-[0_0_40px_rgba(0,0,0,0.4)]">

            <h2 class="text-base font-semibold text-amber-300">
                📝 بيانات الحجز
            </h2>

            @if ($errors->any())
                <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-xs rounded-xl p-3">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('bookings.store', $showTime) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-xs mb-1 text-gray-300">الاسم بالكامل</label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}"
                               class="w-full rounded-xl bg-black/60 border border-white/15
                                      px-3 py-2 text-sm text-white
                                      focus:outline-none focus:border-amber-400">
                    </div>

                    <div>
                        <label class="block text-xs mb-1 text-gray-300">
                            رقم الموبايل <span class="text-amber-300">(واتساب)</span>
                        </label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="w-full rounded-xl bg-black/60 border border-white/15
                                      px-3 py-2 text-sm text-white
                                      focus:outline-none focus:border-amber-400">
                    </div>
                </div>

                {{-- Tickets fixed --}}
                <input type="hidden" name="tickets_count" value="1">

                <div>
                    <label class="block text-xs mb-1 text-gray-300">
                        Screenshot لعملية التحويل
                    </label>
                    <input type="file" name="payment_screenshot" accept="image/*"
                           class="w-full text-xs text-gray-300">
                </div>

                <button type="submit"
                        class="w-full sm:w-auto mt-2
                               inline-flex items-center justify-center
                               px-6 py-2.5 rounded-full
                               bg-amber-400 text-black text-sm font-semibold
                               hover:bg-amber-300 transition">
                    إرسال طلب الحجز
                </button>
            </form>
        </div>

    </div>
</section>
@endsection
