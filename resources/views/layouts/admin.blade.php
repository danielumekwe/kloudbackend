<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Kloud101</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" href="/images/icon-192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

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
                <svg x-show="item.type === 'success'" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="item.type === 'error'" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
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

    {{-- Session flash → toast --}}
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
        <div x-show="sidebarOpen && !isDesktop"
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
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-700 flex items-center justify-center shadow-lg shadow-indigo-500/30 p-1.5">
                    <img src="/images/kloud101-icon.png" alt="Kloud101" class="w-full h-full object-contain">
                </div>
                <div>
                    <div class="text-base font-bold leading-none">
                        <span class="text-slate-900 dark:text-white">kloud</span><span class="text-blue-600 dark:text-blue-400">101</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Admin</div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">

                <a href="{{ route('admin.dashboard') }}"
                   class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                    </svg>
                    Dashboard
                </a>

                @php($role = \App\Enums\AdminRole::tryFrom(session('adminRole')))

                {{-- SERVICES --}}
                <p class="px-3 pt-4 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Services</p>

                <a href="{{ route('admin.services.index') }}"
                   class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    Services
                </a>

                <a href="{{ route('admin.orders.index') }}"
                   class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Orders
                </a>

                @if($role?->canManagePricing())
                <a href="{{ route('admin.pricing') }}"
                   class="nav-link {{ request()->routeIs('admin.pricing') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 7h6m0 10v-3m-3 3v-6m-3 6v-9m-2 9h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v9a2 2 0 002 2z"/>
                    </svg>
                    Pricing
                </a>

                <a href="{{ route('admin.products.index', 'vps') }}"
                   class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Products
                </a>
                @endif

                {{-- BILLING --}}
                @if($role?->canManagePricing())
                <p class="px-3 pt-4 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Billing</p>

                <a href="{{ route('admin.invoices.index') }}"
                   class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                    Invoices
                </a>

                <a href="{{ route('admin.transactions.index') }}"
                   class="nav-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Transactions
                </a>

                <a href="{{ route('admin.billing-settings') }}"
                   class="nav-link {{ request()->routeIs('admin.billing-settings') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Billing Settings
                </a>
                @endif

                {{-- CLIENTS & SUPPORT --}}
                @if($role?->canManageTickets())
                <p class="px-3 pt-4 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Clients & Support</p>

                <a href="{{ route('admin.clients.index') }}"
                   class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 0a4 4 0 100-8"/>
                    </svg>
                    Clients
                </a>

                <a href="{{ route('admin.tickets.index') }}"
                   class="nav-link {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    Support Tickets
                </a>
                @endif

                {{-- COMMUNICATIONS --}}
                <p class="px-3 pt-4 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Communications</p>

                <a href="{{ route('admin.communications.newsletter') }}"
                   class="nav-link {{ request()->routeIs('admin.communications.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Newsletter
                </a>

                {{-- SETTINGS --}}
                <p class="px-3 pt-4 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Settings</p>

                <a href="{{ route('admin.security') }}"
                   class="nav-link {{ request()->routeIs('admin.security*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z"/>
                    </svg>
                    Security
                </a>

                @if($role?->canManageAdmins())
                <a href="{{ route('admin.users.index') }}"
                   class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Admin Users
                </a>
                @endif

            </nav>

            {{-- Footer --}}
            <div class="border-t border-slate-200 dark:border-white/[0.06] p-3 flex-shrink-0">
                <div class="flex items-center gap-3 px-2 py-1.5 mb-1">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        A
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ session('adminEmail', 'Administrator') }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $role?->label() ?? 'Kloud101 Admin' }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400
                                   hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400
                                   transition-all duration-150">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Log Out
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

                <div class="flex items-center gap-2">
                    <button @click="toggleDark()"
                            class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/[0.06] transition-colors"
                            :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>

                    <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z"/>
                        </svg>
                        Admin Mode
                    </span>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-6">
                <div class="max-w-5xl mx-auto">
                    @if(session('success'))
                        <div class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
