@extends('layouts.app')
@section('title', 'Dedicated Server Details')
@section('breadcrumb')
    <a href="{{ route('dedicated.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My Dedicated Servers</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">{{ $order->config['hostname'] ?? 'Server #' . $order->id }}</span>
@endsection

@section('content')
@php
    $badgeClass = match($order->status) {
        'provisioned'          => 'badge-active',
        'paid', 'provisioning' => 'badge-pending',
        'pending_payment'      => 'badge-unpaid',
        'failed'                => 'badge-suspended',
        default                  => 'badge-cancelled',
    };
    $statusLabel = match($order->status) {
        'provisioned'          => $live['serviceInfo']['server_status'] ?? 'Active',
        'paid', 'provisioning' => 'Provisioning…',
        'pending_payment'      => 'Awaiting Payment',
        'failed'                => 'Failed',
        default                  => ucfirst($order->status),
    };
    $listing = $order->config['listing'] ?? [];
    $cpuLabel = is_array($listing['cpu'] ?? null) ? ($listing['cpu'][0] ?? 'Dedicated Server') : 'Dedicated Server';
@endphp

<div class="flex items-start justify-between flex-wrap gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $order->config['hostname'] ?? 'Dedicated Server #' . $order->id }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ $cpuLabel }}</p>
    </div>
    <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
</div>

<div x-data="dedicatedManage({{ $order->id }})" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Hardware</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-4 text-sm">
                <div>
                    <span class="text-slate-400 dark:text-slate-500 text-xs">Memory</span>
                    <p class="font-medium text-slate-900 dark:text-white mt-0.5">{{ $listing['memory'] ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500 text-xs">Disk</span>
                    <p class="font-medium text-slate-900 dark:text-white mt-0.5">
                        {{ is_array($listing['disk'] ?? null) ? implode(', ', $listing['disk']) : ($listing['disk'] ?? '—') }}
                    </p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500 text-xs">Bandwidth</span>
                    <p class="font-medium text-slate-900 dark:text-white mt-0.5">{{ $listing['bandwidth'] ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500 text-xs">IPs</span>
                    <p class="font-medium text-slate-900 dark:text-white mt-0.5">{{ $listing['ips'] ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500 text-xs">Location</span>
                    <p class="font-medium text-slate-900 dark:text-white mt-0.5">{{ $listing['location'] ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500 text-xs">Price</span>
                    <p class="font-medium text-slate-900 dark:text-white mt-0.5">${{ number_format($order->price, 2) }}/mo</p>
                </div>
            </div>
        </div>

        @if($order->status === 'failed' && $order->failure_reason)
        <div class="card bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20">
            <h3 class="font-semibold text-red-700 dark:text-red-400 mb-1">Provisioning failed</h3>
            <p class="text-sm text-red-600 dark:text-red-400">{{ $order->failure_reason }}</p>
        </div>
        @endif

        @if($order->status === 'pending_payment')
        <div class="card bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20">
            <h3 class="font-semibold text-amber-800 dark:text-amber-400 mb-1">Awaiting payment</h3>
            <p class="text-sm text-amber-700 dark:text-amber-400 mb-3">Your server will be provisioned automatically once the invoice is paid.</p>
            <a href="{{ route('billing.show', $order->invoice_id) }}" class="btn btn-primary text-sm">View Invoice</a>
        </div>
        @endif

        @if($order->status === 'provisioned')
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Cancel Server</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                This deprovisions the server and stops recurring billing at the end of the current cycle. Any data on
                the server is not preserved.
            </p>
            <div x-show="message" class="mb-3 text-sm" :class="success ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="message"></div>
            <button @click="confirmCancel()" :disabled="busy" class="btn btn-danger">Cancel Server</button>
        </div>
        @endif

    </div>

    <div class="space-y-6">
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Order</h3>
            <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                <div class="flex justify-between"><span>Order #</span><span class="font-medium text-slate-900 dark:text-white">{{ $order->id }}</span></div>
                <div class="flex justify-between"><span>Ordered</span><span class="font-medium text-slate-900 dark:text-white">{{ $order->created_at->format('M j, Y') }}</span></div>
                <div class="flex justify-between"><span>Billing cycle</span><span class="font-medium text-slate-900 dark:text-white">Monthly</span></div>
                @if($order->invoice_id)
                <div class="flex justify-between">
                    <span>Invoice</span>
                    <a href="{{ route('billing.show', $order->invoice_id) }}" class="font-medium text-blue-600 dark:text-blue-400 hover:underline">#{{ $order->invoice_id }}</a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dedicatedManage(orderId) {
    return {
        busy: false,
        message: '',
        success: false,

        async confirmCancel() {
            if (!confirm('This will cancel and deprovision this server at the end of the current billing cycle. Continue?')) return;
            this.busy = true;
            this.message = '';
            try {
                const res = await fetch(`/dedicated/${orderId}/action`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ command: 'cancel' }),
                });
                const data = await res.json();
                this.success = data.success;
                this.message = data.message;
                if (data.success) window.location.href = '{{ route('dedicated.index') }}';
            } catch (e) {
                this.success = false;
                this.message = 'Could not reach the server. Please try again.';
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endpush
