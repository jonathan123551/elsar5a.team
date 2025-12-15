@extends('layouts.app')

@section('title', 'عن فريق الصرخة')

@section('content')
<section class="max-w-3xl mx-auto space-y-8">

    <h1 class="text-3xl font-bold text-center text-amber-400">
        عن فريق الصرخة المسرحي
    </h1>

    <p class="text-sm text-gray-300 leading-relaxed text-center">
        نحن فريق مسرحي مستقل، نؤمن بأن الفن رسالة، وبأن المسرح قادر على إيقاظ الوعي،
        وتحريك العقول، وطرح الأسئلة قبل تقديم الإجابات.
    </p>

    <div class="grid md:grid-cols-2 gap-6">

        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-2">
            <h3 class="text-amber-400 font-semibold">📅 تاريخ التأسيس</h3>
            <p class="text-xs text-gray-300">تم تأسيس الفريق عام 2020</p>
        </div>

        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-2">
            <h3 class="text-amber-400 font-semibold">🎭 نوع العروض</h3>
            <p class="text-xs text-gray-300">عروض اجتماعية – فلسفية – تجريبية</p>
        </div>

        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-2">
            <h3 class="text-amber-400 font-semibold">📺 قناتنا على يوتيوب</h3>
            <a href="#" class="text-xs text-amber-300 underline">رابط القناة</a>
        </div>

        <div class="bg-black/40 border border-white/10 rounded-xl p-4 space-y-2">
            <h3 class="text-amber-400 font-semibold">📸 إنستجرام</h3>
            <a href="#" class="text-xs text-amber-300 underline">رابط الحساب</a>
        </div>

    </div>

</section>
@endsection
