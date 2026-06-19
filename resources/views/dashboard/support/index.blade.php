@extends('layouts.app')
@section('title', 'Support')
@section('breadcrumb', 'Support')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Support Tickets</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Track and manage your support requests</p>
    </div>
    <a href="{{ route('support.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Ticket
    </a>
</div>

@if(empty($tickets))
    <div class="card text-center py-16">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No tickets yet</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Open a ticket and our team will get back to you shortly.</p>
        <a href="{{ route('support.create') }}" class="btn btn-primary">Open a Ticket</a>
    </div>
@else
    <div class="card !p-0 overflow-hidden">
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
                            $ts === 'open'            => 'badge-open',
                            str_contains($ts, 'answer') => 'badge-answered',
                            str_contains($ts, 'reply')  => 'badge-answered',
                            $ts === 'closed'          => 'badge-closed',
                            default                   => 'badge-open',
                        };
                        $priorityColor = match(strtolower($ticket['priority'] ?? '')) {
                            'high'   => 'text-red-600 dark:text-red-400',
                            'medium' => 'text-amber-600 dark:text-amber-400',
                            default  => 'text-slate-500 dark:text-slate-400',
                        };
                    @endphp
                    <tr>
                        <td>
                            <span class="font-mono text-xs font-medium text-slate-600 dark:text-slate-300">#{{ $ticket['tid'] ?? $ticket['id'] }}</span>
                        </td>
                        <td>
                            <a href="{{ route('support.show', $ticket['id']) }}"
                               class="font-medium text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                {{ $ticket['subject'] ?? '(No subject)' }}
                            </a>
                        </td>
                        <td class="text-slate-600 dark:text-slate-400">{{ $ticket['deptname'] ?? $ticket['department'] ?? '—' }}</td>
                        <td>
                            <span class="text-xs font-medium {{ $priorityColor }}">{{ $ticket['priority'] ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }}">{{ $ticket['status'] ?? '' }}</span>
                        </td>
                        <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $ticket['lastreply'] ?? $ticket['lastresponse'] ?? $ticket['date'] ?? '—' }}</td>
                        <td>
                            <a href="{{ route('support.show', $ticket['id']) }}"
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
