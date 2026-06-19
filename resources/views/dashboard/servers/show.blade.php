@extends('layouts.app')
@section('title', ($service['name'] ?? 'Server') . ' — Manage')
@section('breadcrumb')
    <a href="{{ route('servers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My Servers</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">{{ $service['name'] ?? 'Server' }}</span>
@endsection

@section('content')
@php
    $status = strtolower($service['status'] ?? '');
    $badgeClass = match($status) {
        'active'    => 'badge-active',
        'suspended' => 'badge-suspended',
        'pending'   => 'badge-pending',
        default     => 'badge-cancelled',
    };
    $isActive = $status === 'active';
@endphp

{{-- Header --}}
<div class="flex items-start justify-between mb-6 flex-wrap gap-4">
    <div>
        <div class="flex items-center gap-3 mb-1">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $service['name'] ?? 'Server' }}</h1>
            <span class="badge {{ $badgeClass }}">{{ $service['status'] ?? '' }}</span>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ $service['domain'] ?? $service['groupname'] ?? '' }} &mdash; ID #{{ $service['id'] ?? '' }}
        </p>
    </div>
    <a href="{{ route('servers.index') }}" class="btn btn-secondary text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Servers
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ----------------------------------------------------------------
         Left: Server info
    ----------------------------------------------------------------- --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Server Details</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Product</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200 text-right">{{ $service['name'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Group</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200 text-right">{{ $service['groupname'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Domain</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200 text-right">{{ $service['domain'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">IP Address</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200 font-mono text-right">{{ $service['dedicatedip'] ?? $service['assignedips'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Billing cycle</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200 text-right">{{ $service['billingcycle'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Amount</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200 text-right">
                        ${{ number_format((float)($service['recurringamount'] ?? 0), 2) }}/mo
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Next due</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200 text-right">{{ $service['nextduedate'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">Created</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200 text-right">{{ $service['regdate'] ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Right: Server actions (AJAX with Alpine.js)
    ----------------------------------------------------------------- --}}
    <div class="lg:col-span-2 space-y-6"
         x-data="serverActions({{ $service['id'] }})">

        {{-- Power controls --}}
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Power Controls</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">Manage the power state of your server.</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {{-- Power On --}}
                <button @click="perform('poweron')"
                        :disabled="loading !== null"
                        class="btn btn-success flex-col gap-1.5 py-4 text-sm">
                    <template x-if="loading === 'poweron'">
                        <div class="spinner mx-auto"></div>
                    </template>
                    <template x-if="loading !== 'poweron'">
                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728M12 9v3m0 0v3m0-3h3m-3 0H9"/>
                        </svg>
                    </template>
                    <span>Power On</span>
                </button>

                {{-- Power Off --}}
                <button @click="perform('poweroff')"
                        :disabled="loading !== null"
                        class="btn btn-danger flex-col gap-1.5 py-4 text-sm">
                    <template x-if="loading === 'poweroff'">
                        <div class="spinner mx-auto"></div>
                    </template>
                    <template x-if="loading !== 'poweroff'">
                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </template>
                    <span>Power Off</span>
                </button>

                {{-- Reboot --}}
                <button @click="perform('reboot')"
                        :disabled="loading !== null"
                        class="btn btn-warning flex-col gap-1.5 py-4 text-sm">
                    <template x-if="loading === 'reboot'">
                        <div class="spinner mx-auto"></div>
                    </template>
                    <template x-if="loading !== 'reboot'">
                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </template>
                    <span>Reboot</span>
                </button>
            </div>
        </div>

        {{-- Reinstall --}}
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Reinstall OS</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">
                Reinstall the operating system on your server.
                <span class="font-semibold text-red-500 dark:text-red-400">All data will be erased.</span>
            </p>

            <div x-data="{ confirm: false }">
                <template x-if="!confirm">
                    <button @click="confirm = true"
                            :disabled="loading !== null"
                            class="btn btn-secondary border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Reinstall Server
                    </button>
                </template>
                <template x-if="confirm">
                    <div class="flex items-center gap-3 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-sm text-red-700 dark:text-red-400 flex-1 font-medium">Are you sure? This will erase all data!</p>
                        <div class="flex gap-2">
                            <button @click="confirm = false" class="btn btn-secondary text-xs px-3 py-1.5">Cancel</button>
                            <button @click="perform('reinstall'); confirm = false"
                                    :disabled="loading !== null"
                                    class="btn btn-danger text-xs px-3 py-1.5">
                                <template x-if="loading === 'reinstall'">
                                    <div class="spinner"></div>
                                </template>
                                <span x-show="loading !== 'reinstall'">Reinstall Now</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Change root password --}}
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Change Root Password</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">Update the root/administrator password for your server.</p>

            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative" x-data="{ show: false }">
                    <input :type="show ? 'text' : 'password'"
                           x-model="newPassword"
                           placeholder="New root password (min 8 characters)"
                           class="form-input pr-10">
                    <button type="button"
                            @click="show = !show"
                            class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
                <button @click="changePassword()"
                        :disabled="loading !== null || newPassword.length < 8"
                        class="btn btn-primary flex-shrink-0">
                    <template x-if="loading === 'changepassword'">
                        <div class="spinner"></div>
                    </template>
                    <svg x-show="loading !== 'changepassword'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <span x-show="loading !== 'changepassword'">Update Password</span>
                </button>
            </div>
        </div>

    </div>
</div>
@endsection
