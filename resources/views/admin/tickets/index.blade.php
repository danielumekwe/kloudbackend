@extends('layouts.admin')
@section('title', 'Support Tickets')
@section('breadcrumb', 'Support Tickets')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Support Tickets</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">All client tickets across departments</p>
    </div>
</div>

@if(session('success'))
<div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif

@if(empty($tickets))
    <div class="card text-center py-16">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No tickets yet</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">Client tickets will show up here as they come in.</p>
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
                {{ $ticket['code'] ?? '#' . $ticket['id'] }} &middot; {{ $ticket['client_name'] ?: '—' }} ({{ $ticket['client_email'] ?? '' }})
            </p>
            @if(!empty($ticket['service_label']))
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $ticket['service_label'] }}</p>
            @endif
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                {{ $ticket['deptname'] ?? '—' }} &middot; {{ $ticket['priority'] ?? '—' }} priority &middot; {{ $ticket['lastreply'] ?? $ticket['date'] ?? '—' }}
            </p>
        </a>
        @endforeach
    </div>

    {{-- Desktop / tablet: full table --}}
    <div class="card !p-0 overflow-hidden hidden sm:block">
        <div class="table-container rounded-none border-0">
            <table>
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Client</th>
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
                        <td>
                            <span class="font-mono text-xs font-medium text-slate-600 dark:text-slate-300">{{ $ticket['code'] ?? '#' . $ticket['id'] }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.tickets.show', $ticket['id']) }}"
                               class="font-medium text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                {{ $ticket['subject'] ?? '(No subject)' }}
                            </a>
                            @if(!empty($ticket['service_label']))
                            <div class="text-xs text-slate-400 mt-0.5">{{ $ticket['service_label'] }}</div>
                            @endif
                        </td>
                        <td class="text-slate-600 dark:text-slate-400">
                            {{ $ticket['client_name'] ?: '—' }}
                            <div class="text-xs text-slate-400">{{ $ticket['client_email'] ?? '' }}</div>
                        </td>
                        <td class="text-slate-600 dark:text-slate-400">{{ $ticket['deptname'] ?? '—' }}</td>
                        <td>
                            <span class="text-xs font-medium {{ $priorityColor }}">{{ $ticket['priority'] ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }}">{{ $ticket['status'] ?? '' }}</span>
                        </td>
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
