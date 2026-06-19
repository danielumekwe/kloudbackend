@extends('layouts.app')
@section('title', $order->config['hostname'] ?? 'Certificate #' . $order->id)
@section('breadcrumb')
    <a href="{{ route('ssl.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My SSL Certificates</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">{{ $order->config['hostname'] ?? 'Certificate #' . $order->id }}</span>
@endsection

@section('content')
<div x-data="sslManage({{ $order->id }})">

    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $order->config['hostname'] ?? 'Certificate #' . $order->id }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">SSL Certificate</p>
        </div>
        <span class="badge {{ $order->status === 'provisioned' ? 'badge-active' : ($order->status === 'failed' ? 'badge-suspended' : 'badge-pending') }}">
            {{ $order->status === 'provisioned' ? ($live['status'] ?? 'Active') : str_replace('_', ' ', $order->status) }}
        </span>
    </div>

    @if($order->status === 'pending_payment')
        <div class="card mb-6 flex items-start gap-3 bg-yellow-50 dark:bg-yellow-500/10 border-yellow-200 dark:border-yellow-500/20">
            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">Awaiting payment</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">Your certificate will be issued automatically as soon as this invoice is paid.</p>
                <a href="{{ route('billing.show', $order->whmcs_invoice_id) }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline mt-2 inline-block">View invoice →</a>
            </div>
        </div>
    @elseif($order->status === 'failed')
        <div class="card mb-6 flex items-start gap-3 bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">Issuance failed</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">{{ $order->failure_reason ?? 'Please contact support.' }}</p>
                <a href="{{ route('support.create') }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline mt-2 inline-block">Contact support →</a>
            </div>
        </div>
    @endif

    @if($order->status === 'provisioned')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            <div x-show="message" class="text-sm p-3 rounded-lg" :class="success ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400'" x-text="message"></div>

            <div class="card">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Certificate Actions</h3>
                <div class="flex flex-wrap gap-3">
                    <button @click="run('resendwelcome')" :disabled="busy" class="btn btn-secondary">Resend Welcome Email</button>
                </div>
            </div>

            <div class="card">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Cancel Certificate</h3>
                <p class="text-sm text-red-600 dark:text-red-400 mb-4">This stops renewals at the end of the current billing cycle.</p>
                <button @click="confirmCancel()" :disabled="busy" class="btn btn-danger">Cancel Certificate</button>
            </div>
        </div>

        <div class="card h-fit">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Details</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Hostname</span><span class="font-medium text-slate-900 dark:text-white">{{ $order->config['hostname'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Approver Email</span><span class="font-medium text-slate-900 dark:text-white">{{ $order->config['approver_email'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Status</span><span class="font-medium text-slate-900 dark:text-white">{{ $live['status'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Expires</span><span class="font-medium text-slate-900 dark:text-white">{{ $live['expiry'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Price</span><span class="font-medium text-slate-900 dark:text-white">${{ number_format($order->price, 2) }}</span></div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function sslManage(orderId) {
    return {
        busy: false,
        message: '',
        success: false,

        async run(command, extra = {}) {
            this.busy = true;
            this.message = '';
            try {
                const res = await fetch(`/ssl/${orderId}/action`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ command, ...extra }),
                });
                const data = await res.json();
                this.success = data.success;
                this.message = data.message;
            } catch (e) {
                this.success = false;
                this.message = 'Could not reach the server. Please try again.';
            } finally {
                this.busy = false;
            }
        },

        confirmCancel() {
            if (!confirm('Cancel this certificate? Renewals will stop at the end of the current billing cycle.')) return;
            this.run('cancel');
        },
    };
}
</script>
@endpush
