@extends('layouts.app')
@section('title', 'Order ' . $plan['label'])
@section('breadcrumb')
    <a href="{{ route('vps.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My VPS</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">Order {{ $plan['label'] }}</span>
@endsection

@section('content')

{{-- Step indicator --}}
<div class="mb-6">
    <div class="flex items-center gap-0 mb-4">
        <div class="flex items-center">
            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold shadow">1</div>
            <span class="ml-2 text-sm font-semibold text-blue-600">Configure</span>
        </div>
        <div class="flex-1 h-px bg-slate-200 dark:bg-white/10 mx-4 max-w-16"></div>
        <div class="flex items-center text-slate-400">
            <div class="w-8 h-8 rounded-full border-2 border-slate-300 dark:border-white/20 flex items-center justify-center text-sm font-bold">2</div>
            <span class="ml-2 text-sm font-medium">Review</span>
        </div>
        <div class="flex-1 h-px bg-slate-200 dark:bg-white/10 mx-4 max-w-16"></div>
        <div class="flex items-center text-slate-400">
            <div class="w-8 h-8 rounded-full border-2 border-slate-300 dark:border-white/20 flex items-center justify-center text-sm font-bold">3</div>
            <span class="ml-2 text-sm font-medium">Complete</span>
        </div>
    </div>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Order {{ $plan['label'] }}</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Configure your VPS and we'll create an invoice — your server is provisioned automatically once it's paid.</p>
</div>

