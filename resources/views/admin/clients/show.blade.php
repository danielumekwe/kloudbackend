@extends('layouts.admin')
@section('title', $client->firstname . ' ' . $client->lastname)
@section('breadcrumb')
    <a href="{{ route('admin.clients.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Clients</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">{{ $client->firstname }} {{ $client->lastname }}</span>
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

{{-- Header / status --}}
<div class="card mb-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ $client->firstname }} {{ $client->lastname }}</p>
                @if($client->isSuspended())
                    <span class="badge badge-suspended">Suspended</span>
                @else
                    <span class="badge badge-active">Active</span>
                @endif
                @if($client->isEmailVerified())
                    <span class="text-xs font-medium text-green-600 dark:text-green-400">Verified</span>
                @else
                    <span class="text-xs font-medium text-slate-400">Unverified</span>
                @endif
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ $client->email }}</p>
        </div>
        <span class="font-mono text-sm font-bold px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-white/[0.06] text-slate-700 dark:text-slate-300">
            {{ $client->accountCode() }}
        </span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-slate-100 dark:border-white/[0.06] text-sm">
        <div>
            <p class="text-xs text-slate-400">Client since</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $client->created_at->format('M j, Y') }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Credit balance</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">${{ number_format((float) $client->credit_balance, 2) }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">Phone</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $client->phonenumber ?: '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-slate-400">WHMCS linked</p>
            <p class="text-slate-700 dark:text-slate-300 mt-0.5">{{ $client->whmcs_client_id ? 'Yes (#' . $client->whmcs_client_id . ')' : 'No' }}</p>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3 flex-wrap mt-5 pt-5 border-t border-slate-100 dark:border-white/[0.06]">
        <form method="POST" action="{{ route('admin.clients.reset-password', $client) }}">
            @csrf
            <button type="submit" class="btn btn-secondary text-sm">Send Password Reset</button>
        </form>

        @unless($client->isEmailVerified())
        <form method="POST" action="{{ route('admin.clients.resend-verification', $client) }}">
            @csrf
            <button type="submit" class="btn btn-secondary text-sm">Resend Verification Email</button>
        </form>
        @endunless

        @if($client->isSuspended())
        <form method="POST" action="{{ route('admin.clients.unsuspend', $client) }}">
            @csrf
            <button type="submit" class="btn btn-success text-sm">Unsuspend Client</button>
        </form>
        @else
        <form method="POST" action="{{ route('admin.clients.suspend', $client) }}"
              x-data="{ confirm: false }"
              @submit.prevent="confirm ? $el.submit() : (confirm = true)">
            @csrf
            <button type="submit"
                    x-text="confirm ? 'Click again to confirm' : 'Suspend Client'"
                    class="btn btn-danger text-sm"></button>
        </form>
        @endif
    </div>
</div>

{{-- Edit profile --}}
<div class="card mb-6">
    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Edit Profile</h3>
    <form method="POST" action="{{ route('admin.clients.update', $client) }}">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label">First name</label>
                <input type="text" name="firstname" value="{{ old('firstname', $client->firstname) }}" required class="form-input">
            </div>
            <div>
                <label class="form-label">Last name</label>
                <input type="text" name="lastname" value="{{ old('lastname', $client->lastname) }}" required class="form-input">
            </div>
            <div>
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email', $client->email) }}" required class="form-input">
            </div>
            <div>
                <label class="form-label">Phone</label>
                <input type="text" name="phonenumber" value="{{ old('phonenumber', $client->phonenumber) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Address</label>
                <input type="text" name="address1" value="{{ old('address1', $client->address1) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">City</label>
                <input type="text" name="city" value="{{ old('city', $client->city) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">State</label>
                <input type="text" name="state" value="{{ old('state', $client->state) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Postcode</label>
                <input type="text" name="postcode" value="{{ old('postcode', $client->postcode) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Country (2-letter code)</label>
                <input type="text" name="country" maxlength="2" value="{{ old('country', $client->country) }}" class="form-input">
            </div>
        </div>

        @if($errors->any())
        <div class="mt-4 p-3 rounded-lg bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
            @foreach($errors->all() as $error)
                <p class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <div class="mt-5">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

