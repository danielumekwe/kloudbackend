@extends('layouts.admin')
@section('title', 'Transactions')
@section('breadcrumb', 'Transactions')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Transactions</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">All payment transactions recorded across gateways.</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="card text-center">
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total Transactions</p>
    </div>
    <div class="card text-center">
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Completed</p>
    </div>
    <div class="card text-center">
        <p class="text-2xl font-bold text-slate-900 dark:text-white">${{ number_format((float)$stats['volume'], 2) }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total Volume</p>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-6">
    <form method="GET" action="{{ route('admin.transactions.index') }}" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-slate-500 dark:text-slate-400">Gateway:</label>
            <select name="gateway" class="form-input py-1.5 text-sm">
                <option value="all" @selected($gateway === 'all')>All Gateways</option>
                @foreach($gateways as $g)
                <option value="{{ $g }}" @selected($gateway === $g)>{{ ucfirst($g) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-slate-500 dark:text-slate-400">Status:</label>
            <select name="status" class="form-input py-1.5 text-sm">
                <option value="all"       @selected($status === 'all')>All</option>
                <option value="completed" @selected($status === 'completed')>Completed</option>
                <option value="failed"    @selected($status === 'failed')>Failed</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary text-sm py-1.5">Filter</button>
        @if($gateway !== 'all' || $status !== 'all')
        <a href="{{ route('admin.transactions.index') }}" class="btn btn-secondary text-sm py-1.5">Clear</a>
        @endif
    </form>
</div>

@if($transactions->isEmpty())
<div class="card text-center py-12">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No transactions found</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400">No transactions match the selected filters.</p>
</div>
@else

{{-- Mobile: stacked cards --}}
<div class="space-y-3 sm:hidden">
    @foreach($transactions as $txn)
    <div class="card">
        <div class="flex items-start justify-between gap-3 mb-2">
            <div>
                <p class="font-medium text-slate-900 dark:text-white capitalize">{{ $txn->gateway }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ $txn->client?->firstname }} {{ $txn->client?->lastname }}
                </p>
            </div>
            <span class="badge {{ $txn->status === 'completed' ? 'badge-active' : 'badge-suspended' }} flex-shrink-0">
                {{ $txn->status }}
            </span>
        </div>
        <p class="text-xs text-slate-400 dark:text-slate-500">
            {{ $txn->currency }} {{ number_format((float)$txn->amount, 2) }}
            &middot; {{ $txn->created_at->format('M j, Y') }}
            @if($txn->invoice_id)
            &middot; <a href="{{ route('admin.invoices.show', $txn->invoice_id) }}" class="text-blue-500 hover:underline">Invoice #{{ $txn->invoice_id }}</a>
            @endif
        </p>
    </div>
    @endforeach
</div>

{{-- Desktop: table --}}
<div class="card !p-0 overflow-hidden hidden sm:block">
    <div class="table-container rounded-none border-0">
        <table>
            <thead>
                <tr>
                    <th>Gateway</th>
                    <th>Reference</th>
                    <th>Client</th>
                    <th>Invoice</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $txn)
                <tr>
                    <td class="capitalize font-medium text-slate-700 dark:text-slate-300">{{ $txn->gateway }}</td>
                    <td><span class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $txn->gateway_reference }}</span></td>
                    <td>
                        @if($txn->client)
                        <a href="{{ route('admin.clients.show', $txn->client) }}"
                           class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium">
                            {{ $txn->client->firstname }} {{ $txn->client->lastname }}
                        </a>
                        <div class="text-xs text-slate-400">{{ $txn->client->email }}</div>
                        @else
                        <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td>
                        @if($txn->invoice_id)
                        <a href="{{ route('admin.invoices.show', $txn->invoice_id) }}"
                           class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                            #{{ $txn->invoice_id }}
                        </a>
                        @else
                        <span class="text-slate-400 text-sm">—</span>
                        @endif
                    </td>
                    <td class="font-medium text-slate-900 dark:text-white">{{ $txn->currency }} {{ number_format((float)$txn->amount, 2) }}</td>
                    <td>
                        <span class="badge {{ $txn->status === 'completed' ? 'badge-active' : 'badge-suspended' }}">
                            {{ $txn->status }}
                        </span>
                    </td>
                    <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $txn->created_at->format('M j, Y g:ia') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">
    {{ $transactions->links() }}
</div>
@endif
@endsection
