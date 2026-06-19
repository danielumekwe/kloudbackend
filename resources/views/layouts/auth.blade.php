<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Welcome') — Kloud101</title>

    {{-- Anti-FOUC --}}
    <script>
        (function () {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 dark:bg-[#0a0f1e] text-slate-900 dark:text-slate-100 antialiased min-h-screen"
      x-data="{ darkMode: document.documentElement.classList.contains('dark') }">

    {{-- Dark mode toggle (top-right) --}}
    <div class="fixed top-4 right-4 z-50">
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
    </div>

    {{-- Decorative background --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-blue-600/10 dark:bg-blue-500/10 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full bg-indigo-600/10 dark:bg-indigo-500/10 blur-3xl"></div>
    </div>

    {{-- Centered card --}}
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">

            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl
                            bg-gradient-to-br from-blue-500 to-blue-700 shadow-xl shadow-blue-500/30 mb-4">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Kloud101</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Client Portal</p>
            </div>

            {{-- Card --}}
            <div class="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200 dark:border-white/[0.06]
                        shadow-xl shadow-slate-200/60 dark:shadow-none p-8">
                @yield('content')
            </div>

            {{-- Footer --}}
            <p class="text-center text-xs text-slate-400 dark:text-slate-600 mt-6">
                &copy; {{ date('Y') }} Kloud101. All rights reserved.
            </p>
        </div>
    </div>

</body>
</html>
