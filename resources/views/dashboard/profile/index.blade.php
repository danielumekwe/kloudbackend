@extends('layouts.app')
@section('title', 'Profile Settings')
@section('breadcrumb', 'Profile')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Profile Settings</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage your account details and security settings</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ----------------------------------------------------------------
         Left: Account summary card
    ----------------------------------------------------------------- --}}
    <div class="lg:col-span-1">
        <div class="card text-center">
            <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600
                        flex items-center justify-center text-white text-3xl font-bold mb-4 shadow-lg shadow-blue-500/20">
                {{ strtoupper(substr($client['firstname'] ?? 'U', 0, 1)) }}
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                {{ ($client['firstname'] ?? '') . ' ' . ($client['lastname'] ?? '') }}
            </h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ $client['email'] ?? '' }}</p>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-white/[0.06] space-y-2.5 text-sm text-left">
                <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    {{ $client['phonenumber'] ?? '—' }}
                </div>
                <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                    <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>
                        {{ $client['city'] ?? '' }}{{ !empty($client['city']) && !empty($client['country']) ? ', ' : '' }}{{ $client['country'] ?? '' }}
                    </span>
                </div>
                @if(!empty($client['credit_balance']) && (float)$client['credit_balance'] > 0)
                <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Credit: ${{ number_format((float)$client['credit_balance'], 2) }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------
         Right: Edit forms
    ----------------------------------------------------------------- --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Update profile --}}
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-5">Personal Information</h3>

            @if(session('success') && !str_contains(session('success'), 'Password'))
            <div class="mb-4 flex items-center gap-2 p-3 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20 text-sm text-green-700 dark:text-green-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error') && !str_contains(session('error') ?? '', 'password'))
            <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-sm text-red-700 dark:text-red-400">
                {{ session('error') }}
            </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}"
                  x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div class="space-y-4">
                    {{-- Name --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="firstname" class="form-label">First name</label>
                            <input id="firstname" name="firstname" type="text" required
                                   value="{{ old('firstname', $client['firstname'] ?? '') }}"
                                   class="form-input">
                        </div>
                        <div>
                            <label for="lastname" class="form-label">Last name</label>
                            <input id="lastname" name="lastname" type="text" required
                                   value="{{ old('lastname', $client['lastname'] ?? '') }}"
                                   class="form-input">
                        </div>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="form-label">Email address</label>
                        <input id="email" name="email" type="email" required
                               value="{{ old('email', $client['email'] ?? '') }}"
                               class="form-input">
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label for="phonenumber" class="form-label">Phone number</label>
                        <input id="phonenumber" name="phonenumber" type="tel" required
                               value="{{ old('phonenumber', $client['phonenumber'] ?? '') }}"
                               class="form-input">
                    </div>

                    {{-- Address --}}
                    <div>
                        <label for="address1" class="form-label">Street address</label>
                        <input id="address1" name="address1" type="text" required
                               value="{{ old('address1', $client['address1'] ?? '') }}"
                               class="form-input">
                    </div>

                    {{-- City / State / Postcode --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label for="city" class="form-label">City</label>
                            <input id="city" name="city" type="text" required
                                   value="{{ old('city', $client['city'] ?? '') }}"
                                   class="form-input">
                        </div>
                        <div>
                            <label for="state" class="form-label">State</label>
                            <input id="state" name="state" type="text" required
                                   value="{{ old('state', $client['state'] ?? '') }}"
                                   class="form-input">
                        </div>
                        <div>
                            <label for="postcode" class="form-label">Postcode</label>
                            <input id="postcode" name="postcode" type="text" required
                                   value="{{ old('postcode', $client['postcode'] ?? '') }}"
                                   class="form-input">
                        </div>
                    </div>

                    {{-- Country --}}
                    <div>
                        <label for="country" class="form-label">Country code</label>
                        <input id="country" name="country" type="text" required maxlength="2"
                               value="{{ old('country', $client['country'] ?? '') }}"
                               class="form-input uppercase w-24">
                        <p class="mt-1 text-xs text-slate-400">2-letter ISO code (e.g. US, GB, CA)</p>
                    </div>

                    <div class="pt-2">
                        <button type="submit" :disabled="loading" class="btn btn-primary">
                            <span x-show="!loading">Save Changes</span>
                            <span x-show="loading" class="flex items-center gap-2">
                                <div class="spinner"></div>
                                Saving…
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Change password --}}
        <div class="card">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Change Password</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">Update your account password. Minimum 8 characters.</p>

            @if(session('success') && str_contains(session('success'), 'Password'))
            <div class="mb-4 flex items-center gap-2 p-3 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20 text-sm text-green-700 dark:text-green-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
            @endif

            <form method="POST" action="{{ route('profile.password') }}"
                  x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="password" class="form-label">New password</label>
                        <input id="password" name="password" type="password" required minlength="8"
                               class="form-input" placeholder="At least 8 characters">
                        @error('password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="form-label">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                               class="form-input" placeholder="Repeat new password">
                    </div>
                    <div class="pt-2">
                        <button type="submit" :disabled="loading" class="btn btn-primary">
                            <span x-show="!loading" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Update Password
                            </span>
                            <span x-show="loading" class="flex items-center gap-2">
                                <div class="spinner"></div>
                                Updating…
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