{{-- Tickets --}}
@if(empty($tickets))
<div class="card text-center py-12">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No support tickets</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400">This client hasn't opened any tickets yet.</p>
</div>
@else
{{-- Mobile: stacked cards --}}
<div class="space-y-3 sm:hidden">
    @foreach($tickets as $ticket)
    @php
        $ts = strtolower($ticket['status'] ?? '');
        $badgeClass = match(true) {
            $ts === 'open'              => 'badge-open',
            str_contains($ts, 'answer') => 'badge-answered',
            str_contains($ts, 'reply')  => 'badge-answered',
            $ts === 'closed'            => 'badge-closed',
            default                     => 'badge-open',
        };
    @endphp
    <a href="{{ route('admin.tickets.show', $ticket['id']) }}" class="card block">
        <div class="flex items-start justify-between gap-3 mb-2">
            <p class="font-medium text-slate-900 dark:text-white">{{ $ticket['subject'] ?? '(No subject)' }}</p>
            <span class="badge {{ $badgeClass }} flex-shrink-0">{{ $ticket['status'] ?? '' }}</span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ $ticket['code'] ?? '#' . ($ticket['tid'] ?? $ticket['id']) }} &middot; {{ $ticket['deptname'] ?? '—' }} &middot; {{ $ticket['priority'] ?? '—' }} priority
        </p>
        @if(!empty($ticket['service_label']))
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $ticket['service_label'] }}</p>
        @endif
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $ticket['lastreply'] ?? $ticket['date'] ?? '—' }}</p>
    </a>
    @endforeach
</div>

{{-- Desktop / tablet: full table --}}
<div class="card !p-0 overflow-hidden hidden sm:block">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-white/[0.06]">
        <h3 class="font-semibold text-slate-900 dark:text-white">Support Tickets</h3>
    </div>
    <div class="table-container rounded-none border-0">
        <table>
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Subject</th>
                    <th>Department</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $ticket)
                @php
                    $ts = strtolower($ticket['status'] ?? '');
                    $badgeClass = match(true) {
                        $ts === 'open'              => 'badge-open',
                        str_contains($ts, 'answer') => 'badge-answered',
                        str_contains($ts, 'reply')  => 'badge-answered',
                        $ts === 'closed'            => 'badge-closed',
                        default                     => 'badge-open',
                    };
                    $priorityColor = match(strtolower($ticket['priority'] ?? '')) {
                        'high'   => 'text-red-600 dark:text-red-400',
                        'medium' => 'text-amber-600 dark:text-amber-400',
                        default  => 'text-slate-500 dark:text-slate-400',
                    };
                @endphp
                <tr>
                    <td><span class="font-mono text-xs font-medium text-slate-600 dark:text-slate-300">{{ $ticket['code'] ?? '#' . ($ticket['tid'] ?? $ticket['id']) }}</span></td>
                    <td>
                        <a href="{{ route('admin.tickets.show', $ticket['id']) }}"
                           class="font-medium text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                            {{ $ticket['subject'] ?? '(No subject)' }}
                        </a>
                        @if(!empty($ticket['service_label']))
                        <div class="text-xs text-slate-400 mt-0.5">{{ $ticket['service_label'] }}</div>
                        @endif
                    </td>
                    <td class="text-slate-600 dark:text-slate-400">{{ $ticket['deptname'] ?? '—' }}</td>
                    <td><span class="text-xs font-medium {{ $priorityColor }}">{{ $ticket['priority'] ?? '—' }}</span></td>
                    <td><span class="badge {{ $badgeClass }}">{{ $ticket['status'] ?? '' }}</span></td>
                    <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $ticket['lastreply'] ?? $ticket['date'] ?? '—' }}</td>
                    <td>
                        <a href="{{ route('admin.tickets.show', $ticket['id']) }}"
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
@endif
@endsection
