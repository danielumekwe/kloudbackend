@extends('layouts.admin')
@section('title', 'Clients')
@section('breadcrumb', 'Clients')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Clients</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">All registered customers on the platform.</p>
</div>

<div class="card mb-6">
    <form method="GET" action="{{ route('admin.clients.index') }}" class="flex items-center gap-3">
        <input type="text" name="q" value="{{ $query }}" autofocus
               placeholder="Search by name, email, or account code (KLD-9SGM2)…"
               class="form-input flex-1">
        <button type="submit" class="btn btn-primary flex-shrink-0">Search</button>
        @if($query !== '')
        <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary flex-shrink-0">Clear</a>
        @endif
    </form>
</div>

@if(session('success'))
<div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif

@if($clients->isEmpty())
<div class="card text-center py-12">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No clients found</h3>
    <p class="text-sm text-slate-500 dark:text-slate-400">
        @if($query !== '')
            No name, email, or account code matches "{{ $query }}".
        @else
            No clients have registered yet.
        @endif
    </p>
</div>
@else
    {{-- Mobile: stacked cards --}}
    <div class="space-y-3 sm:hidden">
        @foreach($clients as $c)
        <a href="{{ route('admin.clients.show', $c) }}" class="card block">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div>
                    <p class="font-medium text-slate-900 dark:text-white">{{ $c->firstname }} {{ $c->lastname }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $c->email }}</p>
                </div>
                @if($c->isSuspended())
                <span class="badge badge-suspended flex-shrink-0">Suspended</span>
                @endif
            </div>
            <p class="text-xs text-slate-400 dark:text-slate-500">
                {{ $c->accountCode() }} &middot; Joined {{ $c->created_at->format('M j, Y') }}
                &middot; {{ $c->isEmailVerified() ? 'Verified' : 'Unverified' }}
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
                        <th>Account Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Joined</th>
                        <th>Email Verified</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $c)
                    <tr>
                        <td><span class="font-mono text-xs font-medium text-slate-600 dark:text-slate-300">{{ $c->accountCode() }}</span></td>
                        <td>
                            <a href="{{ route('admin.clients.show', $c) }}"
                               class="font-medium text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                {{ $c->firstname }} {{ $c->lastname }}
                            </a>
                        </td>
                        <td class="text-slate-600 dark:text-slate-400">{{ $c->email }}</td>
                        <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $c->created_at->format('M j, Y') }}</td>
                        <td>
                            @if($c->isEmailVerified())
                                <span class="text-green-600 dark:text-green-400 text-xs font-medium">Verified</span>
                            @else
                                <span class="text-slate-400 text-xs">Unverified</span>
                            @endif
                        </td>
                        <td>
                            @if($c->isSuspended())
                                <span class="badge badge-suspended">Suspended</span>
                            @else
                                <span class="badge badge-active">Active</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.clients.show', $c) }}"
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

    <div class="mt-6">
        {{ $clients->links() }}
    </div>
@endif
@endsection
