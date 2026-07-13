@extends('layouts.app')
@section('title', 'Invoice #' . $invoice->id)
@section('breadcrumb')
    <a href="{{ route('billing.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Billing</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">Invoice #{{ $invoice->id }}</span>
@endsection

@section('content')
<div x-data="invoicePayment({{ $invoice->id }}, {{ (float) $invoice->total }}, '{{ $invoice->currency_code }}', '{{ $paystackPublicKey }}', '{{ $flutterwavePublicKey }}', '{{ session('email') }}', {{ (int) session('clientId') }})">
@php
    $badgeClass = 'badge-' . $invoice->status;
@endphp

<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div class="flex items-center gap-3">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Invoice #{{ $invoice->id }}</h1>
        <span class="badge {{ $badgeClass }}">{{ ucfirst($invoice->status) }}</span>
    </div>
    <div class="flex items-center gap-3">
        @if($invoice->status === 'unpaid')
            @if($invoice->currency_code === 'USD')
            <button @click="payWithCrypto()" :disabled="busy" class="btn btn-primary">
                Pay with Crypto
            </button>
            @else
            <button @click="payWithPaystack()" :disabled="busy || !paystackPublicKey" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Pay with Paystack
            </button>
            @endif
        @endif
        <a href="{{ route('billing.index') }}" class="btn btn-secondary text-sm">← Back</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Invoice details --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Line items --}}
        <div class="card !p-0 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-white/[0.06]">
                <h3 class="font-semibold text-slate-900 dark:text-white">Invoice Items</h3>
            </div>
            @if($invoice->items->isEmpty())
                <div class="px-5 py-6 text-sm text-slate-500 dark:text-slate-400">No line items found.</div>
            @else
            <table class="w-full">
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
                        <td class="text-right font-semibold text-slate-900 dark:text-white">
                            {{ \App\Support\CurrencyConverter::format((float) $item->amount, $invoice->currency_code) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{-- Totals --}}
            <div class="px-5 py-4 border-t border-slate-100 dark:border-white/[0.06] space-y-1.5">
                <div class="flex justify-between text-sm text-slate-500 dark:text-slate-400">
                    <span>Subtotal</span>
                    <span>{{ \App\Support\CurrencyConverter::format((float) $invoice->subtotal, $invoice->currency_code) }}</span>
                </div>
                @if((float) $invoice->tax_amount > 0)
                <div class="flex justify-between text-sm text-slate-500 dark:text-slate-400">
                    <span>Tax ({{ rtrim(rtrim(number_format($invoice->tax_rate, 2), '0'), '.') }}%)</span>
                    <span>{{ \App\Support\CurrencyConverter::format((float) $invoice->tax_amount, $invoice->currency_code) }}</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-slate-900 dark:text-white pt-1 border-t border-slate-100 dark:border-white/[0.06]">
                    <span>Total</span>
                    <span>{{ \App\Support\CurrencyConverter::format((float) $invoice->total, $invoice->currency_code) }}</span>
                </div>
            </div>
            @endif
        </div>

        {{-- Transactions --}}
        @if($invoice->paymentTransactions->isNotEmpty())
        <div class="card !p-0 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-white/[0.06]">
                <h3 class="font-semibold text-slate-900 dark:text-white">Transactions</h3>
            </div>
            <table class="w-full">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Gateway</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->paymentTransactions as $tx)
                    <tr>
                        <td>{{ $tx->created_at->format('M j, Y') }}</td>
                        <td class="capitalize">{{ $tx->gateway }}</td>
                        <td class="text-right font-medium text-green-600 dark:text-green-400">
                            {{ \App\Support\CurrencyConverter::format((float) $tx->amount, $tx->currency) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Sidebar: summary --}}
    <div class="space-y-4">
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Invoice Summary</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Invoice ID</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200">#{{ $invoice->id }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Date Issued</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200">{{ $invoice->created_at->format('M j, Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                    <dd><span class="badge {{ $badgeClass }}">{{ ucfirst($invoice->status) }}</span></dd>
                </div>
                @if($invoice->payment_method)
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Payment Method</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200 capitalize">{{ $invoice->payment_method }}</dd>
                </div>
                @endif
            </dl>

            @if($invoice->status === 'unpaid')
            <div x-show="message" class="mt-4 text-sm p-3 rounded-lg" :class="success ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400'" x-text="message"></div>

            @if($invoice->currency_code === 'USD')
                <button @click="payWithCrypto()" :disabled="busy" class="btn btn-primary w-full mt-5">
                    <span x-show="!busy">Pay with Crypto</span>
                    <span x-show="busy">Processing…</span>
                </button>
                <p class="text-xs text-slate-400 mt-2" x-show="cryptoPending">Crypto payment pending — this can take a few minutes to confirm. We'll update this invoice automatically once it does.</p>
                <p class="text-xs text-slate-400 mt-2">Crypto (NOWPayments) is the only payment method available for USD invoices. Switch your account currency to NGN for card/bank transfer options.</p>
            @else
                <button @click="payWithPaystack()" :disabled="busy || !paystackPublicKey" class="btn btn-primary w-full mt-5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span x-show="!busy">Pay with Paystack</span>
                    <span x-show="busy">Processing…</span>
                </button>
                <p class="text-xs text-slate-400 mt-2" x-show="!paystackPublicKey">Paystack is not configured yet.</p>

                <button @click="payWithFlutterwave()" :disabled="busy || !flutterwavePublicKey" class="btn btn-secondary w-full mt-2">
                    <span x-show="!busy">Pay with Flutterwave</span>
                    <span x-show="busy">Processing…</span>
                </button>
                <p class="text-xs text-slate-400 mt-2" x-show="!flutterwavePublicKey">Flutterwave is not configured yet.</p>
            @endif
            @endif
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://checkout.flutterwave.com/v3.js"></script>
<script>
function invoicePayment(invoiceId, amount, currency, paystackPublicKey, flutterwavePublicKey, email, clientId) {
    return {
        invoiceId,
        amount,
        currency,
        paystackPublicKey,
        flutterwavePublicKey,
        email,
        clientId,
        busy: false,
        message: '',
        success: false,
        cryptoPending: false,

        payWithPaystack() {
            if (!this.paystackPublicKey || typeof PaystackPop === 'undefined') {
                this.success = false;
                this.message = 'Paystack is not available right now. Please try again later.';
                return;
            }

            this.busy = true;
            this.message = '';

            const reference = `kloud101-invoice-${this.invoiceId}-${Date.now()}`;

            const handler = PaystackPop.setup({
                key: this.paystackPublicKey,
                email: this.email,
                amount: Math.round(this.amount * 100), // smallest currency unit (kobo)
                currency: this.currency,
                ref: reference,
                metadata: {
                    invoice_id: this.invoiceId,
                    client_id: this.clientId,
                },
                callback: (response) => {
                    this.verify('paystack', { reference: response.reference });
                },
                onClose: () => {
                    this.busy = false;
                },
            });

            handler.openIframe();
        },

        payWithFlutterwave() {
            if (!this.flutterwavePublicKey || typeof FlutterwaveCheckout === 'undefined') {
                this.success = false;
                this.message = 'Flutterwave is not available right now. Please try again later.';
                return;
            }

            this.busy = true;
            this.message = '';

            const txRef = `kloud101-invoice-${this.invoiceId}-${Date.now()}`;

            FlutterwaveCheckout({
                public_key: this.flutterwavePublicKey,
                tx_ref: txRef,
                amount: this.amount,
                currency: this.currency,
                payment_options: 'card,banktransfer,ussd',
                customer: { email: this.email },
                meta: {
                    invoice_id: this.invoiceId,
                    client_id: this.clientId,
                },
                callback: (response) => {
                    this.verify('flutterwave', { transaction_id: String(response.transaction_id) });
                },
                onclose: () => {
                    this.busy = false;
                },
            });
        },

        async payWithCrypto() {
            this.busy = true;
            this.message = '';

            try {
                const res = await fetch(`/billing/${this.invoiceId}/pay/nowpayments/init`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success && data.invoice_url) {
                    window.open(data.invoice_url, '_blank', 'noopener');
                    this.cryptoPending = true;
                } else {
                    this.success = false;
                    this.message = data.message || 'Could not start a crypto payment.';
                }
            } catch (e) {
                this.success = false;
                this.message = 'Could not reach the server. Please try again.';
            } finally {
                this.busy = false;
            }
        },

        async verify(gateway, body) {
            try {
                const res = await fetch(`/billing/${this.invoiceId}/pay/${gateway}/verify`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                this.success = data.success;
                this.message = data.message || (data.success ? 'Payment confirmed.' : 'Payment could not be confirmed.');
                if (data.success) {
                    setTimeout(() => window.location.reload(), 1500);
                }
            } catch (e) {
                this.success = false;
                this.message = 'Could not reach the server to confirm your payment. If you were charged, please contact support.';
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endpush
