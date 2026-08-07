@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')

@unless($client->isEmailVerified())
<div class="flex items-center justify-between gap-4 flex-wrap mb-6 px-4 py-3 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20">
    <p class="text-sm text-amber-800 dark:text-amber-400">
        <strong>Verify your email address</strong> — we sent a link to {{ $client->email }} when you signed up.
    </p>
    <form method="POST" action="{{ route('verification.resend') }}">
        @csrf
        <button type="submit" class="text-sm font-medium text-amber-800 dark:text-amber-400 hover:underline whitespace-nowrap">
            Resend email
        </button>
    </form>
</div>
@endunless

{{-- Welcome banner --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 px-6 py-7 mb-6 shadow-lg shadow-blue-900/20">
    <div class="absolute -top-10 -right-10 w-56 h-56 rounded-full bg-white/10 blur-2xl"></div>
    <div class="absolute -bottom-16 -left-10 w-48 h-48 rounded-full bg-white/10 blur-2xl"></div>
    <div class="relative flex items-center justify-between flex-wrap gap-4">
        <div>
            <p class="text-blue-100 text-sm font-medium">
                Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }} 👋
            </p>
            <h1 class="text-2xl font-bold text-white mt-1">{{ session('firstName') }} {{ session('lastName') }}</h1>
            <p class="mt-1.5 text-sm text-blue-100/90">Here's what's happening with your account today.</p>
        </div>
        <a href="{{ route('vps.catalog', 'linux-vps') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-blue-700 font-semibold text-sm
                  hover:bg-blue-50 shadow-lg shadow-black/10 transition-all duration-150">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Order a Server
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ================================================================
         LEFT COLUMN (main)
    ================================================================= --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

            <a href="#services-grid" class="card flex items-center gap-4 hover:shadow-md dark:hover:bg-white/[0.03] transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $totalActiveServices }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Active Servers</p>
                </div>
            </a>

            <a href="{{ route('billing.index') }}" class="card flex items-center gap-4 hover:shadow-md dark:hover:bg-white/[0.03] transition-shadow">
                <div class="w-12 h-12 rounded-xl {{ $unpaidInvoices > 0 ? 'bg-amber-50 dark:bg-amber-500/10' : 'bg-green-50 dark:bg-green-500/10' }} flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 {{ $unpaidInvoices > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $unpaidInvoices }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Unpaid Invoices</p>
                </div>
            </a>

            <a href="{{ route('support.index') }}" class="card flex items-center gap-4 hover:shadow-md dark:hover:bg-white/[0.03] transition-shadow">
                <div class="w-12 h-12 rounded-xl {{ $openTickets > 0 ? 'bg-purple-50 dark:bg-purple-500/10' : 'bg-slate-50 dark:bg-slate-700/40' }} flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 {{ $openTickets > 0 ? 'text-purple-600 dark:text-purple-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $openTickets }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Open Tickets</p>
                </div>
            </a>
        </div>

        {{-- Services --}}
        <div id="services-grid">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Services</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                @php
                    $serviceCards = [
                        [
                            'label' => 'VPS', 'count' => $vpsActive, 'iconColor' => 'text-blue-500',
                            'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
                            'view' => ['route' => 'vps.index'], 'order' => ['route' => 'vps.catalog', 'param' => 'linux-vps'],
                        ],
                        [
                            'label' => 'Quick Servers', 'count' => $qsActive, 'iconColor' => 'text-blue-500',
                            'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2',
                            'view' => ['route' => 'qs.index'], 'order' => ['route' => 'qs.catalog'],
                        ],
                        [
                            'label' => 'SSL Certificates', 'count' => $sslActive, 'iconColor' => 'text-purple-500',
                            'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z',
                            'view' => ['route' => 'ssl.index'], 'order' => ['route' => 'ssl.catalog'],
                        ],
                        [
                            'label' => 'Domains', 'count' => $domainsActive, 'iconColor' => 'text-purple-500',
                            'icon' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 000 18M12.5 3a17 17 0 010 18',
                            'view' => ['route' => 'domains.index'], 'order' => ['route' => 'domains.search'],
                        ],
                        [
                            'label' => 'Dedicated Servers', 'count' => $dedicatedActive, 'iconColor' => 'text-blue-500',
                            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                            'view' => ['route' => 'dedicated.index'], 'order' => ['route' => 'dedicated.catalog'],
                        ],
                        [
                            'label' => 'My Services', 'count' => $activeServices, 'iconColor' => 'text-slate-500',
                            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                            'view' => ['route' => 'servers.index'], 'order' => ['route' => 'servers.order'],
                        ],
                        [
                            'label' => 'Business Email Hosting', 'count' => null, 'iconColor' => 'text-amber-500',
                            'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                            'view' => null, 'order' => ['route' => 'coming-soon', 'param' => 'business-email-hosting'],
                        ],
                        [
                            'label' => 'Backup & Security', 'count' => null, 'iconColor' => 'text-amber-500',
                            'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                            'view' => null, 'order' => ['route' => 'coming-soon', 'param' => 'backup-and-security'],
                        ],
                    ];
                @endphp

                @foreach($serviceCards as $svc)
                <div class="card !p-0 overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-white/[0.06]">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4.5 h-4.5 {{ $svc['iconColor'] }} flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $svc['icon'] }}"/>
                            </svg>
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $svc['label'] }}</h4>
                        </div>
                        @if(is_null($svc['count']))
                            <span class="text-[11px] font-medium px-2 py-1 rounded-full bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400">
                                Coming Soon
                            </span>
                        @else
                            <span class="text-[11px] font-medium px-2 py-1 rounded-full {{ $svc['count'] > 0 ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400' }}">
                                {{ $svc['count'] }} Active
                            </span>
                        @endif
                    </div>

                    <div class="flex-1 flex items-center justify-center px-5 py-6 text-center">
                        @if(is_null($svc['count']))
                            <p class="text-sm text-slate-400 dark:text-slate-500">Not available yet</p>
                        @elseif($svc['count'] > 0)
                            <a href="{{ route($svc['view']['route']) }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                View {{ $svc['count'] }} active {{ \Illuminate\Support\Str::plural('service', $svc['count']) }}
                            </a>
                        @else
                            <p class="text-sm text-slate-400 dark:text-slate-500">No active services</p>
                        @endif
                    </div>

                    <a href="{{ isset($svc['order']['param']) ? route($svc['order']['route'], $svc['order']['param']) : route($svc['order']['route']) }}"
                       class="flex items-center justify-center gap-1.5 px-5 py-3 text-sm font-semibold text-blue-600 dark:text-blue-400
                              border-t border-slate-100 dark:border-white/[0.06] hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Order New
                    </a>
                </div>
                @endforeach

            </div>
        </div>

        {{-- Quick actions --}}
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                <a href="{{ route('vps.catalog', 'linux-vps') }}"
                   class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-white/[0.08]
                          hover:border-blue-300 dark:hover:border-blue-500/30 hover:bg-blue-50/50 dark:hover:bg-blue-500/5 transition-all duration-150 group">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Order a Server</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Spin up a new VPS in minutes</p>
                    </div>
                </a>

                <a href="{{ route('support.create') }}"
                   class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-white/[0.08]
                          hover:border-purple-300 dark:hover:border-purple-500/30 hover:bg-purple-50/50 dark:hover:bg-purple-500/5 transition-all duration-150 group">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Open a Ticket</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Get help from our support team</p>
                    </div>
                </a>

                <a href="{{ route('billing.index') }}"
                   class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-white/[0.08]
                          hover:border-amber-300 dark:hover:border-amber-500/30 hover:bg-amber-50/50 dark:hover:bg-amber-500/5 transition-all duration-150 group">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">View Invoices</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Check your billing history</p>
                    </div>
                </a>

                <a href="{{ route('profile.index') }}"
                   class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-white/[0.08]
                          hover:border-green-300 dark:hover:border-green-500/30 hover:bg-green-50/50 dark:hover:bg-green-500/5 transition-all duration-150 group">
                    <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-500/10 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Manage Profile</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Update your account details</p>
                    </div>
                </a>

            </div>
        </div>

        {{-- Recent Invoices --}}
        <div class="card !p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-white/[0.06]">
                <h3 class="font-semibold text-slate-900 dark:text-white">Recent Invoices</h3>
                <a href="{{ route('billing.index') }}" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">View all</a>
            </div>
            @if($recentInvoices->isEmpty())
                <div class="px-5 py-10 text-center">
                    <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-slate-400 dark:text-slate-500">No invoices yet.</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                    @foreach($recentInvoices as $invoice)
                    <li>
                        <a href="{{ route('billing.show', $invoice->id) }}"
                           class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">#{{ $invoice->id }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $invoice->created_at->format('M j, Y') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ $invoice->currency_code }} {{ number_format((float) $invoice->total, 2) }}
                                </span>
                                <span class="badge badge-{{ $invoice->status }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </div>
                        </a>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Recent Tickets --}}
        <div class="card !p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-white/[0.06]">
                <h3 class="font-semibold text-slate-900 dark:text-white">Recent Support Tickets</h3>
                <a href="{{ route('support.index') }}" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">View all</a>
            </div>
            @if(empty($recentTickets))
                <div class="px-5 py-10 text-center">
                    <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="text-sm text-slate-400 dark:text-slate-500">No tickets yet.</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                    @foreach($recentTickets as $ticket)
                    <li>
                        <a href="{{ route('support.show', $ticket['id']) }}"
                           class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $ticket['subject'] ?? '(No subject)' }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $ticket['department'] ?? '' }} &middot; {{ $ticket['date'] ?? '' }}</p>
                            </div>
                            @php $ts = strtolower($ticket['status'] ?? ''); @endphp
                            <span class="ml-3 flex-shrink-0 badge badge-{{ $ts === 'open' ? 'open' : ($ts === 'answered' ? 'answered' : 'closed') }}">
                                {{ $ticket['status'] ?? '' }}
                            </span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>

    {{-- ================================================================
         RIGHT COLUMN (sidebar widgets)
    ================================================================= --}}
    <div class="space-y-6">

        {{-- Account ID card --}}
        <div class="card bg-slate-900 dark:bg-[#0d1526] border-slate-800 dark:border-white/[0.08]">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <p class="text-sm font-semibold text-white">Your Account ID</p>
            </div>
            <p class="text-xs text-slate-400 mb-4">Reference this ID when contacting support for faster assistance.</p>
            <div class="flex items-center gap-2">
                <div class="flex-1 px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-center">
                    <span class="text-xl font-mono font-bold text-white tracking-widest">{{ $client->accountCode() }}</span>
                </div>
                <button type="button"
                        x-data
                        @click="navigator.clipboard.writeText('{{ $client->accountCode() }}'); $store.toast.add('Account ID copied to clipboard', 'success')"
                        class="w-11 h-11 flex-shrink-0 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center
                               text-slate-300 hover:text-white hover:bg-white/10 transition-colors">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="card !p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-white/[0.06]">
                <h3 class="font-semibold text-slate-900 dark:text-white">Recent Activity</h3>
                <a href="{{ route('profile.index') }}#login-activity" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">View all</a>
            </div>
            @if($recentActivity->isEmpty())
                <div class="px-5 py-8 text-center">
                    <svg class="w-9 h-9 mx-auto text-slate-300 dark:text-slate-600 mb-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    <p class="text-sm text-slate-400 dark:text-slate-500">No activity yet.</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                    @foreach($recentActivity as $activity)
                    <li class="flex items-start gap-3 px-5 py-3">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">Account Login</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">IP: {{ $activity->ip_address }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Server status --}}
        @if($serverStatus['online'] + $serverStatus['offline'] > 0)
        <div class="card">
            <p class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Server Status</p>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ $serverStatus['online'] }} Online</span>
                </div>
                @if($serverStatus['offline'] > 0)
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ $serverStatus['offline'] }} Offline</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Wallet / Credit balance --}}
        <div class="card">
            <p class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Wallet</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Available account credit</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white mb-4">
                {{ \App\Support\CurrencyConverter::format((float)($client['credit_balance'] ?? 0), 'NGN') }}
            </p>
            <a href="{{ route('billing.index') }}" class="btn btn-secondary w-full text-sm justify-center">
                View Billing
            </a>
        </div>

        {{-- Need help card --}}
        <div class="card bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-500/10 dark:to-indigo-500/10
                    border-blue-100 dark:border-blue-500/20">
            <div class="w-10 h-10 rounded-xl bg-white dark:bg-white/10 flex items-center justify-center mb-3 shadow-sm">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Need help?</p>
            <p class="text-xs text-slate-600 dark:text-slate-400 mb-4">Our support team typically responds within 1-4 hours.</p>
            <a href="{{ route('support.create') }}" class="btn btn-primary w-full text-sm justify-center">
                Contact Support
            </a>
        </div>

    </div>
</div>
@endsection
