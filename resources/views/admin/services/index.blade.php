@extends('layouts.admin')
@section('title', 'Services')
@section('breadcrumb', 'Services')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Services</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">All provisioned services across clients.</p>
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

{{-- Summary stats --}}
<div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
    <div class="card text-center">
        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $stats['vps_active'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Active VPS</p>
    </div>
    <div class="card text-center">
        <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['vps_suspended'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Suspended VPS</p>
    </div>
    <div class="card text-center">
        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $stats['qs_active'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Active QS</p>
    </div>
    <div class="card text-center">
        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $stats['ssl_active'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Active SSL</p>
    </div>
    <div class="card text-center">
        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $stats['domain_active'] }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Active Domains</p>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-6">
    <form method="GET" action="{{ route('admin.services.index') }}" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-slate-500 dark:text-slate-400">Type:</label>
            <select name="type" class="form-input py-1.5 text-sm">
                <option value="vps"    @selected($type === 'vps')>VPS</option>
                <option value="qs"     @selected($type === 'qs')>Quick Server</option>
                <option value="ssl"    @selected($type === 'ssl')>SSL</option>
                <option value="domain" @selected($type === 'domain')>Domain</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-slate-500 dark:text-slate-400">Status:</label>
            <select name="status" class="form-input py-1.5 text-sm">
                <option value="all"          @selected($status === 'all')>All</option>
                <option value="provisioned"  @selected($status === 'provisioned')>Active</option>
                <option value="suspended"    @selected($status === 'suspended')>Suspended</option>
                <option value="cancelled"    @selected($status === 'cancelled')>Cancelled</option>
                <option value="failed"       @selected($status === 'failed')>Failed</option>
                <option value="provisioning" @selected($status === 'provisioning')>Provisioning</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary text-sm py-1.5">Filter</button>
    </form>
</div>

@php
    $items = $vps->isNotEmpty() ? $vps : ($qs->isNotEmpty() ? $qs : ($ssl->isNotEmpty() ? $ssl : $domain));
    $isVps = $type === 'vps';
    $isQs  = $type === 'qs';
@endphp

@if($items->isEmpty())
<div class="card text-center py-12">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No services found</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400">No {{ $type }} services match the selected filter.</p>
</div>
@else

{{-- Mobile: stacked cards --}}
<div class="space-y-3 sm:hidden">
    @foreach($items as $svc)
    @php
        $badgeClass = match($svc->status) {
            'provisioned'  => 'badge-active',
            'suspended'    => 'badge-answered',
            'provisioning' => 'badge-answered',
            'pending_payment' => 'badge-open',
            'failed'       => 'badge-suspended',
            'cancelled'    => 'badge-closed',
            default        => 'badge-open',
        };
        $detailRoute = $isVps ? route('admin.services.vps.show', $svc)
                     : ($isQs  ? route('admin.services.qs.show', $svc) : null);
        $desc = $isVps ? ($svc->config['hostname'] ?? $svc->config['plan_name'] ?? 'VPS')
              : ($isQs  ? ($svc->config['plan_name'] ?? 'Quick Server')
              : ($type === 'ssl' ? ($svc->config['domain'] ?? 'SSL') : ($svc->domain_name . '.' . $svc->tld)));
    @endphp
    <div class="card">
        <div class="flex items-start justify-between gap-3 mb-2">
            <div>
                <p class="font-medium text-slate-900 dark:text-white">{{ $desc }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $svc->client?->firstname }} {{ $svc->client?->lastname }} &middot; {{ $svc->client?->email }}</p>
            </div>
            <span class="badge {{ $badgeClass }} flex-shrink-0">{{ $svc->status }}</span>
        </div>
        <div class="flex items-center gap-3 mt-3">
            @if($detailRoute)
            <a href="{{ $detailRoute }}" class="btn btn-secondary text-xs py-1">Manage →</a>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Desktop: table --}}
<div class="card !p-0 overflow-hidden hidden sm:block">
    <div class="table-container rounded-none border-0">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Client</th>
                    @if($isVps)<th>InterServer ID</th>@endif
                    @if($isQs)<th>InterServer ID</th>@endif
                    <th>Price</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $svc)
                @php
                    $badgeClass = match($svc->status) {
                        'provisioned'  => 'badge-active',
                        'suspended'    => 'badge-answered',
                        'provisioning' => 'badge-answered',
                        'pending_payment' => 'badge-open',
                        'failed'       => 'badge-suspended',
                        'cancelled'    => 'badge-closed',
                        default        => 'badge-open',
                    };
                    $desc = $isVps ? ($svc->config['hostname'] ?? $svc->config['plan_name'] ?? 'VPS')
                          : ($isQs  ? ($svc->config['plan_name'] ?? 'Quick Server')
                          : ($type === 'ssl' ? ($svc->config['domain'] ?? 'SSL') : ($svc->domain_name . '.' . $svc->tld)));
                    $detailRoute = $isVps ? route('admin.services.vps.show', $svc)
                                 : ($isQs  ? route('admin.services.qs.show', $svc) : null);
                    $serverId = $isVps ? $svc->interserver_vps_id : ($isQs ? $svc->interserver_qs_id : null);
                @endphp
                <tr>
                    <td class="font-mono text-xs text-slate-500">#{{ $svc->id }}</td>
                    <td class="font-medium text-slate-900 dark:text-white">{{ $desc }}</td>
                    <td>
                        @if($svc->client)
                        <a href="{{ route('admin.clients.show', $svc->client) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                            {{ $svc->client->firstname }} {{ $svc->client->lastname }}
                        </a>
                        <div class="text-xs text-slate-400">{{ $svc->client->email }}</div>
                        @else —
                        @endif
                    </td>
                    @if($isVps || $isQs)
                    <td class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $serverId ?? '—' }}</td>
                    @endif
                    <td class="text-slate-700 dark:text-slate-300">${{ number_format((float)$svc->price, 2) }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ $svc->status }}</span></td>
                    <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $svc->created_at->format('M j, Y') }}</td>
                    <td>
                        @if($detailRoute)
                        <a href="{{ $detailRoute }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">Manage →</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $items->links() }}</div>
@endif
@endsection
