<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'فريق الصرخة المسرحي')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

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
</head>
<body class="stage-bg min-h-screen text-gray-100">

    {{-- Navbar --}}
    <header class="border-b border-white/10 bg-black/40 backdrop-blur sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">

            {{-- اللوجو + الاسم --}}
            <div class="flex items-center gap-3 w-full md:w-auto">
                <img src="{{ asset('images/sarkha-logo.png') }}"
                     class="w-12 h-12 sm:w-16 sm:h-16 object-contain invert brightness-125 drop-shadow-[0_0_15px_rgba(255,255,255,0.6)]"
                     alt="فريق الصرخة المسرحي">
                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-semibold tracking-wide truncate">
                        فريق الصرخة المسرحي
                    </div>
                    <div class="text-[11px] sm:text-xs text-gray-400">
                        حجز تذاكر العروض
                    </div>
                </div>
            </div>

            {{-- الناف بار --}}
            <nav class="flex flex-wrap items-center gap-2 text-xs sm:text-sm font-medium
                        bg-black/40 backdrop-blur px-3 py-2 rounded-full border border-white/10
                        self-stretch md:self-auto justify-center md:justify-start">

                {{-- Home --}}
                <a href="{{ route('shows.index') }}"
                   class="px-3 sm:px-4 py-1.5 rounded-full transition-all duration-200
                          hover:bg-amber-400 hover:text-black
                          {{ request()->routeIs('shows.index') ? 'bg-amber-400 text-black' : 'text-gray-300' }}">
                    Home
                </a>

                

                {{-- العروض السابقة --}}
                <a href="{{ route('archive') }}"
                   class="px-3 sm:px-4 py-1.5 rounded-full transition-all duration-200
                          hover:bg-amber-400 hover:text-black
                          {{ request()->routeIs('archive') ? 'bg-amber-400 text-black' : 'text-gray-300' }}">
                    العروض السابقة
                </a>

                {{-- About --}}
                <a href="{{ route('about') }}"
                   class="px-3 sm:px-4 py-1.5 rounded-full transition-all duration-200
                          hover:bg-amber-400 hover:text-black
                          {{ request()->routeIs('about') ? 'bg-amber-400 text-black' : 'text-gray-300' }}">
                    About
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
        <div class="max-w-5xl mx-auto px-4 py-4 flex flex-col md:flex-row items-center justify-between text-xs text-gray-400 gap-2">
            <div>© {{ now()->year }} فريق الصرخة المسرحي – نجول، نصرخ… فيزداد العقل وعيًا.</div>
            <div class="flex gap-2">
                <span>أجواء مسرح • حجز أونلاين • QR Tickets</span>
            </div>
        </div>
    </footer>
</body>
</html>
