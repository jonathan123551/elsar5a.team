@extends('layouts.app')

@section('title', 'لوحة تحكم الأدمن')

@section('content')
    <section class="space-y-8">

        {{-- عنوان وترحيب --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold mb-2">لوحة تحكم الأدمن 🎭</h1>
                <p class="text-sm text-gray-300">
                    من هنا تقدر تتابع نبض العروض، الحجوزات، والتذاكر اللي طلعت للجمهور.
                </p>
            </div>

            {{-- حالة حفظ آخر مرة (لو حابب تستغل session status) --}}
            @if(session('status'))
                <div class="text-[11px] px-3 py-2 rounded-full bg-emerald-500/10 border border-emerald-400/40 text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif
        </div>

        {{-- صف إحصائيات رئيسي --}}
        <div class="grid md:grid-cols-4 gap-4 text-sm">

            {{-- إجمالي العروض --}}
            <div class="bg-black/40 border border-white/10 rounded-xl p-4 flex flex-col gap-1">
                <span class="text-[11px] text-gray-400">عدد العروض</span>
                <span class="text-2xl font-bold text-amber-300">{{ $totalShows }}</span>
                <span class="text-[11px] text-gray-500">كل العروض المسرحية المسجَّلة على السيستم.</span>
            </div>

            {{-- إجمالي المواعيد --}}
            <div class="bg-black/40 border border-white/10 rounded-xl p-4 flex flex-col gap-1">
                <span class="text-[11px] text-gray-400">مواعيد العروض</span>
                <span class="text-2xl font-bold text-amber-300">{{ $totalShowTimes }}</span>
                <span class="text-[11px] text-gray-500">عدد المرات اللي العروض هتتقدَّم فيها على المسرح.</span>
            </div>

            {{-- إجمالي التذاكر المتبقية --}}
            <div class="bg-black/40 border border-emerald-500/30 rounded-2xl p-4 space-y-2">
                <p class="text-xs text-gray-400">التذاكر المتبقية</p>
                <p class="text-3xl font-bold text-emerald-300">
                    {{ $ticketsRemaining }}
                </p>
                <p class="text-[11px] text-gray-400 mt-1">
                    محسوبة من إجمالي التذاكر الأساسية لكل المواعيد
                    ناقص التذاكر في الحجوزات
                    <span class="text-emerald-300">(pending + approved)</span>.
                    الحجوزات المرفوضة مش بتتحسب.
                </p>
            </div>

            {{-- إجمالي التذاكر المعتمدة --}}
            <div class="bg-black/40 border border-white/10 rounded-xl p-4 flex flex-col gap-1">
                <span class="text-[11px] text-gray-400">التذاكر <span class="text-emerald-300">approved</span>.</span>
                <span class="text-2xl font-bold text-emerald-300">{{ $totalTicketsApproved }}</span>
                <span class="text-[11px] text-gray-500">
                    تذاكر لحجوزات اتأكدت واتقبلت، وطلع لها QR.
                </span>
            </div>
        </div>

        {{-- صف تاني للإيرادات وحالة الحجوزات + إعدادات التحويل --}}
        <div class="grid md:grid-cols-3 gap-4 text-sm">

            {{-- إجمالي الفلوس --}}
            <div class="bg-black/50 border border-amber-400/40 rounded-xl p-4 flex flex-col gap-1 shadow-[0_0_35px_rgba(250,204,21,0.2)]">
                <span class="text-[11px] text-amber-200">إجمالي الإيرادات المعتمدة</span>
                <span class="text-3xl font-extrabold text-amber-300">
                    {{ number_format($totalRevenue, 0) }}
                    <span class="text-sm font-normal">جنيه</span>
                </span>
                <span class="text-[11px] text-gray-500">
                    محسوبة من الحجوزات اللي حالتها
                    <span class="text-emerald-300 font-semibold">approved</span>.
                </span>
            </div>

            {{-- حجوزات قيد المراجعة --}}
            <div class="bg-black/40 border border-white/10 rounded-xl p-4 flex flex-col gap-1">
                <span class="text-[11px] text-gray-400">حجوزات قيد المراجعة</span>
                <span class="text-2xl font-bold text-sky-300">{{ $pendingBookings }}</span>
                <span class="text-[11px] text-gray-500">
                    لسه محتاجة مراجعة Screenshot والتحويل قبل ما تتقبل.
                </span>
            </div>

            {{-- كارت إعدادات بيانات التحويل --}}
            <div class="bg-black/40 border border-emerald-400/40 rounded-xl p-4 flex flex-col gap-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] text-gray-300">إعدادات بيانات التحويل</span>
                    <span class="text-[10px] px-2 py-1 rounded-full bg-emerald-500/10 border border-emerald-400/40 text-emerald-200">
                        يظهر للعميل في صفحة الحجز
                    </span>
                </div>

                <form action="{{ route('admin.settings.payments.update') }}" method="POST" class="space-y-2 text-xs">
                    @csrf

                    <div class="space-y-1">
                        <label class="block text-[11px] text-gray-300">رقم المحفظة</label>
                        <input type="text"
                               name="transfer_wallet"
                               value="{{ old('transfer_wallet', $transferWallet) }}"
                               class="w-full rounded-lg bg-black/60 border border-white/15 px-2 py-1.5 text-xs focus:outline-none focus:border-emerald-400"
                               placeholder="مثال: 010xxxxxxxx">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-[11px] text-gray-300">InstaPay</label>
                        <input type="text"
                               name="transfer_insta"
                               value="{{ old('transfer_insta', $transferInsta) }}"
                               class="w-full rounded-lg bg-black/60 border border-white/15 px-2 py-1.5 text-xs focus:outline-none focus:border-emerald-400"
                               placeholder="مثال: name@instapay">
                    </div>

                    <button type="submit"
                            class="mt-1 inline-flex items-center justify-center px-3 py-1.5 rounded-full bg-emerald-500 text-black text-[11px] font-semibold hover:bg-emerald-400 transition">
                        حفظ بيانات التحويل
                    </button>
                </form>
            </div>
        </div>

        {{-- كروت التحكم الرئيسية --}}
        <div class="grid md:grid-cols-3 gap-6 text-sm font-medium mt-4">

            {{-- إدارة العروض --}}
            <a href="{{ route('admin.shows.index') }}"
               class="group bg-black/40 border border-white/10 rounded-xl p-5 transition
                      hover:border-amber-400/60 hover:shadow-[0_0_30px_rgba(250,204,21,0.35)] hover:-translate-y-1 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div class="text-2xl">🎭</div>
                    <span class="text-[10px] px-2 py-1 rounded-full bg-white/5 border border-white/15 text-gray-300">
                        إدارة العروض
                    </span>
                </div>
                <div>
                    <h2 class="text-base font-semibold mb-1">العروض المسرحية</h2>
                    <p class="text-[11px] text-gray-400">
                        إضافة عروض جديدة، تعديل التفاصيل، رفع البوسترات، وتفعيل/إخفاء العروض من الموقع.
                    </p>
                </div>
            </a>

            {{-- الحجوزات والتحويلات --}}
            <a href="{{ route('admin.bookings.index') }}"
               class="group bg-black/40 border border-white/10 rounded-xl p-5 transition
                      hover:border-amber-400/60 hover:shadow-[0_0_30px_rgba(250,204,21,0.35)] hover:-translate-y-1 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div class="text-2xl">💳</div>
                    <span class="text-[10px] px-2 py-1 rounded-full bg-white/5 border border-white/15 text-gray-300">
                        إدارة الحجوزات
                    </span>
                </div>
                <div>
                    <h2 class="text-base font-semibold mb-1">الحجوزات والتحويلات</h2>
                    <p class="text-[11px] text-gray-400">
                        مراجعة طلبات الحجز، التأكد من التحويلات، واعتماد التذاكر وإرسال الـ QR للحضور.
                    </p>
                </div>
            </a>

            {{-- وضع فحص التذاكر --}}
            <a href="{{ route('admin.scanner') }}"
               class="group bg-black/40 border border-white/10 rounded-xl p-5 transition
                      hover:border-amber-400/60 hover:shadow-[0_0_30px_rgba(250,204,21,0.35)] hover:-translate-y-1 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div class="text-2xl">📷</div>
                    <span class="text-[10px] px-2 py-1 rounded-full bg-white/5 border border-white/15 text-gray-300">
                        فحص التذاكر على الباب
                    </span>
                </div>
                <div>
                    <h2 class="text-base font-semibold mb-1">وضع Scan تذاكر الـ QR</h2>
                    <p class="text-[11px] text-gray-400">
                        افتح من موبايل المسؤول على باب المسرح، وامسح كود كل تذكرة
                        عشان تتأكد إن الحجز صالح ومش مستخدم قبل كده.
                    </p>
                </div>
            </a>

        </div>

        {{-- جدول المواعيد والتذاكر لكل ميعاد --}}
        <section class="mt-4 space-y-3">


            <h2 class="text-sm font-semibold text-gray-200 mb-2">
                المواعيد والتذاكر لكل عرض
            </h2>

            {{-- 💻 DESKTOP TABLE --}}
            <div class="hidden md:block overflow-x-auto border border-white/5 rounded-2xl bg-black/40">
                <table class="min-w-full text-xs text-gray-200">

                    <thead class="bg-white/5 text-[11px] uppercase tracking-wide">
                    <tr>
                        <th class="px-3 py-2 text-right">العرض</th>
                        <th class="px-3 py-2 text-right">التاريخ</th>
                        <th class="px-3 py-2 text-right">الساعة</th>
                        <th class="px-3 py-2 text-center">إجمالي</th>
                        <th class="px-3 py-2 text-center text-emerald-300">Approved</th>
                        <th class="px-3 py-2 text-center text-amber-300">Pending</th>
                        <th class="px-3 py-2 text-center text-sky-300">المتبقي</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($showTimesStats as $time)
                        <tr class="border-t border-white/5 hover:bg-white/5">

                            <td class="px-3 py-2">{{ $time->show->title }}</td>

                            <td class="px-3 py-2">
                                {{ $time->date?->format('Y-m-d') }}
                            </td>

                            <td class="px-3 py-2">
                                {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                            </td>

                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-1 rounded-full bg-white/5 border border-white/10">
                                    {{ $time->total_tickets }}
                                </span>
                            </td>

                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-1 rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                    {{ $time->approved_tickets }}
                                </span>
                            </td>

                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-1 rounded-full bg-amber-400/10 text-amber-300 border border-amber-400/30">
                                    {{ $time->pending_tickets }}
                                </span>
                            </td>

                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-1 rounded-full
                                    {{ $time->remaining_tickets > 0
                                        ? 'bg-sky-500/15 text-sky-300 border border-sky-500/30'
                                        : 'bg-red-500/15 text-red-300 border border-red-500/30' }}">
                                    {{ $time->remaining_tickets }}
                                </span>
                            </td>

                        </tr>
                    @endforeach
                    </tbody>

                </table>
            </div>

            {{-- 📱 MOBILE CARDS --}}
            <div class="md:hidden space-y-3">

            @forelse($showTimesStats as $time)

                <div class="bg-black/40 border border-white/10 rounded-xl p-3 space-y-3 shadow-md">

                    {{-- Header --}}
                    <div class="flex justify-between text-xs items-center">

                    {{-- 🎭 اسم العرض --}}
                    <span class="text-gray-400 flex items-center gap-1">
                        <span>🎭</span>
                        <span>{{ $time->show->title }}</span>
                    </span>

                    {{-- ⏰ الوقت --}}
                    <span class="text-amber-400 font-medium">
                        {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                    </span>

                </div>

                {{-- 📅 التاريخ --}}
                <div class="text-xs text-gray-400 flex items-center gap-1">
                    <span>📅</span>
                    <span>{{ $time->date?->format('Y-m-d') }}</span>
                </div>

                    {{-- Stats --}}
                    <div class="grid grid-cols-2 gap-2 text-xs">

                        <div class="flex justify-between bg-white/5 rounded-lg px-2 py-1">
                            <span class="text-gray-400">إجمالي</span>
                            <span>{{ $time->total_tickets }}</span>
                        </div>

                        <div class="flex justify-between bg-emerald-500/10 rounded-lg px-2 py-1">
                            <span class="text-emerald-300">Approved</span>
                            <span class="text-emerald-300 font-semibold">
                                {{ $time->approved_tickets }}
                            </span>
                        </div>

                        <div class="flex justify-between bg-amber-400/10 rounded-lg px-2 py-1">
                            <span class="text-amber-300">Pending</span>
                            <span class="text-amber-300 font-semibold">
                                {{ $time->pending_tickets }}
                            </span>
                        </div>

                        <div class="flex justify-between rounded-lg px-2 py-1
                            {{ $time->remaining_tickets > 0 ? 'bg-sky-500/10' : 'bg-red-500/10' }}">

                            <span class="{{ $time->remaining_tickets > 0 ? 'text-sky-300' : 'text-red-300' }}">
                                المتبقي
                            </span>

                            <span class="font-semibold
                                {{ $time->remaining_tickets > 0 ? 'text-sky-300' : 'text-red-300' }}">
                                {{ $time->remaining_tickets }}
                            </span>

                        </div>

                    </div>

                </div>

            @empty
                <div class="text-center text-gray-400 text-sm">
                    لا توجد بيانات
                </div>
            @endforelse

            </div>
        </section>



        <hr class="border-white/10">

        <div class="flex items-center gap-4">

    {{-- زر تسجيل الخروج --}}
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button class="text-xs text-red-400 hover:text-red-300 transition">
            تسجيل خروج
        </button>
    </form>

    {{-- زر تعديل About --}}
    <a href="{{ route('admin.about.edit') }}"
       class="text-xs text-amber-400 hover:text-amber-300 transition">
        تعديل About
    </a>

    {{-- زر تعديل العروض السابقة --}}
    <a href="{{ route('admin.archive.index') }}"
       class="text-xs text-emerald-400 hover:text-emerald-300 transition">
        تعديل العروض السابقة
    </a>

</div>

    </section>
@endsection
