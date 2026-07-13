@extends('layouts.admin')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
<div class="max-w-6xl mx-auto -mt-px">
<h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">Dashboard</h1>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
    Revenue, order, invoice, and ticket figures are all local to this app (Paystack/Flutterwave/NOWPayments payments).
</p>

{{-- Revenue --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
    <div class="card">
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Total Revenue</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">${{ number_format($stats['total_revenue'], 2) }}</p>
        <p class="text-xs text-slate-400 mt-1">All-time, completed payments (USD-equivalent)</p>
    </div>
    <div class="card">
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Revenue This Month</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">${{ number_format($stats['revenue_this_month'], 2) }}</p>
        <p class="text-xs text-slate-400 mt-1">{{ now()->format('F Y') }}</p>
    </div>
    <div class="card">
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Revenue Waiting Collection</p>
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">${{ number_format($billingStats['revenue_waiting'], 2) }}</p>
        <p class="text-xs text-slate-400 mt-1">Total of all unpaid invoices</p>
    </div>
</div>

{{-- Orders / services --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <div class="card">
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Active VPS</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['active_vps']) }}</p>
    </div>
    <div class="card">
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Active Domains</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['active_domains']) }}</p>
    </div>
    <div class="card">
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Pending Orders</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['pending_orders']) }}</p>
        <p class="text-xs text-slate-400 mt-1">Awaiting payment, all service types</p>
    </div>
    <div class="card">
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Open Support Tickets</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($billingStats['open_tickets']) }}</p>
    </div>
</div>

{{-- Invoices --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="card">
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Pending Invoices</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($billingStats['pending_invoices']) }}</p>
    </div>
    <div class="card">
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Cancelled Invoices</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($billingStats['cancelled_invoices']) }}</p>
    </div>
    <div class="card">
        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Paid Invoices</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($billingStats['paid_invoices']) }}</p>
    </div>
</div>

{{-- Revenue chart --}}
<div class="card" x-data="revenueChart(@js($revenueChart))" x-init="render()">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-900 dark:text-white">Daily Revenue — Last 30 Days</h2>
    </div>
    <canvas x-ref="canvas" height="90"></canvas>
</div>

</div>

@push('scripts')
<script>
    window.revenueChart = function (data) {
        return {
            chart: null,
            render() {
                const isDark = document.documentElement.classList.contains('dark');
                this.chart = new Chart(this.$refs.canvas, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Revenue (USD)',
                            data: data.data,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99,102,241,0.12)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { ticks: { color: isDark ? '#94a3b8' : '#64748b' }, grid: { display: false } },
                            y: { ticks: { color: isDark ? '#94a3b8' : '#64748b' }, grid: { color: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)' } },
                        },
                    },
                });
            },
        };
    };
</script>
@endpush
@endsection
