@extends('layouts.app')

@section('title', 'حالة الحجز')

@section('content')

<section class="max-w-md mx-auto space-y-4 my-6">
    <div class="scream-border">
        <div class="scream-card px-5 py-6 text-center space-y-3">

            <div class="text-4xl">🎟️</div>

            <h1 class="text-xl font-bold text-amber-300">
                حالة الحجز
            </h1>

            <p class="text-xs text-gray-300">
                كود الحجز
            </p>
            <p class="font-mono text-sm text-amber-200 break-all">
                {{ $booking->reference_code }}
            </p>

            @if($booking->showTime && $booking->showTime->show)
                <div class="mt-3 text-sm text-gray-200">
                    🎭 {{ $booking->showTime->show->title }}
                </div>
                <div class="text-xs text-gray-400">
                    📅 {{ optional($booking->showTime->date)->format('d/m/Y') }}
                    • {{ \Carbon\Carbon::parse($booking->showTime->time)->format('g:i A') }}
                </div>
            @endif

            <div class="mt-3 text-xs">
                @if($booking->status === 'approved')
                    <span class="inline-block px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-400/40 text-emerald-200">
                        ✅ تم اعتماد حجزك — راجع رسائل الواتساب لإيجاد التذكرة
                    </span>
                @elseif($booking->status === 'rejected')
                    <span class="inline-block px-3 py-1 rounded-full bg-red-500/15 border border-red-400/40 text-red-200">
                        ❌ هذا الحجز مرفوض
                    </span>
                @else
                    <span class="inline-block px-3 py-1 rounded-full bg-sky-500/15 border border-sky-400/40 text-sky-200">
                        ⏳ حجزك قيد المراجعة
                    </span>
                @endif
            </div>

            <div class="pt-3">
                <a href="{{ url('/') }}"
                   class="inline-block text-xs px-4 py-2 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
                    ⬅️ الصفحة الرئيسية
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
