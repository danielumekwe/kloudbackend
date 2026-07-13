@extends('layouts.admin')
@section('title', 'Invoices')
@section('breadcrumb', 'Invoices')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Invoices</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">All client invoices across the platform.</p>
</div>

@if(session('success'))
<div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
    <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="card text-center">
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['unpaid'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Unpaid</p>
    </div>
    <div class="card text-center">
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['paid'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Paid</p>
    </div>
    <div class="card text-center">
        <p class="text-2xl font-bold text-slate-500 dark:text-slate-400">{{ $stats['cancelled'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Cancelled</p>
    </div>
    <div class="card text-center">
        <p class="text-2xl font-bold text-slate-900 dark:text-white">${{ number_format((float)$stats['revenue'], 2) }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total Revenue</p>
    </div>
</div>

{{-- Filter --}}
<div class="card mb-6">
    <form method="GET" action="{{ route('admin.invoices.index') }}" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-slate-500 dark:text-slate-400">Status:</label>
            <select name="status" class="form-input py-1.5 text-sm">
                <option value="all"       @selected($status === 'all')>All</option>
                <option value="unpaid"    @selected($status === 'unpaid')>Unpaid</option>
                <option value="paid"      @selected($status === 'paid')>Paid</option>
                <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary text-sm py-1.5">Filter</button>
        @if($status !== 'all')
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary text-sm py-1.5">Clear</a>
        @endif
    </form>
</div>

@if($invoices->isEmpty())
<div class="card text-center py-12">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No invoices found</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400">No invoices match the selected filter.</p>
</div>
@else

{{-- Mobile: stacked cards --}}
<div class="space-y-3 sm:hidden">
    @foreach($invoices as $invoice)
    @php
        $badgeClass = match($invoice->status) {
            'paid'      => 'badge-active',
            'unpaid'    => 'badge-open',
            'cancelled' => 'badge-closed',
            default     => 'badge-open',
        };
    @endphp
    <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="card block">
        <div class="flex items-start justify-between gap-3 mb-2">
            <div>
                <p class="font-medium text-slate-900 dark:text-white">#{{ $invoice->id }} &mdash; {{ $invoice->client?->firstname }} {{ $invoice->client?->lastname }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $invoice->client?->email }}</p>
            </div>
            <span class="badge {{ $badgeClass }} flex-shrink-0">{{ $invoice->status }}</span>
        </div>
        <p class="text-xs text-slate-400 dark:text-slate-500">
            {{ $invoice->currency_code }} {{ number_format((float)$invoice->total, 2) }}
            &middot; {{ $invoice->created_at->format('M j, Y') }}
        </p>
    </a>
    @endforeach
</div>

{{-- Desktop: table --}}
<div class="card !p-0 overflow-hidden hidden sm:block">
    <div class="table-container rounded-none border-0">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Total</th>
                    <th>Currency</th>
                    <th>Status</th>
                    <th>Method</th>
                    <th>Created</th>
                    <th>Paid At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                @php
                    $badgeClass = match($invoice->status) {
                        'paid'      => 'badge-active',
                        'unpaid'    => 'badge-open',
                        'cancelled' => 'badge-closed',
                        default     => 'badge-open',
                    };
                @endphp
                <tr>
                    <td><span class="font-mono text-xs font-medium text-slate-600 dark:text-slate-300">#{{ $invoice->id }}</span></td>
                    <td>
                        @if($invoice->client)
                        <a href="{{ route('admin.clients.show', $invoice->client) }}"
                           class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium">
                            {{ $invoice->client->firstname }} {{ $invoice->client->lastname }}
                        </a>
                        <div class="text-xs text-slate-400">{{ $invoice->client->email }}</div>
                        @else
                        <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="font-medium text-slate-900 dark:text-white">{{ number_format((float)$invoice->total, 2) }}</td>
                    <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $invoice->currency_code }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $invoice->status }}</span></td>
                    <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $invoice->payment_method ?? '—' }}</td>
                    <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $invoice->created_at->format('M j, Y') }}</td>
                    <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $invoice->paid_at?->format('M j, Y') ?? '—' }}</td>
                    <td>
                        <a href="{{ route('admin.invoices.show', $invoice->id) }}"
                           class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline whitespace-nowrap">
                            View →
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">
    {{ $invoices->links() }}
</div>
@endif
@endsection
