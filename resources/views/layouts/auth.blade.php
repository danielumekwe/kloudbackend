<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Welcome') — Kloud101</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" href="/images/icon-192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Anti-FOUC: default to light unless the user has explicitly chosen dark --}}
    <script>
        (function () {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-50 via-slate-50 to-indigo-50 dark:from-[#0a0f1e] dark:via-[#0a0f1e] dark:to-[#0a0f1e]
             text-slate-900 dark:text-slate-100 antialiased min-h-screen flex flex-col"
      x-data="{ darkMode: document.documentElement.classList.contains('dark') }">

    {{-- Header --}}
    <header class="relative z-10 flex items-center justify-between px-6 py-5 lg:px-10">
        <a href="{{ route('login') }}" class="flex items-center">
            <img src="/images/kloud101-logo.png" alt="Kloud101" class="h-10 w-auto bg-white rounded-lg px-2.5 py-1.5 shadow-sm">
        </a>

        <div class="flex items-center gap-3">
            {{-- Dark mode toggle --}}
            <button @click="
                    darkMode = !darkMode;
                    darkMode
                        ? (document.documentElement.classList.add('dark'), localStorage.setItem('theme','dark'))
                        : (document.documentElement.classList.remove('dark'), localStorage.setItem('theme','light'));
                    "
                    class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/[0.08]
                           text-slate-500 dark:text-slate-400 shadow-sm hover:shadow-md transition-all">
                <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>

            {{-- Contextual sign in / sign up switch --}}
            @if(request()->routeIs('register') || request()->routeIs('register.complete'))
                <a href="{{ route('login') }}"
                   class="px-4 py-2 rounded-xl border border-slate-200 dark:border-white/[0.08]
                          bg-white dark:bg-white/[0.03] text-sm font-semibold text-slate-700 dark:text-slate-200
                          hover:bg-slate-50 dark:hover:bg-white/[0.06] transition-colors">
                    Sign In
                </a>
            @elseif(request()->routeIs('login'))
                <a href="{{ route('register') }}"
                   class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold
                          hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/30">
                    Sign Up
                </a>
            @endif
        </div>
    </header>

    <div class="relative flex-1 flex flex-col items-center justify-center gap-5 p-4 lg:p-8">

        {{-- Card --}}
        <div class="w-full max-w-5xl bg-white dark:bg-[#111827] rounded-3xl border border-slate-200/60 dark:border-white/[0.06]
                    shadow-2xl shadow-slate-300/40 dark:shadow-none overflow-hidden
                    flex flex-col lg:flex-row lg:min-h-[600px]">

            {{-- Left: form panel --}}
            <div class="w-full lg:w-1/2 flex flex-col justify-center px-6 py-10 sm:px-10 lg:px-12 lg:py-14">
                <div class="w-full max-w-sm mx-auto">
                    @yield('content')
                </div>
            </div>

            {{-- Right: image panel --}}
            <div class="hidden lg:block lg:w-1/2 relative">
                <img src="/images/auth-sidebar.png" alt=""
                     class="absolute inset-0 w-full h-full object-cover">
                {{-- Dark overlay so text stays readable over the light artwork --}}
                <div class="absolute inset-0 bg-slate-950/70"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/40 via-transparent to-slate-950/30"></div>

                <div class="relative h-full flex flex-col justify-center p-10 xl:p-14">
                    <p class="text-sm font-semibold uppercase tracking-wider text-blue-300 mb-4">Kloud101 Cloud Platform</p>
                    <h2 class="text-2xl xl:text-3xl font-bold text-white leading-tight mb-4">
                        Deploy, scale, and manage<br>your cloud infrastructure.
                    </h2>
                    <p class="text-slate-200/90 text-sm max-w-md">
                        One dashboard for your servers, billing, and support.
                    </p>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <p class="text-center text-xs text-slate-400 dark:text-slate-600">
            &copy; {{ date('Y') }} Kloud101. All rights reserved.
        </p>
    </div>

</body>
</html>
