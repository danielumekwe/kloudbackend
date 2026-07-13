@extends('layouts.app')
@section('title', $order->domain_name . '.' . $order->tld)
@section('breadcrumb')
    <a href="{{ route('domains.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My Domains</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">{{ $order->domain_name }}.{{ $order->tld }}</span>
@endsection

@section('content')
<div x-data="domainManage({{ $order->id }})">

    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $order->domain_name }}.{{ $order->tld }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ ucfirst($order->order_type) }} · {{ $order->registration_years }} yr</p>
        </div>
        <span class="badge {{ $order->status === 'provisioned' ? 'badge-active' : ($order->status === 'failed' ? 'badge-suspended' : 'badge-pending') }}">
            {{ $order->status === 'provisioned' ? 'Active' : str_replace('_', ' ', $order->status) }}
        </span>
    </div>

    @if($order->status === 'pending_payment')
        <div class="card mb-6 flex items-start gap-3 bg-yellow-50 dark:bg-yellow-500/10 border-yellow-200 dark:border-yellow-500/20">
            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">Awaiting payment</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">This domain will be {{ $order->order_type === 'transfer' ? 'transferred' : 'registered' }} automatically as soon as this invoice is paid.</p>
                <a href="{{ route('billing.show', $order->invoice_id) }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline mt-2 inline-block">View invoice →</a>
            </div>
        </div>
    @elseif($order->status === 'failed')
        <div class="card mb-6 flex items-start gap-3 bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ ucfirst($order->order_type) }} failed</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">{{ $order->failure_reason ?? 'Please contact support.' }}</p>
                <a href="{{ route('support.create') }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline mt-2 inline-block">Contact support →</a>
            </div>
        </div>
    @endif

    @if($order->status === 'provisioned')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            <div x-show="message" class="text-sm p-3 rounded-lg" :class="success ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400'" x-text="message"></div>

            {{-- Contact --}}
            <div class="card">
                <button type="button" class="flex items-center justify-between w-full" @click="toggle('contact')">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Registrant Contact</h3>
                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open.contact ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open.contact" class="mt-4 grid grid-cols-2 gap-3">
                    <input type="text" x-model="contact.first_name" placeholder="First name" class="form-input">
                    <input type="text" x-model="contact.last_name" placeholder="Last name" class="form-input">
                    <input type="email" x-model="contact.email" placeholder="Email" class="form-input col-span-2">
                    <input type="text" x-model="contact.org_name" placeholder="Company" class="form-input col-span-2">
                    <input type="text" x-model="contact.address1" placeholder="Address" class="form-input col-span-2">
                    <input type="text" x-model="contact.city" placeholder="City" class="form-input">
                    <input type="text" x-model="contact.state" placeholder="State" class="form-input">
                    <input type="text" x-model="contact.postal_code" placeholder="Zip" class="form-input">
                    <input type="text" x-model="contact.country" placeholder="Country" class="form-input">
                    <input type="text" x-model="contact.phone" placeholder="Phone" class="form-input col-span-2">
                    <button @click="saveContact()" :disabled="busy" class="btn btn-primary col-span-2">Save Contact</button>
                </div>
            </div>

            {{-- Whois Privacy --}}
            <div class="card">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Whois Privacy</h3>
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-600 dark:text-slate-400">Currently {{ $order->whois_privacy ? 'enabled' : 'disabled' }} for this domain.</p>
                    <div class="flex gap-3">
                        <button @click="run('whois', 'enable')" :disabled="busy" class="btn btn-secondary">Enable</button>
                        <button @click="run('whois', 'disable')" :disabled="busy" class="btn btn-secondary">Disable</button>
                    </div>
                </div>
            </div>

            {{-- Nameservers --}}
            <div class="card">
                <button type="button" class="flex items-center justify-between w-full" @click="toggle('ns'); loadNameservers()">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Nameservers</h3>
                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open.ns ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open.ns" class="mt-4 space-y-4">
                    <pre x-show="nameservers" class="text-xs bg-slate-50 dark:bg-slate-900/50 rounded-lg p-3 overflow-x-auto" x-text="nameservers"></pre>
                    <div class="flex flex-wrap gap-3 items-end">
                        <input type="text" x-model="nsName" placeholder="ns1.example.com" class="form-input flex-1 min-w-[180px]">
                        <input type="text" x-model="nsIp" placeholder="IP address" class="form-input flex-1 min-w-[140px]">
                        <button @click="addNameserver()" :disabled="busy || !nsName || !nsIp" class="btn btn-primary">Add Nameserver</button>
                    </div>
                </div>
            </div>

            {{-- DNSSEC --}}
            <div class="card">
                <button type="button" class="flex items-center justify-between w-full" @click="toggle('dnssec'); loadDnssec()">
                    <h3 class="font-semibold text-slate-900 dark:text-white">DNSSEC</h3>
                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open.dnssec ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open.dnssec" class="mt-4 space-y-4">
                    <pre x-show="dnssecRecords" class="text-xs bg-slate-50 dark:bg-slate-900/50 rounded-lg p-3 overflow-x-auto" x-text="dnssecRecords"></pre>
                    <button @click="clearDnssec()" :disabled="busy" class="btn btn-danger">Clear DNSSEC Records</button>
                </div>
            </div>

            {{-- Renew --}}
            <div class="card">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Renew Domain</h3>
                <div class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="form-label">Years</label>
                        <select x-model.number="renewYears" class="form-input">
                            @for($y = 1; $y <= 10; $y++)<option value="{{ $y }}">{{ $y }}</option>@endfor
                        </select>
                    </div>
                    <button @click="renew()" :disabled="busy" class="btn btn-primary">Create Renewal Invoice</button>
                </div>
            </div>

            @if($order->order_type === 'transfer')
            {{-- Transfer status --}}
            <div class="card">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Transfer Status</h3>
                <button @click="checkTransfer()" :disabled="busy" class="btn btn-secondary">Re-check Transfer Status</button>
                <pre x-show="transferStatus" class="mt-3 text-xs bg-slate-50 dark:bg-slate-900/50 rounded-lg p-3 overflow-x-auto" x-text="transferStatus"></pre>
            </div>
            @endif

            {{-- Cancel --}}
            <div class="card">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Cancel Domain</h3>
                <p class="text-sm text-red-600 dark:text-red-400 mb-4">This stops auto-renewal at the registrar.</p>
                <button @click="confirmCancel()" :disabled="busy" class="btn btn-danger">Cancel Domain</button>
            </div>
        </div>

        <div class="card h-fit">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Details</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Domain</span><span class="font-medium text-slate-900 dark:text-white">{{ $order->domain_name }}.{{ $order->tld }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Expires</span><span class="font-medium text-slate-900 dark:text-white">{{ $live['serviceInfo']['domain_expire_date'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Status</span><span class="font-medium text-slate-900 dark:text-white">{{ $live['serviceInfo']['domain_status'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Price</span><span class="font-medium text-slate-900 dark:text-white">${{ number_format($order->price, 2) }}</span></div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function domainManage(orderId) {
    return {
        busy: false,
        message: '',
        success: false,
        open: { contact: false, ns: false, dnssec: false },
        contact: @json($order->registrant_contact ?? []),
        nameservers: '',
        nsName: '',
        nsIp: '',
        dnssecRecords: '',
        renewYears: 1,
        transferStatus: '',

        toggle(key) { this.open[key] = !this.open[key]; },

        async call(url, method = 'GET', body = null) {
            this.busy = true;
            this.message = '';
            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: body ? JSON.stringify(body) : undefined,
                });
                const data = await res.json();
                if ('success' in data) {
                    this.success = data.success;
                    this.message = data.message;
                }
                return data;
            } catch (e) {
                this.success = false;
                this.message = 'Could not reach the server. Please try again.';
                return null;
            } finally {
                this.busy = false;
            }
        },

        async run(command, value) {
            if (command === 'whois') {
                await this.call(`/domains/${orderId}/whois`, 'POST', { func: value });
            }
        },

        async saveContact() {
            await this.call(`/domains/${orderId}/contact`, 'POST', this.contact);
        },

        async loadNameservers() {
            const data = await this.call(`/domains/${orderId}/nameservers`);
            if (data) this.nameservers = JSON.stringify(data, null, 2);
        },

        async addNameserver() {
            const data = await this.call(`/domains/${orderId}/nameservers`, 'POST', { name: this.nsName, ipAddress: this.nsIp });
            if (data?.success) { this.nsName = ''; this.nsIp = ''; this.loadNameservers(); }
        },

        async loadDnssec() {
            const data = await this.call(`/domains/${orderId}/dnssec`);
            if (data) this.dnssecRecords = JSON.stringify(data, null, 2);
        },

        async clearDnssec() {
            if (!confirm('Clear all DNSSEC records for this domain? This disables DNSSEC at the registrar.')) return;
            await this.call(`/domains/${orderId}/dnssec`, 'DELETE');
            this.dnssecRecords = '';
        },

        async renew() {
            const data = await this.call(`/domains/${orderId}/renew`, 'POST', { years: this.renewYears });
            if (data?.success && data.invoice_id) {
                window.location.href = `/billing/${data.invoice_id}`;
            }
        },

        async checkTransfer() {
            const data = await this.call(`/domains/${orderId}/transfer`, 'POST');
            if (data) this.transferStatus = JSON.stringify(data, null, 2);
        },

        confirmCancel() {
            if (!confirm('Cancel this domain? Auto-renewal will stop at the registrar.')) return;
            this.call(`/domains/${orderId}/action`, 'POST', { command: 'cancel' });
        },
    };
}
</script>
@endpush
