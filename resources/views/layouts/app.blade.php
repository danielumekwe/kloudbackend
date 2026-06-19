<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Kloud101</title>

    {{-- Anti-FOUC: apply dark class before page renders --}}
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
<body class="bg-slate-50 dark:bg-[#0a0f1e] text-slate-900 dark:text-slate-100 antialiased"
      x-data="appLayout()"
      x-init="init()">

    {{-- ----------------------------------------------------------------
         Toast Notification Container
    ----------------------------------------------------------------- --}}
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 w-80" x-data>
        <template x-for="item in $store.toast.items" :key="item.id">
            <div x-transition:enter="transform ease-out duration-300 transition"
                 x-transition:enter-start="translate-y-2 opacity-0 translate-x-4"
                 x-transition:enter-end="translate-y-0 opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-4"
                 class="flex items-start gap-3 px-4 py-3 rounded-xl shadow-xl text-sm text-white"
                 :class="{
                     'bg-green-600': item.type === 'success',
                     'bg-red-600':   item.type === 'error',
                     'bg-blue-600':  item.type === 'info',
                 }">
                {{-- Success icon --}}
                <svg x-show="item.type === 'success'" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{-- Error icon --}}
                <svg x-show="item.type === 'error'" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{-- Info icon --}}
                <svg x-show="item.type === 'info'" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <p x-text="item.message" class="leading-snug"></p>
                </div>
                <button @click="$store.toast.remove(item.id)" class="flex-shrink-0 opacity-70 hover:opacity-100 transition-opacity">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Session flash → toast (fired after Alpine initialises) --}}
    @if(session('success'))
    <script>
        document.addEventListener('alpine:initialized', () => {
            Alpine.store('toast').add(@json(session('success')), 'success');
        });
    </script>
    @endif
    @if(session('error'))
    <script>
        document.addEventListener('alpine:initialized', () => {
            Alpine.store('toast').add(@json(session('error')), 'error');
        });
    </script>
    @endif

    {{-- ----------------------------------------------------------------
         Layout Shell
    ----------------------------------------------------------------- --}}
    <div class="flex h-screen overflow-hidden">

        {{-- Mobile sidebar overlay --}}
        <div x-show="sidebarOpen && window.innerWidth < 1024"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm z-20 lg:hidden"></div>

        {{-- ============================================================
             SIDEBAR
        ============================================================= --}}
        <aside class="fixed lg:static inset-y-0 left-0 z-30 w-64 flex flex-col
                       bg-white dark:bg-[#0d1526]
                       border-r border-slate-200 dark:border-white/[0.06]
                       transition-transform duration-300 ease-in-out"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

            {{-- Logo --}}
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-200 dark:border-white/[0.06] flex-shrink-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center shadow-lg shadow-blue-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-base font-bold text-slate-900 dark:text-white leading-none">Kloud101</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Client Portal</div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">

                <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Main</p>

                <a href="{{ route('dashboard') }}"
                   class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('vps.index') }}"
                   class="nav-link {{ request()->routeIs('vps.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    My VPS
                </a>

                <a href="{{ route('qs.index') }}"
                   class="nav-link {{ request()->routeIs('qs.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                    My Quick Servers
                </a>

                <a href="{{ route('servers.index') }}"
                   class="nav-link {{ request()->routeIs('servers.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    My Services
                </a>

                <a href="{{ route('ssl.index') }}"
                   class="nav-link {{ request()->routeIs('ssl.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z"/>
                    </svg>
                    My SSL Certificates
                </a>

                <a href="{{ route('domains.index') }}"
                   class="nav-link {{ request()->routeIs('domains.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 000 18M12.5 3a17 17 0 010 18"/>
                    </svg>
                    My Domains
                </a>

                <p class="px-3 pt-4 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Order a Plan</p>

                <div x-data="{ openGroup: '{{
                    request()->routeIs('vps.catalog') ? 'vps'
                    : (request()->routeIs('qs.catalog') ? 'quickservers'
                    : (request()->is('coming-soon/dedicated-server') || request()->is('coming-soon/managed-dedicated-server') ? 'server'
                    : (request()->routeIs('ssl.catalog') ? 'ssl'

                    : (request()->routeIs('domains.search') || request()->routeIs('domains.catalog') ? 'domains' : ''))))
                }}' }" class="space-y-0.5">

                    {{-- VPS --}}
                    <button type="button" @click="openGroup = openGroup === 'vps' ? '' : 'vps'"
                            class="nav-link w-full justify-between {{ request()->routeIs('vps.catalog') ? 'active' : '' }}">
                        <span class="flex items-center gap-3">
                            <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                            VPS
                        </span>
                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400 transition-transform" :class="openGroup === 'vps' ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div x-show="openGroup === 'vps'" class="space-y-0.5">
                        <a href="{{ route('vps.catalog', 'linux-vps') }}"
                           class="nav-link pl-11 {{ request()->routeIs('vps.catalog') && request()->route('category') === 'linux-vps' ? 'active' : '' }}">
                            Linux VPS
                        </a>
                        <a href="{{ route('vps.catalog', 'managed-vps') }}"
                           class="nav-link pl-11 {{ request()->routeIs('vps.catalog') && request()->route('category') === 'managed-vps' ? 'active' : '' }}">
                            Managed VPS
                        </a>
                        <a href="{{ route('vps.catalog', 'storage-vps') }}"
                           class="nav-link pl-11 {{ request()->routeIs('vps.catalog') && request()->route('category') === 'storage-vps' ? 'active' : '' }}">
                            Storage VPS
                        </a>
                        <a href="{{ route('vps.catalog', 'windows-vps') }}"
                           class="nav-link pl-11 {{ request()->routeIs('vps.catalog') && request()->route('category') === 'windows-vps' ? 'active' : '' }}">
                            Windows VPS
                        </a>
                    </div>

                    {{-- Quick Servers --}}
                    <button type="button" @click="openGroup = openGroup === 'quickservers' ? '' : 'quickservers'"
                            class="nav-link w-full justify-between {{ request()->routeIs('qs.catalog') ? 'active' : '' }}">
                        <span class="flex items-center gap-3">
                            <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                            </svg>
                            Quick Servers
                        </span>
                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400 transition-transform" :class="openGroup === 'quickservers' ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div x-show="openGroup === 'quickservers'" class="space-y-0.5">
                        <a href="{{ route('qs.catalog') }}"
                           class="nav-link pl-11 {{ request()->routeIs('qs.catalog') ? 'active' : '' }}">
                            Quick Server
                        </a>
                    </div>

                    {{-- Server --}}
                    <button type="button" @click="openGroup = openGroup === 'server' ? '' : 'server'"
                            class="nav-link w-full justify-between {{ request()->is('coming-soon/dedicated-server') || request()->is('coming-soon/managed-dedicated-server') ? 'active' : '' }}">
                        <span class="flex items-center gap-3">
                            <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Server
                        </span>
                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400 transition-transform" :class="openGroup === 'server' ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div x-show="openGroup === 'server'" class="space-y-0.5">
                        <a href="{{ route('coming-soon', 'dedicated-server') }}" class="nav-link pl-11">Dedicated Server</a>
                        <a href="{{ route('coming-soon', 'managed-dedicated-server') }}" class="nav-link pl-11">Managed Dedicated Server</a>
                    </div>

                    {{-- SSL --}}
                    <button type="button" @click="openGroup = openGroup === 'ssl' ? '' : 'ssl'"
                            class="nav-link w-full justify-between {{ request()->routeIs('ssl.catalog') ? 'active' : '' }}">
                        <span class="flex items-center gap-3">
                            <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z"/>
                            </svg>
                            SSL
                        </span>
                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400 transition-transform" :class="openGroup === 'ssl' ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div x-show="openGroup === 'ssl'" class="space-y-0.5">
                        <a href="{{ route('ssl.catalog') }}"
                           class="nav-link pl-11 {{ request()->routeIs('ssl.catalog') ? 'active' : '' }}">
                            SSL Certificates
                        </a>
                    </div>

                    {{-- Domains --}}
                    <button type="button" @click="openGroup = openGroup === 'domains' ? '' : 'domains'"
                            class="nav-link w-full justify-between {{ request()->routeIs('domains.search') || request()->routeIs('domains.catalog') ? 'active' : '' }}">
                        <span class="flex items-center gap-3">
                            <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 000 18M12.5 3a17 17 0 010 18"/>
                            </svg>
                            Domains
                        </span>
                        <svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400 transition-transform" :class="openGroup === 'domains' ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div x-show="openGroup === 'domains'" class="space-y-0.5">
                        <a href="{{ route('domains.search') }}"
                           class="nav-link pl-11 {{ request()->routeIs('domains.search') || request()->routeIs('domains.catalog') ? 'active' : '' }}">
                            Register a Domain
                        </a>
                    </div>
                </div>

                <a href="{{ route('coming-soon', 'business-email-hosting') }}"
                   class="nav-link mt-1 {{ request()->is('coming-soon/business-email-hosting') ? 'active' : '' }}">
                    Business Email Hosting
                </a>
                <a href="{{ route('coming-soon', 'backup-and-security') }}"
                   class="nav-link {{ request()->is('coming-soon/backup-and-security') ? 'active' : '' }}">
                    Backup &amp; Security
                </a>

                <p class="px-3 pt-4 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Account</p>

                <a href="{{ route('billing.index') }}"
                   class="nav-link {{ request()->routeIs('billing.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Billing
                </a>

                <a href="{{ route('support.index') }}"
                   class="nav-link {{ request()->routeIs('support.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    Support
                </a>

                <a href="{{ route('profile.index') }}"
                   class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profile
                </a>
            </nav>

            {{-- User / Logout Footer --}}
            <div class="border-t border-slate-200 dark:border-white/[0.06] p-3 flex-shrink-0">
                <div class="flex items-center gap-3 px-2 py-1.5 mb-1">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ strtoupper(substr(session('firstName', 'U'), 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-900 dark:text-white truncate">
                            {{ session('firstName') }} {{ session('lastName') }}
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ session('email') }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400
                                   hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400
                                   transition-all duration-150">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>

        {{-- ============================================================
             MAIN CONTENT AREA
        ============================================================= --}}
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

            {{-- Top Bar --}}
            <header class="flex items-center justify-between px-6 py-3.5 bg-white dark:bg-[#0d1526]
                           border-b border-slate-200 dark:border-white/[0.06] flex-shrink-0">

                {{-- Left: Hamburger (mobile) + Breadcrumb --}}
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen"
                            class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-white/[0.06] hover:text-slate-700 dark:hover:text-slate-300 transition-colors lg:hidden">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="hidden sm:block text-sm text-slate-500 dark:text-slate-400">
                        @yield('breadcrumb', '')
                    </div>
                </div>

                {{-- Right: Dark mode toggle + User dropdown --}}
                <div class="flex items-center gap-2">

                    {{-- Dark mode toggle --}}
                    <button @click="toggleDark()"
                            class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/[0.06] transition-colors"
                            :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'">
                        {{-- Moon (shown in light mode) --}}
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        {{-- Sun (shown in dark mode) --}}
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>

                    {{-- Currency switcher --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300
                                       hover:bg-slate-100 dark:hover:bg-white/[0.06] transition-colors">
                            {{ session('currency', 'USD') }}
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open"
                             @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl
                                    border border-slate-200 dark:border-white/[0.08] py-1.5 z-10 origin-top-right">
                            @foreach($availableCurrencies as $cur)
                                <form method="POST" action="{{ route('currency.store') }}">
                                    @csrf
                                    <input type="hidden" name="currency" value="{{ $cur['code'] }}">
                                    <button type="submit"
                                            class="w-full flex items-center justify-between gap-2 px-4 py-2 text-sm
                                                   {{ session('currency') === $cur['code'] ? 'font-semibold text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300' }}
                                                   hover:bg-slate-50 dark:hover:bg-white/[0.05] transition-colors">
                                        <span>{{ $cur['code'] }}</span>
                                        @unless(in_array($cur['code'], config('services.whmcs.payable_currencies', [])))
                                            <span class="text-xs text-slate-400">not payable yet</span>
                                        @endunless
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    {{-- User dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-2 pl-1.5 pr-2.5 py-1.5 rounded-xl
                                       hover:bg-slate-100 dark:hover:bg-white/[0.06] transition-colors">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600
                                        flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr(session('firstName', 'U'), 0, 1)) }}
                            </div>
                            <span class="hidden sm:block text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ session('firstName') }}
                            </span>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open"
                             @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-52 bg-white dark:bg-slate-800 rounded-xl shadow-xl
                                    border border-slate-200 dark:border-white/[0.08] py-1.5 z-10 origin-top-right">
                            <div class="px-4 py-2.5 border-b border-slate-100 dark:border-white/[0.06]">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ session('firstName') }} {{ session('lastName') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ session('email') }}</p>
                            </div>
                            <a href="{{ route('profile.index') }}"
                               class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 dark:text-slate-300
                                      hover:bg-slate-50 dark:hover:bg-white/[0.05] transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Profile Settings
                            </a>
                            <hr class="my-1 border-slate-100 dark:border-white/[0.06]">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-red-600
                                               hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
