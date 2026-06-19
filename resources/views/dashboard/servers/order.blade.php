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
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Choose a plan and billing cycle to get started.</p>
</div>

@if($errors->any())
<div class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        @foreach($errors->all() as $error)
            <p class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
        @endforeach
    </div>
</div>
@endif

@if(empty($products))
    <div class="card text-center py-16">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No plans available</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">There are currently no products available to order. Please contact support.</p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($products as $product)
        @php
            $cycles       = $product['cycles'];
            $defaultCycle = array_key_first($cycles);
        @endphp
        <div class="card flex flex-col" x-data="{ cycle: '{{ $defaultCycle }}' }">
            <div class="flex items-start justify-between mb-3">
                <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5.5 h-5.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                </div>
            </div>

            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">{{ $product['name'] ?? 'Plan' }}</h3>

            <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1.5 mb-5 flex-1">
                @foreach(array_filter(explode("\n", $product['description'] ?? '')) as $line)
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ trim($line) }}
                </li>
                @endforeach
            </ul>

            <form method="POST" action="{{ route('servers.order.store') }}"
                  x-data="{ loading: false, cycles: {{ json_encode($cycles) }} }"
                  @submit="loading = true">
                @csrf
                <input type="hidden" name="pid" value="{{ $product['pid'] }}">
                <input type="hidden" name="billingcycle" :value="cycle">

                <div class="mb-4">
                    <label class="form-label">Billing cycle</label>
                    <select x-model="cycle" class="form-input">
                        @foreach($cycles as $key => $cycle)
                            <option value="{{ $key }}">{{ $cycle['label'] }} — ${{ number_format($cycle['price'], 2) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-baseline justify-between mb-4">
                    <span class="text-2xl font-bold text-slate-900 dark:text-white">
                        $<span x-text="cycles[cycle].price.toFixed(2)"></span>
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500">USD</span>
                </div>

                <button type="submit" :disabled="loading" class="btn btn-primary w-full justify-center">
                    <span x-show="!loading">Order Now</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <div class="spinner"></div>
                        Placing order…
                    </span>
                </button>
            </form>
        </div>
        @endforeach
    </div>
@endif
@endsection
