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
        <span class="text-sm font-semibold text-green-700 dark:text-green-400">Credit: {{ \App\Support\CurrencyConverter::format((float)$credit, 'NGN') }}</span>
    </div>
    @endif
</div>

{{-- Fund Wallet --}}
<div class="card mb-6" x-data="fundWallet({{ (float) $ngnRate }}, '{{ $paystackPublicKey }}', '{{ $flutterwavePublicKey }}', '{{ session('email') }}', {{ (int) session('clientId') }})">
    <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Fund Wallet</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Add funds to your wallet balance. Minimum ₦10,000, maximum ₦5,000,000.</p>

    <div class="max-w-xs">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Amount (NGN)</label>
        <input type="number" x-model.number="amount" min="10000" max="5000000" step="100"
               class="w-full rounded-lg border border-slate-300 dark:border-white/10 dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
               placeholder="e.g. 25000">
        <p class="text-xs text-slate-400 mt-1" x-show="amount > 0" x-text="usdEquivalent"></p>
        <p class="text-xs text-red-500 mt-1" x-show="amount > 0 && !isValid">Amount must be between ₦10,000 and ₦5,000,000.</p>
    </div>

    <div x-show="message" class="mt-3 text-sm p-3 rounded-lg max-w-md" :class="success ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400'" x-text="message"></div>

    <div class="flex flex-wrap gap-2 mt-4">
        <button @click="payWithPaystack()" :disabled="busy || !isValid || !paystackPublicKey" class="btn btn-primary">
            <span x-show="!busy">Fund with Paystack</span>
            <span x-show="busy">Processing…</span>
        </button>
        <button @click="payWithFlutterwave()" :disabled="busy || !isValid || !flutterwavePublicKey" class="btn btn-secondary">
            <span x-show="!busy">Fund with Flutterwave</span>
            <span x-show="busy">Processing…</span>
        </button>
        <button @click="payWithCrypto()" :disabled="busy || !isValid" class="btn btn-secondary">
            <span x-show="!busy">Fund with Crypto</span>
            <span x-show="busy">Processing…</span>
        </button>
    </div>
</div>

@if($invoices->isEmpty())
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
        $unpaidCount  = $invoices->where('status', 'unpaid')->count();
        $unpaidTotal  = $invoices->where('status', 'unpaid')->sum(fn($i) => (float) $i->total);
        $paidCount    = $invoices->where('status', 'paid')->count();
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

    {{-- Mobile: stacked cards --}}
    <div class="space-y-3 sm:hidden">
        <h3 class="font-semibold text-slate-900 dark:text-white px-1">All Invoices</h3>
        @foreach($invoices as $invoice)
        <a href="{{ route('billing.show', $invoice->id) }}" class="card block">
            <div class="flex items-start justify-between gap-3 mb-2">
                <p class="font-medium text-slate-900 dark:text-white">#{{ $invoice->id }}</p>
                <span class="badge badge-{{ $invoice->status }} flex-shrink-0">
                    {{ ucfirst($invoice->status) }}
                </span>
            </div>
            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                {{ $invoice->currency_code }} {{ number_format((float) $invoice->total, 2) }}
            </p>
            <p class="text-xs {{ $invoice->status === 'unpaid' ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-slate-500 dark:text-slate-400' }} mt-1">
                {{ $invoice->created_at->format('M j, Y') }}
            </p>
        </a>
        @endforeach
    </div>

    {{-- Desktop / tablet: full table --}}
    <div class="card !p-0 overflow-hidden hidden sm:block">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-white/[0.06]">
            <h3 class="font-semibold text-slate-900 dark:text-white">All Invoices</h3>
        </div>
        <div class="table-container rounded-none border-0">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-white">#{{ $invoice->id }}</td>
                        <td>{{ $invoice->created_at->format('M j, Y') }}</td>
                        <td class="font-semibold text-slate-900 dark:text-white">
                            {{ $invoice->currency_code }} {{ number_format((float) $invoice->total, 2) }}
                        </td>
                        <td>
                            <span class="badge badge-{{ $invoice->status }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('billing.show', $invoice->id) }}"
                               class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline whitespace-nowrap">
                                View {{ $invoice->status === 'unpaid' ? '& Pay' : '' }} →
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

@push('scripts')
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://checkout.flutterwave.com/v3.js"></script>
<script>
function fundWallet(ngnRate, paystackPublicKey, flutterwavePublicKey, email, clientId) {
    return {
        amount: null,
        ngnRate,
        paystackPublicKey,
        flutterwavePublicKey,
        email,
        clientId,
        busy: false,
        message: '',
        success: false,

        get isValid() {
            return this.amount >= 10000 && this.amount <= 5000000;
        },

        get usdEquivalent() {
            if (!this.amount || !this.ngnRate) return '';
            const usd = this.amount / this.ngnRate;
            return '≈ $' + usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        payWithPaystack() {
            if (!this.isValid) return;
            if (!this.paystackPublicKey || typeof PaystackPop === 'undefined') {
                this.success = false;
                this.message = 'Paystack is not available right now. Please try again later.';
                return;
            }

            this.busy = true;
            this.message = '';

            const reference = `kloud101-wallet-${this.clientId}-${Date.now()}`;

            const handler = PaystackPop.setup({
                key: this.paystackPublicKey,
                email: this.email,
                amount: Math.round(this.amount * 100), // smallest currency unit (kobo)
                currency: 'NGN',
                ref: reference,
                metadata: {
                    purpose: 'wallet',
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
            if (!this.isValid) return;
            if (!this.flutterwavePublicKey || typeof FlutterwaveCheckout === 'undefined') {
                this.success = false;
                this.message = 'Flutterwave is not available right now. Please try again later.';
                return;
            }

            this.busy = true;
            this.message = '';

            const txRef = `kloud101-wallet-${this.clientId}-${Date.now()}`;

            FlutterwaveCheckout({
                public_key: this.flutterwavePublicKey,
                tx_ref: txRef,
                amount: this.amount,
                currency: 'NGN',
                payment_options: 'card,banktransfer,ussd',
                customer: { email: this.email },
                meta: {
                    purpose: 'wallet',
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
            if (!this.isValid) return;

            this.busy = true;
            this.message = '';

            try {
                const res = await fetch('/billing/wallet/fund/nowpayments/init', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ amount: this.amount }),
                });
                const data = await res.json();
                if (data.success && data.invoice_url) {
                    window.open(data.invoice_url, '_blank', 'noopener');
                    this.success = true;
                    this.message = 'Crypto payment started — this can take a few minutes to confirm. Your balance will update automatically once it does.';
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
                const res = await fetch(`/billing/wallet/fund/${gateway}/verify`, {
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
                this.message = data.message || (data.success ? 'Wallet funded successfully.' : 'Payment could not be confirmed.');
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
