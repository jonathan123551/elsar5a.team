<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'فريق الصرخة المسرحي')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#020617">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @hasSection('meta_robots')
        <meta name="robots" content="@yield('meta_robots')">
    @endif
    <meta name="description" content="@yield('meta_description', 'فريق الصرخة المسرحي — حجز تذاكر العروض أونلاين.')">
    <link rel="icon" type="image/png" href="{{ asset('images/sarkha-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/sarkha-logo.png') }}">

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        /* خلفية أجواء مسرح */
        .stage-bg {
            background:
                radial-gradient(circle at top, rgba(255,255,255,0.14), transparent 55%),
                radial-gradient(circle at 20% 0, rgba(251,191,36,0.2), transparent 60%),
                radial-gradient(circle at 80% 0, rgba(239,68,68,0.25), transparent 60%),
                #020617;
        }

        .scream-hero {
            position: relative;
            overflow: hidden;
        }
        .scream-hero::before {
            content: "";
            position: absolute;
            inset: -40%;
            background:
                radial-gradient(circle at 10% 0, rgba(251,191,36,0.22), transparent 60%),
                radial-gradient(circle at 90% 10%, rgba(248,113,113,0.28), transparent 60%);
            opacity: 0.9;
            filter: blur(30px);
            z-index: -1;
        }
        .scream-border {
            border-radius: 1.5rem;
            background: linear-gradient(135deg, rgba(250,204,21,0.5), rgba(248,113,113,0.5));
            padding: 1px;
        }
        .scream-card {
            border-radius: 1.4rem;
            background: radial-gradient(circle at top, rgba(15,23,42,0.95), rgba(2,6,23,0.96));
        }

        @keyframes screamGlow {
            0%, 100% { text-shadow: 0 0 10px rgba(250,204,21,0.4); transform: translateY(0); }
            50% { text-shadow: 0 0 22px rgba(248,113,113,0.9); transform: translateY(-2px); }
        }
        .scream-title {
            animation: screamGlow 2.4s ease-in-out infinite;
        }

        @keyframes screamPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(250,204,21,0.0); }
            50% { box-shadow: 0 0 35px 0 rgba(250,204,21,0.45); }
        }
        .scream-pulse {
            animation: screamPulse 3s ease-in-out infinite;
        }

        .logo-light {
            filter: drop-shadow(0 0 25px rgba(255,255,255,0.7));
        }
    </style>
    <style>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

</head>
<body class="stage-bg min-h-screen text-gray-100">

    {{-- Navbar --}}
    <header class="border-b border-white/10 bg-black/40 backdrop-blur sticky top-0 z-40">
    <div class="max-w-5xl mx-auto px-3 sm:px-4 py-2 flex items-center justify-between gap-2">

        {{-- اللوجو + الاسم --}}
        <a href="{{ url('/') }}"
           class="flex items-center gap-2 group focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60 rounded-xl pr-1"
           aria-label="الصفحة الرئيسية">
            <img src="{{ asset('images/sarkha-logo.png') }}"
                 class="w-9 h-9 sm:w-10 sm:h-10 object-contain invert brightness-125
                        drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]
                        transition-transform duration-300 group-hover:scale-105"
                 alt="فريق الصرخة المسرحي">

            <div class="leading-tight">
                <div class="text-[12px] sm:text-sm font-semibold whitespace-nowrap">
                    فريق الصرخة المسرحي
                </div>
                <div class="hidden sm:block text-[10px] text-gray-400">
                    حجز تذاكر العروض أونلاين
                </div>
            </div>
        </a>

        {{-- الناف بار --}}
        <nav aria-label="التنقل"
            class="flex items-center gap-0.5 sm:gap-1 text-[11px] sm:text-sm font-medium
                   bg-black/50 backdrop-blur px-1.5 sm:px-2 py-1
                   rounded-full border border-white/10 max-w-full overflow-x-auto scrollbar-hide">

            {{-- Home (تروح لـ / مباشرة، مو لـ /shows) --}}
            <a href="{{ url('/') }}"
               class="px-2 sm:px-2.5 py-1 rounded-full transition whitespace-nowrap
                      hover:bg-amber-400 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60
                      {{ request()->routeIs('home') || request()->is('/') ? 'bg-amber-400 text-black' : 'text-gray-300' }}">
               الرئيسية
            </a>

            {{-- العروض السابقة --}}
            <a href="{{ route('archive') }}"
               class="px-2 sm:px-2.5 py-1 rounded-full transition whitespace-nowrap
                      hover:bg-amber-400 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60
                      {{ request()->routeIs('archive') ? 'bg-amber-400 text-black' : 'text-gray-300' }}">
                العروض السابقة
            </a>

            {{-- About --}}
            <a href="{{ route('about') }}"
               class="px-2 sm:px-2.5 py-1 rounded-full transition whitespace-nowrap
                      hover:bg-amber-400 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60
                      {{ request()->routeIs('about') ? 'bg-amber-400 text-black' : 'text-gray-300' }}">
                عن الفريق
            </a>
        </nav>

    </div>
</header>


    {{-- المحتوى الرئيسي --}}
    <main class="max-w-5xl mx-auto px-4 py-6 md:py-10">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t border-white/10 bg-black/60 mt-10">
        <div class="max-w-5xl mx-auto px-4 py-5 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-gray-400">
            <div class="text-center md:text-right space-y-1">
                <div>
                    © {{ now()->year }} فريق الصرخة المسرحي — نجول، نصرخ… فيزداد العقل وعيًا.
                </div>
                <div class="text-[10px] text-gray-500">
                    حجز أونلاين • تذاكر QR
                </div>
            </div>

            <nav aria-label="روابط التذييل"
                 class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
                <a href="{{ url('/') }}"
                   class="hover:text-amber-300 transition focus:outline-none focus-visible:underline">
                    الرئيسية
                </a>
                <span class="text-gray-700" aria-hidden="true">•</span>
                <a href="{{ route('about') }}"
                   class="hover:text-amber-300 transition focus:outline-none focus-visible:underline">
                    عن الفريق
                </a>
                <span class="text-gray-700" aria-hidden="true">•</span>
                <a href="{{ route('archive') }}"
                   class="hover:text-amber-300 transition focus:outline-none focus-visible:underline">
                    العروض السابقة
                </a>
                <span class="text-gray-700" aria-hidden="true">•</span>
                <a href="https://www.instagram.com/elsar5a.team"
                   target="_blank" rel="noopener noreferrer"
                   class="hover:text-amber-300 transition focus:outline-none focus-visible:underline">
                    Instagram
                </a>
                <span class="text-gray-700" aria-hidden="true">•</span>
                <a href="https://wa.me/201000000000"
                   target="_blank" rel="noopener noreferrer"
                   class="hover:text-amber-300 transition focus:outline-none focus-visible:underline">
                    تواصل
                </a>
            </nav>
        </div>
    </footer>
</body>
</html>
