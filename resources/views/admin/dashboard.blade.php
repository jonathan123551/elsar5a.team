@extends('layouts.app')

@section('title', 'لوحة تحكم الأدمن')

@section('content')
<section class="space-y-8">

    {{-- عنوان وترحيب --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-bold mb-1 md:mb-2">
                لوحة تحكم الأدمن 🎭
            </h1>
            <p class="text-xs md:text-sm text-gray-300 leading-relaxed">
                من هنا تقدر تتابع نبض العروض، الحجوزات، والتذاكر اللي طلعت للجمهور.
            </p>
        </div>

        @if(session('status'))
            <div class="text-[11px] px-3 py-2 rounded-full bg-emerald-500/10 border border-emerald-400/40 text-emerald-200">
                {{ session('status') }}
            </div>
        @endif
    </div>

    {{-- إحصائيات --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-sm">

        <div class="bg-black/40 border border-white/10 rounded-xl p-5 space-y-1">
            <p class="text-[11px] text-gray-400">عدد العروض</p>
            <p class="text-3xl md:text-2xl font-bold text-amber-300">{{ $totalShows }}</p>
        </div>

        <div class="bg-black/40 border border-white/10 rounded-xl p-5 space-y-1">
            <p class="text-[11px] text-gray-400">مواعيد العروض</p>
            <p class="text-3xl md:text-2xl font-bold text-amber-300">{{ $totalShowTimes }}</p>
        </div>

        <div class="bg-black/40 border border-emerald-500/30 rounded-xl p-5 space-y-1">
            <p class="text-[11px] text-gray-400">التذاكر المتبقية</p>
            <p class="text-3xl md:text-2xl font-bold text-emerald-300">{{ $ticketsRemaining }}</p>
        </div>

        <div class="bg-black/40 border border-white/10 rounded-xl p-5 space-y-1">
            <p class="text-[11px] text-gray-400">التذاكر المعتمدة</p>
            <p class="text-3xl md:text-2xl font-bold text-emerald-300">{{ $totalTicketsApproved }}</p>
        </div>
    </div>

    {{-- صف الإيرادات --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">

        <div class="bg-black/50 border border-amber-400/40 rounded-xl p-5">
            <p class="text-[11px] text-amber-200">إجمالي الإيرادات</p>
            <p class="text-3xl font-bold text-amber-300">
                {{ number_format($totalRevenue, 0) }} جنيه
            </p>
        </div>

        <div class="bg-black/40 border border-white/10 rounded-xl p-5">
            <p class="text-[11px] text-gray-400">حجوزات Pending</p>
            <p class="text-3xl font-bold text-sky-300">{{ $pendingBookings }}</p>
        </div>

        <div class="bg-black/40 border border-emerald-400/40 rounded-xl p-5">
            <p class="text-[11px] text-gray-400 mb-2">بيانات التحويل</p>

            <form action="{{ route('admin.settings.payments.update') }}" method="POST" class="space-y-2 text-xs">
                @csrf
                <input type="text" name="transfer_wallet"
                       value="{{ old('transfer_wallet', $transferWallet) }}"
                       class="w-full rounded-lg bg-black/60 border border-white/15 px-3 py-2"
                       placeholder="رقم المحفظة">

                <input type="text" name="transfer_insta"
                       value="{{ old('transfer_insta', $transferInsta) }}"
                       class="w-full rounded-lg bg-black/60 border border-white/15 px-3 py-2"
                       placeholder="InstaPay">

                <button class="w-full mt-2 bg-emerald-500 text-black rounded-full py-2 text-xs font-semibold">
                    حفظ البيانات
                </button>
            </form>
        </div>
    </div>

    {{-- كروت التحكم --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5 mt-4">

        <a href="{{ route('admin.shows.index') }}"
           class="bg-black/40 border border-white/10 rounded-xl p-6 md:p-5 hover:border-amber-400 transition">
            <h3 class="font-semibold mb-1">🎭 العروض المسرحية</h3>
            <p class="text-xs text-gray-400">إدارة وتعديل العروض</p>
        </a>

        <a href="{{ route('admin.bookings.index') }}"
           class="bg-black/40 border border-white/10 rounded-xl p-6 md:p-5 hover:border-amber-400 transition">
            <h3 class="font-semibold mb-1">💳 الحجوزات</h3>
            <p class="text-xs text-gray-400">مراجعة واعتماد التذاكر</p>
        </a>

        <a href="{{ route('admin.scanner') }}"
           class="bg-black/40 border border-white/10 rounded-xl p-6 md:p-5 hover:border-amber-400 transition">
            <h3 class="font-semibold mb-1">📷 فحص التذاكر</h3>
            <p class="text-xs text-gray-400">Scan QR على باب المسرح</p>
        </a>
    </div>

    {{-- جدول Desktop --}}
    <div class="hidden md:block overflow-x-auto bg-black/40 rounded-xl border border-white/10 mt-6">
        <table class="w-full text-xs">
            <thead class="bg-white/5">
                <tr>
                    <th class="px-3 py-2 text-right">العرض</th>
                    <th class="px-3 py-2">التاريخ</th>
                    <th class="px-3 py-2">الوقت</th>
                    <th class="px-3 py-2">المتبقي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($showTimesStats as $time)
                    <tr class="border-t border-white/5">
                        <td class="px-3 py-2">{{ $time->show->title }}</td>
                        <td class="px-3 py-2">{{ $time->date }}</td>
                        <td class="px-3 py-2">{{ $time->time }}</td>
                        <td class="px-3 py-2 text-sky-300">{{ $time->remaining_tickets }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden space-y-3 mt-6">
        @foreach($showTimesStats as $time)
            <div class="bg-black/40 border border-white/10 rounded-xl p-4 text-xs">
                <p class="font-semibold text-amber-300">{{ $time->show->title }}</p>
                <p class="text-gray-400">{{ $time->date }} – {{ $time->time }}</p>
                <p class="mt-1 font-bold text-sky-300">المتبقي: {{ $time->remaining_tickets }}</p>
            </div>
        @endforeach
    </div>

    {{-- أزرار أسفل --}}
    <div class="flex flex-col sm:flex-row gap-3 text-xs mt-6">
        <form action="{{ route('logout') }}" method="POST">@csrf
            <button class="text-red-400">تسجيل خروج</button>
        </form>

        <a href="{{ route('admin.about.edit') }}" class="text-amber-400">تعديل About</a>
        <a href="{{ route('admin.archive.index') }}" class="text-emerald-400">العروض السابقة</a>
    </div>

</section>
@endsection
