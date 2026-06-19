@extends('layouts.app')
@section('title', 'My VPS')
@section('breadcrumb', 'My VPS')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">My VPS</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Your cloud VPS instances, powered by InterServer</p>
    </div>
</div>

@if($instances->isEmpty())
    <div class="card text-center py-16">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No VPS instances yet</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Pick a VPS plan from the sidebar to get started.</p>
    </div>
@else
    <div class="card !p-0 overflow-hidden">
        <div class="table-container rounded-none border-0">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hostname</th>
                        <th>Plan</th>
                        <th>IP Address</th>
                        <th>Slices</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Ordered</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($instances as $item)
                    @php
                        $order = $item['order'];
                        $live  = $item['live'];
                        $badgeClass = match($order->status) {
                            'provisioned'     => 'badge-active',
                            'paid'            => 'badge-pending',
                            'pending_payment' => 'badge-unpaid',
                            'failed'          => 'badge-suspended',
                            default           => 'badge-cancelled',
                        };
                        $statusLabel = match($order->status) {
                            'provisioned'     => $live['vps_status'] ?? 'Active',
                            'paid'            => 'Provisioning…',
                            'pending_payment' => 'Awaiting Payment',
                            'failed'          => 'Failed',
                            default           => ucfirst($order->status),
                        };
                    @endphp
                    <tr>
                        <td class="text-slate-500 dark:text-slate-400">#{{ $order->id }}</td>
                        <td class="font-medium text-slate-900 dark:text-white">{{ $order->config['hostname'] ?? 'VPS #' . $order->id }}</td>
                        <td>{{ config('vps_pricing.categories.' . $order->category . '.label', $order->category) }}</td>
                        <td>{{ $live['vps_ip'] ?? '—' }}</td>
                        <td>{{ $order->config['slices'] ?? '—' }}</td>
                        <td>${{ number_format($order->price, 2) }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                        <td>{{ $order->created_at->format('M j, Y') }}</td>
                        <td>
                            <a href="{{ route('vps.show', $order->id) }}"
                               class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline whitespace-nowrap">
                                Manage →
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
