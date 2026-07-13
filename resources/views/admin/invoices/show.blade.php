@extends('layouts.admin')
@section('title', 'Invoice #' . $invoice->id)
@section('breadcrumb')
    <a href="{{ route('admin.invoices.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Invoices</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">#{{ $invoice->id }}</span>
@endsection

@section('content')

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

@php
    $badgeClass = match($invoice->status) {
        'paid'      => 'badge-active',
        'unpaid'    => 'badge-open',
        'cancelled' => 'badge-closed',
        default     => 'badge-open',
    };
@endphp

{{-- Header --}}
<div class="card mb-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Invoice #{{ $invoice->id }}</h1>
                <span class="badge {{ $badgeClass }}">{{ $invoice->status }}</span>
            </div>
            @if($invoice->client)
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                <a href="{{ route('admin.clients.show', $invoice->client) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                    {{ $invoice->client->firstname }} {{ $invoice->client->lastname }}
                </a>
                &middot; {{ $invoice->client->email }}
            </p>
            @endif
        </div>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">
            {{ $invoice->currency_code }} {{ number_format((float)$invoice->total, 2) }}
        </p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-slate-100 dark:border-white/[0.06] text-sm">
        <div>
            <p class="text-xs text-slate-400">Created</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $invoice->created_at->format('M j, Y g:ia') }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Paid at</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $invoice->paid_at?->format('M j, Y g:ia') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Payment method</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $invoice->payment_method ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Tax rate</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $invoice->tax_rate }}%</p>
        </div>
    </div>

    {{-- Actions --}}
    @if($invoice->status === 'unpaid')
    <div class="flex items-center gap-3 flex-wrap mt-5 pt-5 border-t border-slate-100 dark:border-white/[0.06]">
        <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice->id) }}">
            @csrf
            <button type="submit" class="btn btn-success text-sm">Mark as Paid (Manual)</button>
        </form>
        <form method="POST" action="{{ route('admin.invoices.cancel', $invoice->id) }}"
              x-data="{ confirm: false }"
              @submit.prevent="confirm ? $el.submit() : (confirm = true)">
            @csrf
            <button type="submit"
                    x-text="confirm ? 'Click again to confirm' : 'Cancel Invoice'"
                    class="btn btn-danger text-sm"></button>
        </form>
    </div>
    @endif
</div>

{{-- Line items --}}
<div class="card mb-6">
    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Line Items</h3>
    @if($invoice->items->isEmpty())
    <p class="text-sm text-slate-500 dark:text-slate-400">No line items.</p>
    @else
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td class="text-slate-700 dark:text-slate-300">{{ $item->description }}</td>
                    <td class="text-right font-medium text-slate-900 dark:text-white">
                        {{ $invoice->currency_code }} {{ number_format((float)$item->amount, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="text-sm text-slate-500 dark:text-slate-400">Subtotal</td>
                    <td class="text-right text-slate-700 dark:text-slate-300">{{ $invoice->currency_code }} {{ number_format((float)$invoice->subtotal, 2) }}</td>
                </tr>
                @if((float)$invoice->tax_amount > 0)
                <tr>
                    <td class="text-sm text-slate-500 dark:text-slate-400">Tax ({{ $invoice->tax_rate }}%)</td>
                    <td class="text-right text-slate-700 dark:text-slate-300">{{ $invoice->currency_code }} {{ number_format((float)$invoice->tax_amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td class="font-semibold text-slate-900 dark:text-white">Total</td>
                    <td class="text-right font-bold text-slate-900 dark:text-white">{{ $invoice->currency_code }} {{ number_format((float)$invoice->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

{{-- Payment transactions --}}
@if($invoice->paymentTransactions->isNotEmpty())
<div class="card">
    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Payment Transactions</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Gateway</th>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->paymentTransactions as $txn)
                <tr>
                    <td class="capitalize text-slate-700 dark:text-slate-300">{{ $txn->gateway }}</td>
                    <td><span class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $txn->gateway_reference }}</span></td>
                    <td class="font-medium text-slate-900 dark:text-white">{{ $txn->currency }} {{ number_format((float)$txn->amount, 2) }}</td>
                    <td>
                        <span class="badge {{ $txn->status === 'completed' ? 'badge-active' : 'badge-open' }}">
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
@endif

@endsection
