@extends('layouts.app')
@section('title', 'Billing')
@section('breadcrumb', 'Billing')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Billing</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage invoices and payment history</p>
    </div>
    {{-- Credit balance --}}
    @if((float)$credit > 0)
    <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-sm font-semibold text-green-700 dark:text-green-400">Credit: {{ \App\Support\CurrencyConverter::format((float)$credit, session('currency', 'USD')) }}</span>
    </div>
    @endif
</div>

@if(empty($invoices))
    <div class="card text-center py-16">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No invoices found</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">Your invoice history will appear here.</p>
    </div>
@else
    {{-- Invoice summary stats --}}
    @php
        $unpaidCount  = collect($invoices)->where('status', 'Unpaid')->count();
        $unpaidTotal  = collect($invoices)->where('status', 'Unpaid')->sum(fn($i) => (float)($i['total'] ?? 0));
        $paidCount    = collect($invoices)->where('status', 'Paid')->count();
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="card flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $unpaidCount }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Unpaid Invoices</p>
            </div>
        </div>
        <div class="card flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-500/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                {{-- Sums totals across invoices, which may span multiple currencies if the
                     client switched currency between orders — shown under the account's
                     current currency symbol regardless; a known simplification. --}}
                <p class="text-xl font-bold text-slate-900 dark:text-white">{{ \App\Support\CurrencyConverter::format($unpaidTotal, session('currency', 'USD')) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Amount Outstanding</p>
            </div>
        </div>
        <div class="card flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-500/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $paidCount }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Paid Invoices</p>
            </div>
        </div>
    </div>

    {{-- Invoices table --}}
    <div class="card !p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-white/[0.06]">
            <h3 class="font-semibold text-slate-900 dark:text-white">All Invoices</h3>
        </div>
        <div class="table-container rounded-none border-0">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                    @php $st = strtolower($invoice['status'] ?? ''); @endphp
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-white">#{{ $invoice['id'] }}</td>
                        <td>{{ $invoice['date'] ?? '—' }}</td>
                        <td class="{{ $st === 'unpaid' ? 'text-amber-600 dark:text-amber-400 font-medium' : '' }}">
                            {{ $invoice['duedate'] ?? '—' }}
                        </td>
                        <td class="font-semibold text-slate-900 dark:text-white">
                            {{ $invoice['currencycode'] ?? '' }} {{ number_format((float)($invoice['total'] ?? 0), 2) }}
                        </td>
                        <td>
                            <span class="badge badge-{{ $st === 'paid' ? 'paid' : ($st === 'unpaid' ? 'unpaid' : 'cancelled') }}">
                                {{ $invoice['status'] ?? '' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('billing.show', $invoice['id']) }}"
                               class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline whitespace-nowrap">
                                View {{ $st === 'unpaid' ? '& Pay' : '' }} →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
