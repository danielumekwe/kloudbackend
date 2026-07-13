@extends('layouts.admin')
@section('title', 'Orders')
@section('breadcrumb', 'Orders')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Orders</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">All service orders across VPS, Quick Server, SSL, and Domains.</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="card text-center">
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total Orders</p>
    </div>
    <div class="card text-center">
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['provisioned'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Provisioned</p>
    </div>
    <div class="card text-center">
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pending</p>
    </div>
    <div class="card text-center">
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['failed'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Failed</p>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-6">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-slate-500 dark:text-slate-400">Type:</label>
            <select name="type" class="form-input py-1.5 text-sm">
                <option value="all"    @selected($type === 'all')>All Types</option>
                <option value="vps"    @selected($type === 'vps')>VPS</option>
                <option value="qs"     @selected($type === 'qs')>Quick Server</option>
                <option value="ssl"    @selected($type === 'ssl')>SSL</option>
                <option value="domain" @selected($type === 'domain')>Domain</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-slate-500 dark:text-slate-400">Status:</label>
            <select name="status" class="form-input py-1.5 text-sm">
                <option value="all"         @selected($status === 'all')>All Statuses</option>
                <option value="pending"      @selected($status === 'pending')>Pending</option>
                <option value="provisioning" @selected($status === 'provisioning')>Provisioning</option>
                <option value="provisioned"  @selected($status === 'provisioned')>Provisioned</option>
                <option value="failed"       @selected($status === 'failed')>Failed</option>
                <option value="cancelled"    @selected($status === 'cancelled')>Cancelled</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary text-sm py-1.5">Filter</button>
        @if($type !== 'all' || $status !== 'all')
        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary text-sm py-1.5">Clear</a>
        @endif
    </form>
</div>

@if($orders->isEmpty())
<div class="card text-center py-12">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No orders found</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400">No orders match the selected filters.</p>
</div>
@else

{{-- Mobile: stacked cards --}}
<div class="space-y-3 sm:hidden">
    @foreach($orders as $order)
    @php
        $badgeClass = match($order['status']) {
            'provisioned'  => 'badge-active',
            'provisioning' => 'badge-answered',
            'pending'      => 'badge-open',
            'failed'       => 'badge-suspended',
            'cancelled'    => 'badge-closed',
            default        => 'badge-open',
        };
    @endphp
    <div class="card">
        <div class="flex items-start justify-between gap-3 mb-2">
            <div>
                <p class="font-medium text-slate-900 dark:text-white">{{ $order['description'] }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ $order['client']?->firstname }} {{ $order['client']?->lastname }}
                    &middot; {{ $order['client']?->email }}
                </p>
            </div>
            <span class="badge {{ $badgeClass }} flex-shrink-0">{{ $order['status'] }}</span>
        </div>
        <p class="text-xs text-slate-400 dark:text-slate-500">
            {{ $order['type'] }} &middot; ${{ number_format((float)$order['price'], 2) }}
            &middot; {{ $order['created_at']?->format('M j, Y') }}
            @if($order['invoice_id'])
            &middot; <a href="{{ route('admin.invoices.show', $order['invoice_id']) }}" class="text-blue-500 hover:underline">Invoice #{{ $order['invoice_id'] }}</a>
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
                    <th>Type</th>
                    <th>Description</th>
                    <th>Client</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Invoice</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                @php
                    $badgeClass = match($order['status']) {
                        'provisioned'  => 'badge-active',
                        'provisioning' => 'badge-answered',
                        'pending'      => 'badge-open',
                        'failed'       => 'badge-suspended',
                        'cancelled'    => 'badge-closed',
                        default        => 'badge-open',
                    };
                @endphp
                <tr>
                    <td>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-white/[0.08] text-slate-600 dark:text-slate-300">
                            {{ $order['type'] }}
                        </span>
                    </td>
                    <td class="font-medium text-slate-900 dark:text-white">{{ $order['description'] }}</td>
                    <td>
                        @if($order['client'])
                        <a href="{{ route('admin.clients.show', $order['client']) }}"
                           class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                            {{ $order['client']->firstname }} {{ $order['client']->lastname }}
                        </a>
                        <div class="text-xs text-slate-400">{{ $order['client']->email }}</div>
                        @else
                        <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="text-slate-700 dark:text-slate-300">${{ number_format((float)$order['price'], 2) }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $order['status'] }}</span></td>
                    <td>
                        @if($order['invoice_id'])
                        <a href="{{ route('admin.invoices.show', $order['invoice_id']) }}"
                           class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                            #{{ $order['invoice_id'] }}
                        </a>
                        @else
                        <span class="text-slate-400 text-sm">—</span>
                        @endif
                    </td>
                    <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $order['created_at']?->format('M j, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
