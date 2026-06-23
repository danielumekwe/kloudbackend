@extends('layouts.app')
@section('title', 'Order a Server')
@section('breadcrumb')
    <a href="{{ route('servers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My Servers</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">Order a Server</span>
@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Order a Server</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Choose a service to get started.</p>
</div>

@foreach($groups as $group)
<div class="mb-8">
    <h2 class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-3">{{ $group['label'] }}</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($group['services'] as $service)
        <a href="{{ $service['route'] }}" class="card flex flex-col hover:shadow-md hover:border-blue-500/30 dark:hover:border-blue-500/30 transition-all duration-200 group">
            <div class="flex items-start justify-between mb-3">
                <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5.5 h-5.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                </div>
                @if(!empty($service['comingSoon']))
                    <span class="badge badge-pending">Coming soon</span>
                @endif
            </div>

            <h3 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-1">
                {{ $service['name'] }}
            </h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 flex-1">{{ $service['description'] }}</p>

            <span class="mt-4 text-sm text-blue-600 dark:text-blue-400 font-medium group-hover:underline">
                {{ !empty($service['comingSoon']) ? 'Learn more' : 'Order now' }} →
            </span>
        </a>
        @endforeach
    </div>
</div>
@endforeach
@endsection