@if($errors->any())
<div class="mb-5 flex items-start gap-3 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5"
     x-data="vpsOrder({
        templates: {{ json_encode($templates) }},
        osNames: {{ json_encode($osNames) }},
        locations: {{ json_encode($locations) }},
        stock: {{ json_encode($stock) }},
        periods: {{ json_encode($periods) }},
        controlpanelOptions: {{ json_encode($controlpanelOptions) }},
        currency: {{ json_encode($currency) }},
        platform: '{{ $plan['platform'] }}',
        category: '{{ $category }}',
        minSlices: {{ $minSlices }},
        maxSlices: {{ $maxSlices }},
        recommendedMinSlices: {{ $recommendedMinSlices }},
        pricePerSlice: {{ $pricePerSlice }},
     })">

    {{-- ── Main Configuration Form ─────────────────────────────────────── --}}
    <form id="vps-order-form" method="POST" action="{{ route('vps.store', $category) }}" class="lg:col-span-2 space-y-4" @submit="loading = true">
        @csrf
        <input type="hidden" name="osDistro"    :value="osDistro">
        <input type="hidden" name="osVersion"   :value="osVersion">
        <input type="hidden" name="location"    :value="location">
        <input type="hidden" name="period"      :value="period">
        <input type="hidden" name="controlpanel" :value="controlpanel">
        <input type="hidden" name="slices"      :value="slices">

        {{-- ── 1. Choose Location ──────────────────────────────────────── --}}
        <div class="card p-0 overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 dark:border-white/[0.05]">
                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">1</div>
                <h3 class="font-semibold text-slate-900 dark:text-white text-sm">Choose Location</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <template x-for="locId in Object.keys(locations)" :key="locId">
                        <button type="button"
                            @click="if (isLocationAvailable(locId)) { location = locId; quote(); }"
                            :disabled="!isLocationAvailable(locId)"
                            :class="{ 'is-selected shadow-sm': location == locId }"
                            class="sel-card relative flex items-center gap-3 px-4 py-3 rounded-xl border-2 text-left disabled:opacity-40 disabled:cursor-not-allowed">
                            <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate" x-text="locations[locId]"></div>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span :class="isLocationAvailable(locId) ? 'bg-green-500' : 'bg-slate-400'" class="w-1.5 h-1.5 rounded-full flex-shrink-0"></span>
                                    <span :class="isLocationAvailable(locId) ? 'text-green-600 dark:text-green-400' : 'text-slate-400'" class="text-xs font-medium" x-text="isLocationAvailable(locId) ? 'Available' : 'Unavailable'"></span>
                                </div>
                            </div>
                            <div x-show="location == locId" class="absolute top-2 right-2">
                                <div class="w-4 h-4 rounded-full bg-blue-600 flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- ── 2. Choose Your Plan ──────────────────────────────────────── --}}
        <div class="card p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-white/[0.05]">
                <div class="flex items-center gap-3">
                    <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">2</div>
                    <h3 class="font-semibold text-slate-900 dark:text-white text-sm">Choose Your Plan</h3>
                </div>
                <span class="text-xs text-slate-400" x-text="formatPrice(pricePerSlice) + ' per slice/mo'"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-white/[0.05] bg-slate-50 dark:bg-white/[0.02]">
                            <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide w-8"></th>
                            <th class="text-left px-3 py-2.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Plan</th>
                            <th class="text-left px-3 py-2.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                <svg class="w-3.5 h-3.5 inline mr-1 -mt-0.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582 4 8 4s8 1.79 8 4"/></svg>
                                Storage
                            </th>
                            <th class="text-left px-3 py-2.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                <svg class="w-3.5 h-3.5 inline mr-1 -mt-0.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                                RAM
                            </th>
                            <th class="text-left px-3 py-2.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                <svg class="w-3.5 h-3.5 inline mr-1 -mt-0.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Bandwidth
                            </th>
                            <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="n in sliceRange()" :key="n">
                            <tr @click="slices = n; quote()"
                                :class="{ 'is-selected': slices === n }"
                                class="plan-row border-b border-slate-100 dark:border-white/[0.04]">
                                <td class="px-5 py-3 text-center">
                                    <div class="w-4 h-4 rounded-full border-2 mx-auto flex items-center justify-center transition"
                                         :class="slices === n ? 'border-blue-600 bg-blue-600' : 'border-slate-300 dark:border-slate-600'">
                                        <div x-show="slices === n" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 font-medium text-slate-900">
                                    <span x-text="n + (n === 1 ? ' slice' : ' slices')"></span>
                                    <template x-if="n < minSlices">
                                        <span class="ml-2 text-xs text-slate-400">(not available)</span>
                                    </template>
                                </td>
                                <td class="px-3 py-3 text-slate-600" x-text="(n * sliceStorageGb) + ' GB'"></td>
                                <td class="px-3 py-3 text-slate-600" x-text="(n * sliceRamGb) + ' GB'"></td>
                                <td class="px-3 py-3 text-slate-600" x-text="(n * sliceBandwidthGb).toLocaleString() + ' GB'"></td>
                                <td class="px-5 py-3 text-right">
                                    <span class="font-semibold" :class="slices === n ? 'text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300'" x-text="formatPrice(n * pricePerSlice)"></span>
                                    <span class="text-xs text-slate-400">/mo</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="minSlices === 1 && recommendedMinSlices > 1" class="mx-4 mb-4 mt-1 flex items-start gap-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-500/30">
                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-amber-800 dark:text-amber-300">Managed Windows VPS starts from <span x-text="recommendedMinSlices"></span> slices — fewer slices may have limited availability.</p>
            </div>
        </div>

        {{-- ── 3. Operating System ──────────────────────────────────────── --}}
        <div class="card p-0 overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 dark:border-white/[0.05]">
                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">3</div>
                <h3 class="font-semibold text-slate-900 dark:text-white text-sm">Operating System</h3>
            </div>
            <div class="p-4 space-y-4">
                {{-- OS tab strip --}}
                <div class="flex border border-slate-200 dark:border-white/10 rounded-lg overflow-hidden w-fit">
                    <button type="button" @click="switchTab('templates')"
                        :class="osTab === 'templates' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5'"
                        class="px-4 py-2 text-sm font-medium flex items-center gap-2 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                        </svg>
                        OS Templates
                    </button>
                    <button type="button" @click="switchTab('marketplace')"
                        :class="osTab === 'marketplace' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5'"
                        class="px-4 py-2 text-sm font-medium flex items-center gap-2 transition border-l border-slate-200 dark:border-white/10">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        Marketplace Apps
                        <span class="ml-0.5 px-1.5 py-0.5 text-xs rounded-full font-bold"
                              :class="osTab === 'marketplace' ? 'bg-white/20 text-white' : 'bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-300'"
                              x-text="marketplaceApps().length"></span>
                    </button>
                </div>

                {{-- OS templates grid --}}
                <div x-show="osTab === 'templates'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <template x-for="os in osTemplateDistros()" :key="os">
                        <button type="button"
                            @click="selectOsTemplate(os)"
                            :class="{ 'is-selected': osDistro === os }"
                            class="sel-card relative flex flex-col items-center gap-2 px-3 py-4 rounded-xl border-2 text-center">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden shadow-sm"
                                 :class="osIconUrl(os) ? 'bg-white dark:bg-slate-700 border border-slate-200 dark:border-white/10 p-1.5' : ''"
                                 :style="osIconUrl(os) ? '' : 'background-color: ' + osColor(os)">
                                <template x-if="osIconUrl(os)">
                                    <img :src="osIconUrl(os)" :alt="os" class="w-full h-full object-contain">
                                </template>
                                <template x-if="!osIconUrl(os)">
                                    <span class="text-white text-base font-bold" x-text="osIcon(os)"></span>
                                </template>
                            </div>
                            <span class="text-xs font-medium leading-tight" x-text="osNames[os] || os"></span>
                            <div x-show="osDistro === os" class="absolute top-1.5 right-1.5">
                                <div class="w-4 h-4 rounded-full bg-blue-600 flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>

                {{-- Version selector (OS templates only) --}}
                <div x-show="osTab === 'templates'">
                    <label class="form-label flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        Version
                    </label>
                    <select x-model="osVersion" @change="quote()" class="form-input">
                        <template x-for="(version, file) in (templates[osDistro] || {})" :key="file">
                            <option :value="file" x-text="version"></option>
                        </template>
                    </select>
                </div>

                {{-- Marketplace Apps grid --}}
                <div x-show="osTab === 'marketplace'" class="space-y-3">
                    {{-- Search --}}
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="appSearch" @input="appPage = 1" placeholder="Search apps — WordPress, Docker, cPanel…"
                               class="form-input pl-9 w-full">
                        <button type="button" x-show="appSearch" @click="appSearch = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- App cards grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        <template x-for="app in paginatedApps()" :key="app.key">
                            <button type="button"
                                @click="selectMarketplaceApp(app.key)"
                                :class="{ 'is-selected': osVersion === app.key && osDistro === 'cloudinit' }"
                                class="sel-card relative flex items-center gap-3 px-3 py-2.5 rounded-xl border-2 text-left">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden"
                                     :class="appIconUrl(app.label) ? 'bg-white dark:bg-slate-700 border border-slate-200 dark:border-white/10 p-1' : 'shadow-sm'"
                                     :style="appIconUrl(app.label) ? '' : 'background-color: ' + appColor(app.label)">
                                    <template x-if="appIconUrl(app.label)">
                                        <img :src="appIconUrl(app.label)" :alt="app.label" class="w-full h-full object-contain">
                                    </template>
                                    <template x-if="!appIconUrl(app.label)">
                                        <span class="text-white text-sm font-bold" x-text="appIcon(app.label)"></span>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-semibold truncate" x-text="app.label"></div>
                                    <div class="text-xs opacity-70 truncate" x-text="app.base"></div>
                                </div>
                                <div x-show="osVersion === app.key && osDistro === 'cloudinit'" class="flex-shrink-0">
                                    <div class="w-4 h-4 rounded-full bg-blue-600 flex items-center justify-center">
                                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </button>
                        </template>
                        <div x-show="filteredApps().length === 0" class="col-span-3 py-8 text-center text-sm text-slate-400">
                            No apps match "<span x-text="appSearch"></span>"
                        </div>
                    </div>

                    {{-- Pagination --}}
                    <div x-show="totalAppPages() > 1" class="flex items-center justify-center gap-3 pt-3 border-t border-slate-100 dark:border-white/[0.05]">
                        <button type="button" @click="if (appPage > 1) appPage--" :disabled="appPage <= 1"
                                class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 dark:border-white/10 disabled:opacity-40 hover:bg-slate-50 dark:hover:bg-white/5 transition text-slate-500 dark:text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            Page <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="appPage"></span>
                            / <span x-text="totalAppPages()"></span>
                            &middot; <span x-text="filteredApps().length"></span> apps
                        </span>
                        <button type="button" @click="if (appPage < totalAppPages()) appPage++" :disabled="appPage >= totalAppPages()"
                                class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 dark:border-white/10 disabled:opacity-40 hover:bg-slate-50 dark:hover:bg-white/5 transition text-slate-500 dark:text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    {{-- Selected app summary --}}
                    <div x-show="osDistro === 'cloudinit' && osVersion" class="flex items-center gap-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-500/20 border border-blue-200 dark:border-blue-500/20 text-sm">
                        <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-blue-800 dark:text-blue-200 font-medium">Selected: </span>
                        <span class="text-blue-700 dark:text-blue-300" x-text="appLabel(osVersion)"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 4. Control Panel (managed VPS only) ─────────────────────── --}}
        <div x-show="hasControlpanelOptions()" class="card p-0 overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 dark:border-white/[0.05]">
                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">4</div>
                <h3 class="font-semibold text-slate-900 dark:text-white text-sm">Control Panel / License</h3>
            </div>
            <div class="p-4 space-y-3">
                <template x-for="key in Object.keys(controlpanelOptions)" :key="key">
                    <button type="button"
                        @click="controlpanel = key; quote()"
                        :class="{ 'is-selected': controlpanel === key }"
                        class="sel-card relative w-full flex items-start gap-3 px-4 py-3 rounded-xl border-2 text-left">
                        <div class="w-4 h-4 rounded-full border-2 mt-0.5 flex items-center justify-center flex-shrink-0 transition"
                             :class="controlpanel === key ? 'border-blue-600 bg-blue-600' : 'border-slate-300 dark:border-slate-600'">
                            <div x-show="controlpanel === key" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-sm" x-text="controlpanelOptions[key].label"></span>
                                <span class="text-sm font-semibold text-blue-600 dark:text-blue-400" x-text="'+' + formatPrice(controlpanelOptions[key].price) + '/mo'"></span>
                            </div>
                            <ul x-show="(controlpanelOptions[key]?.features || []).length" class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5">
                                <template x-for="feat in (controlpanelOptions[key]?.features || [])" :key="feat">
                                    <li class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                        <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span x-text="feat"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        {{-- ── 5. Billing Cycle ─────────────────────────────────────────── --}}
        <div class="card p-0 overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 dark:border-white/[0.05]">
                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0"
                     x-text="hasControlpanelOptions() ? '5' : '4'"></div>
                <h3 class="font-semibold text-slate-900 dark:text-white text-sm">Billing Cycle</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                    <template x-for="(p, months) in periods" :key="months">
                        <button type="button"
                            @click="period = parseInt(months); quote()"
                            :class="{ 'is-selected': period === parseInt(months) }"
                            class="sel-card flex flex-col items-center px-3 py-2.5 rounded-xl border-2 text-center">
                            <span class="text-xs font-semibold" x-text="p.label"></span>
                            <span class="text-sm font-bold mt-0.5" x-text="formatPrice(priceForPeriod(parseInt(months), p.discount))"></span>
                            <template x-if="p.discount < 1">
                                <span class="text-xs text-green-600 dark:text-green-400 font-medium" x-text="'Save ' + Math.round((1 - p.discount) * 100) + '%'"></span>
                            </template>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- ── 6. Identity & Security ───────────────────────────────────── --}}
        <div class="card p-0 overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 dark:border-white/[0.05]">
                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0"
                     x-text="hasControlpanelOptions() ? '6' : '5'"></div>
                <h3 class="font-semibold text-slate-900 dark:text-white text-sm">Identity &amp; Security</h3>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="form-label flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        Hostname
                    </label>
                    <input type="text" name="hostname" x-model="hostname" @blur="quote()"
                           placeholder="server.example.com"
                           class="form-input">
                    <p class="text-xs text-slate-400 mt-1">Must be a full hostname with at least two dots, e.g. <code class="bg-slate-100 dark:bg-white/10 px-1 py-0.5 rounded text-xs">server.example.com</code></p>
                </div>

                <div>
                    <label class="form-label flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Root Password
                    </label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input :type="showPassword ? 'text' : 'password'"
                                   name="rootpass"
                                   x-model="rootpass"
                                   placeholder="At least 8 chars, mixed case + number + symbol"
                                   class="form-input pr-10 w-full">
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        <button type="button" @click="rootpass = generateStrongPassword(); showPassword = true"
                                class="btn btn-secondary whitespace-nowrap text-sm">
                            Generate
                        </button>
                    </div>
                    <div x-show="rootpass" class="mt-2 flex gap-1">
                        <template x-for="i in 4" :key="i">
                            <div class="h-1 flex-1 rounded-full transition-colors duration-300"
                                 :class="passwordStrength() >= i ? strengthColor() : 'bg-slate-200 dark:bg-white/10'"></div>
                        </template>
                    </div>
                </div>

                <div x-show="quoteError" class="flex items-start gap-2 p-3 rounded-lg bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
                    <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm text-red-700 dark:text-red-400" x-text="quoteError"></p>
                </div>
            </div>
        </div>

    </form>

    {{-- ── Order Summary Sidebar ────────────────────────────────────────── --}}
    <div class="space-y-4">
        <div class="card p-0 overflow-hidden sticky top-6">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-white/[0.05]">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h3 class="font-semibold text-slate-900 dark:text-white text-sm">Order Summary</h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400" x-text="'{{ $plan['label'] }}'"></p>
            </div>

            {{-- Spec rows --}}
            <div class="divide-y divide-slate-100 dark:divide-white/[0.05]">
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582 4 8 4s8 1.79 8 4"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide font-medium">Storage</div>
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="(slices * sliceStorageGb) + ' GB'"></div>
                    </div>
                </div>
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide font-medium">Memory</div>
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="(slices * sliceRamGb * 1024) + ' MB'"></div>
                    </div>
                </div>
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide font-medium">Transfer</div>
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="(slices * sliceBandwidthGb).toLocaleString() + ' GB'"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide font-medium">Billing cycle</span>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="periods[period]?.label || '—'"></span>
                </div>
                <div x-show="hasControlpanelOptions()" class="flex items-center justify-between px-5 py-3">
                    <span class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide font-medium">Control Panel</span>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="controlpanelOptions[controlpanel]?.label || '—'"></span>
                </div>
            </div>

            {{-- Total --}}
            <div class="px-5 py-4 border-t border-slate-100 dark:border-white/[0.05]">
                <div class="flex items-baseline justify-between mb-4">
                    <span class="text-sm font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide">Total</span>
                    <div class="text-right">
                        <template x-if="quoting">
                            <span class="text-sm text-slate-400 animate-pulse">calculating…</span>
                        </template>
                        <template x-if="!quoting && price !== null">
                            <div>
                                <span class="text-2xl font-bold text-slate-900 dark:text-white" x-text="formatPrice(price)"></span>
                                <span class="text-sm text-slate-400 ml-1">/ <span x-text="period === 1 ? 'mo' : periods[period]?.label?.toLowerCase() || 'mo'"></span></span>
                            </div>
                        </template>
                        <template x-if="!quoting && price === null">
                            <div>
                                <span class="text-2xl font-bold text-slate-900 dark:text-white" x-text="formatPrice(priceForPeriod(period, periods[period]?.discount ?? 1))"></span>
                                <span class="text-sm text-slate-400 ml-1">/ <span x-text="period === 1 ? 'mo' : periods[period]?.label?.toLowerCase() || 'mo'"></span></span>
                            </div>
                        </template>
                    </div>
                </div>
                <button type="submit" form="vps-order-form"
                        :disabled="loading"
                        class="btn btn-primary w-full justify-center text-sm py-3">
                    <span x-show="!loading">Continue</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <div class="spinner"></div>
                        Creating invoice…
                    </span>
                </button>
                <p class="text-xs text-center text-slate-400 dark:text-slate-500 mt-3 leading-relaxed">An invoice will be created and your server provisioned automatically once paid.</p>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function vpsOrder(opts) {
    return {
        templates: opts.templates,
        osNames: opts.osNames,
        locations: opts.locations,
        stock: opts.stock,
        periods: opts.periods,
        controlpanelOptions: opts.controlpanelOptions,
        currency: opts.currency,
        platform: opts.platform,
        category: opts.category,
        minSlices: opts.minSlices,
        maxSlices: opts.maxSlices,
        recommendedMinSlices: opts.recommendedMinSlices,
        pricePerSlice: opts.pricePerSlice,

        sliceStorageGb: 40,
        sliceRamGb: 2,
        sliceBandwidthGb: 2000,

        osDistro: '',
        osVersion: '',
        location: '',
        slices: opts.minSlices,
        period: 1,
        hostname: '',
        rootpass: '',
        showPassword: false,
        controlpanel: Object.keys(opts.controlpanelOptions)[0] || '',
        osTab: 'templates',
        appSearch: '',
        appPage: 1,
        appsPerPage: 25,

        price: null,
        quoting: false,
        quoteError: '',
        loading: false,

        get priceReady() { return this.price !== null && !this.quoteError; },

        init() {
            // Default to first non-cloudinit, non-none distro
            const first = this.osTemplateDistros()[0] || '';
            this.osDistro = first;
            this.osVersion = Object.keys(this.templates[this.osDistro] || {})[0] || '';
            const avail = this.availableLocations();
            if (avail.length) this.location = avail[0];
        },

        osTemplateDistros() {
            return Object.keys(this.templates).filter(k => k !== 'cloudinit' && k !== 'none');
        },

        marketplaceApps() {
            const raw = this.templates['cloudinit'] || {};
            return Object.entries(raw).map(([key, fullLabel]) => {
                const label = fullLabel.replace(/^(?:Ubuntu \d+(?:\.\d+)?|Debian \d+) \+ /, '');
                const baseMatch = fullLabel.match(/^(Ubuntu \d+(?:\.\d+)?|Debian \d+)/);
                return { key, label, fullLabel, base: baseMatch ? baseMatch[1] : '' };
            }).sort((a, b) => a.label.localeCompare(b.label));
        },

        filteredApps() {
            const q = this.appSearch.trim().toLowerCase();
            if (!q) return this.marketplaceApps();
            return this.marketplaceApps().filter(a => a.label.toLowerCase().includes(q) || a.base.toLowerCase().includes(q));
        },

        paginatedApps() {
            const start = (this.appPage - 1) * this.appsPerPage;
            return this.filteredApps().slice(start, start + this.appsPerPage);
        },

        totalAppPages() {
            return Math.max(1, Math.ceil(this.filteredApps().length / this.appsPerPage));
        },

        appLabel(key) {
            const apps = this.marketplaceApps();
            const found = apps.find(a => a.key === key);
            return found ? found.label : key;
        },

        selectOsTemplate(os) {
            this.osDistro = os;
            this.osVersion = Object.keys(this.templates[os] || {})[0] || '';
            this.quote();
        },

        selectMarketplaceApp(key) {
            this.osDistro = 'cloudinit';
            this.osVersion = key;
            this.quote();
        },

        switchTab(tab) {
            this.osTab = tab;
            if (tab === 'templates' && this.osDistro === 'cloudinit') {
                // Reset to first OS template when switching away from marketplace
                const first = this.osTemplateDistros()[0] || '';
                this.osDistro = first;
                this.osVersion = Object.keys(this.templates[first] || {})[0] || '';
                this.quote();
            }
        },

        onOsChange() {
            this.osVersion = Object.keys(this.templates[this.osDistro] || {})[0] || '';
            this.quote();
        },

        availableLocations() {
            return Object.keys(this.locations).filter(id => this.stock[id]?.[this.platform]);
        },

        isLocationAvailable(locId) {
            return !!this.stock[locId]?.[this.platform];
        },

        hasControlpanelOptions() {
            return Object.keys(this.controlpanelOptions).length > 0;
        },

        sliceRange() {
            const rows = [];
            for (let i = this.minSlices; i <= this.maxSlices; i++) rows.push(i);
            return rows;
        },

        formatPrice(amount) {
            if (this.currency.prefix) return this.currency.prefix + Number(amount).toFixed(2);
            if (this.currency.suffix) return Number(amount).toFixed(2) + ' ' + this.currency.suffix;
            return this.currency.code + ' ' + Number(amount).toFixed(2);
        },

        priceForPeriod(months, discount) {
            const cpPrice = this.controlpanelOptions[this.controlpanel]?.price || 0;
            return (this.slices * this.pricePerSlice + cpPrice) * months * discount;
        },

        osColor(os) {
            const map = {
                ubuntu:     '#E95420',
                debian:     '#d70a53',
                almalinux:  '#0f4266',
                centos:     '#932279',
                fedora:     '#3c6eb4',
                rocky:      '#10B981',
                arch:       '#1793d1',
                windows:    '#0078d4',
                hyperv:     '#0078d4',
                freebsd:    '#AB0000',
                openbsd:    '#FFD700',
            };
            const lc = os.toLowerCase();
            for (const [key, color] of Object.entries(map)) {
                if (lc.includes(key)) return color;
            }
            return '#6366f1';
        },

        osIcon(os) {
            const lc = os.toLowerCase();
            if (lc.includes('ubuntu'))    return 'U';
            if (lc.includes('debian'))    return 'D';
            if (lc.includes('almalinux')) return 'A';
            if (lc.includes('centos'))    return 'C';
            if (lc.includes('fedora'))    return 'F';
            if (lc.includes('rocky'))     return 'R';
            if (lc.includes('arch'))      return 'A';
            if (lc.includes('windows'))   return '⊞';
            if (lc.includes('freebsd'))   return 'B';
            return os.charAt(0).toUpperCase();
        },

        appColor(label) {
            const lc = label.toLowerCase();
            if (lc.includes('wordpress') || lc.includes('woocommerce')) return '#21759b';
            if (lc.includes('docker') || lc.includes('coolify') || lc.includes('portainer')) return '#2496ed';
            if (lc.includes('nextcloud') || lc.includes('nirvashare') || lc.includes('filecloud') || lc.includes('immich')) return '#0082c9';
            if (lc.includes('laravel') || lc.includes('php') || lc.includes('lamp') || lc.includes('lemp')) return '#FF2D20';
            if (lc.includes('nodejs') || lc.includes('node.js') || lc.includes('bun') || lc.includes('strapi') || lc.includes('supabase')) return '#339933';
            if (lc.includes('grafana') || lc.includes('prometheus') || lc.includes('signoz')) return '#F46800';
            if (lc.includes('minecraft')) return '#62A14F';
            if (lc.includes('mongodb') || lc.includes('elasticsearch')) return '#13AA52';
            if (lc.includes('cyberpanel') || lc.includes('cloudpanel') || lc.includes('cpanel') || lc.includes('webmin') || lc.includes('fastpanel') || lc.includes('easypanel') || lc.includes('serverwand') || lc.includes('swpanel') || lc.includes('n99panel') || lc.includes('cloudron')) return '#7C3AED';
            if (lc.includes('vpn') || lc.includes('wireguard') || lc.includes('pritunl') || lc.includes('netbird') || lc.includes('openvpn')) return '#1D4ED8';
            if (lc.includes('drupal')) return '#0678BE';
            if (lc.includes('joomla')) return '#F44321';
            if (lc.includes('prestashop')) return '#DF0067';
            if (lc.includes('odoo')) return '#714B67';
            if (lc.includes('onlyoffice')) return '#FF6F3D';
            if (lc.includes('gitea') || lc.includes('forgejo')) return '#609926';
            if (lc.includes('uptime') || lc.includes('statusnook')) return '#5CDD8B';
            if (lc.includes('azuracast')) return '#2F3C7E';
            if (lc.includes('bitnami')) return '#1E55B8';
            if (lc.includes('asp.net') || lc.includes('aspnet')) return '#512BD4';
            if (lc.includes('k3s') || lc.includes('kubernetes')) return '#326CE5';
            if (lc.includes('ant media')) return '#E53935';
            if (lc.includes('openclaw')) return '#4CAF50';
            if (lc.includes('mediaWiki') || lc.includes('mediawiki')) return '#014F73';
            if (lc.includes('flarum')) return '#4D698E';
            if (lc.includes('microweber')) return '#FF7043';
            return '#6366f1';
        },

        appIcon(label) {
            const lc = label.toLowerCase();
            if (lc.includes('wordpress') || lc.includes('woocommerce')) return 'W';
            if (lc.includes('docker'))      return '🐳';
            if (lc.includes('nextcloud'))   return '☁';
            if (lc.includes('minecraft'))   return '⛏';
            if (lc.includes('nodejs') || lc.includes('node.js')) return 'N';
            if (lc.includes('mongodb'))     return 'M';
            if (lc.includes('elasticsearch')) return 'E';
            if (lc.includes('grafana'))     return 'G';
            if (lc.includes('wireguard') || lc.includes('vpn') || lc.includes('openvpn') || lc.includes('pritunl') || lc.includes('netbird')) return '🔒';
            if (lc.includes('laravel'))     return 'L';
            if (lc.includes('drupal'))      return 'D';
            if (lc.includes('joomla'))      return 'J';
            if (lc.includes('supabase'))    return 'S';
            if (lc.includes('k3s') || lc.includes('kubernetes')) return 'K';
            return label.charAt(0).toUpperCase();
        },

        osIconUrl(os) {
            const lc = os.toLowerCase();
            const si = (slug, color) => `https://cdn.simpleicons.org/${slug}/${color}`;
            if (lc.includes('ubuntu'))    return si('ubuntu',     'E95420');
            if (lc.includes('debian'))    return si('debian',     'A81D33');
            if (lc.includes('almalinux')) return si('almalinux',  '0F4266');
            if (lc.includes('centos'))    return si('centos',     '262577');
            if (lc.includes('fedora'))    return si('fedora',     '51A2DA');
            if (lc.includes('rocky'))     return si('rockylinux', '10B981');
            if (lc.includes('arch'))      return si('archlinux',  '1793D1');
            if (lc.includes('windows'))   return si('windows',    '0078D4');
            if (lc.includes('freebsd'))   return si('freebsd',    'AB2B28');
            return null;
        },

        appIconUrl(label) {
            const lc = label.toLowerCase();
            const si = (slug, color) => `https://cdn.simpleicons.org/${slug}/${color}`;
            if (lc.includes('woocommerce'))  return si('woocommerce',   '96588A');
            if (lc.includes('wordpress'))    return si('wordpress',     '21759B');
            if (lc.includes('coolify'))      return si('coolify',       '6C47FF');
            if (lc.includes('portainer'))    return si('portainer',     '13BEF9');
            if (lc.includes('docker'))       return si('docker',        '2496ED');
            if (lc.includes('nextcloud'))    return si('nextcloud',     '0082C9');
            if (lc.includes('laravel'))      return si('laravel',       'FF2D20');
            if (lc.includes('node.js') || lc.includes('nodejs')) return si('nodedotjs', '339933');
            if (lc.includes('mongodb'))      return si('mongodb',       '47A248');
            if (lc.includes('grafana'))      return si('grafana',       'F46800');
            if (lc.includes('joomla'))       return si('joomla',        'F44321');
            if (lc.includes('drupal'))       return si('drupal',        '0678BE');
            if (lc.includes('kubernetes') || lc.includes('k3s')) return si('kubernetes', '326CE5');
            if (lc.includes('elasticsearch')) return si('elasticsearch', '005571');
            if (lc.includes('forgejo'))      return si('forgejo',       'FB923C');
            if (lc.includes('gitea'))        return si('gitea',         '609926');
            if (lc.includes('prestashop'))   return si('prestashop',    'DF0067');
            if (lc.includes('odoo'))         return si('odoo',          '714B67');
            if (lc.includes('onlyoffice'))   return si('onlyoffice',    'FF6F3D');
            if (lc.includes('supabase'))     return si('supabase',      '3ECF8E');
            if (lc.includes('netbird'))      return si('netbird',       '00B4B6');
            if (lc.includes('wireguard'))    return si('wireguard',     '88171A');
            if (lc.includes('strapi'))       return si('strapi',        '4945FF');
            if (lc.includes('flarum'))       return si('flarum',        '4D698E');
            if (lc.includes('mediawiki'))    return si('mediawiki',     '000000');
            if (lc.includes('minecraft'))    return si('minecraft',     '62A14F');
            if (lc.includes('cpanel'))       return si('cpanel',        'FF6C2C');
            if (lc.includes('plesk'))        return si('plesk',         '52BBE6');
            if (lc.includes('prometheus'))   return si('prometheus',    'E6522C');
            if (lc.includes('uptime kuma')) return si('uptimekuma',     '5CDD8B');
            if (lc.includes('directadmin')) return si('directadmin',    '2089C7');
            if (lc.includes('signoz'))       return si('signoz',        'F8542A');
            return null;
        },

        passwordStrength() {
            const p = this.rootpass;
            if (!p) return 0;
            let score = 0;
            if (p.length >= 8)  score++;
            if (p.length >= 12) score++;
            if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++;
            if (/[0-9]/.test(p) && /[^A-Za-z0-9]/.test(p)) score++;
            return score;
        },

        strengthColor() {
            const s = this.passwordStrength();
            if (s <= 1) return 'bg-red-500';
            if (s === 2) return 'bg-amber-500';
            if (s === 3) return 'bg-yellow-400';
            return 'bg-green-500';
        },

        generateStrongPassword() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%^&*';
            let pass = '';
            for (let i = 0; i < 16; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
            return pass;
        },

        async quote() {
            if (!this.hostname || !/^.*\..*\..*$/.test(this.hostname)) {
                this.price = null;
                return;
            }
            this.quoting = true;
            this.quoteError = '';
            try {
                const res = await fetch('{{ route('vps.quote') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        category:     this.category,
                        osDistro:     this.osDistro,
                        osVersion:    this.osVersion,
                        slices:       this.slices,
                        location:     this.location,
                        period:       this.period,
                        hostname:     this.hostname,
                        controlpanel: this.controlpanel,
                    }),
                });
                const data = await res.json();
                if (res.ok) {
                    this.price = data.price;
                } else {
                    this.price = null;
                    this.quoteError = data.error || 'This configuration is not available.';
                }
            } catch (e) {
                this.price = null;
                this.quoteError = 'Could not reach the server. Please try again.';
            } finally {
                this.quoting = false;
            }
        },
    };
}
</script>
@endpush
