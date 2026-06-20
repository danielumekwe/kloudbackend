@extends('layouts.app')
@section('title', 'Open Support Ticket')
@section('breadcrumb')
    <a href="{{ route('support.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Support</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">New Ticket</span>
@endsection

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Open a Support Ticket</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Our team typically responds within 1-4 hours</p>
    </div>
    <a href="{{ route('support.index') }}" class="btn btn-secondary text-sm">← Back to Tickets</a>
</div>

<div class="max-w-2xl">
    <div class="card">
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

        <form method="POST" action="{{ route('support.store') }}"
              x-data="{ loading: false, charCount: 0 }"
              @submit="loading = true">
            @csrf

            <div class="space-y-5">
                {{-- Product --}}
                <div>
                    <label for="service" class="form-label">Product (optional)</label>
                    @if(empty($services))
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">You don't have any active services yet.</p>
                    @else
                        <select id="service" name="service" class="form-input">
                            <option value="">Not related to a specific service…</option>
                            @foreach($services as $svc)
                                @php($value = "{$svc['type']}:{$svc['id']}")
                                <option value="{{ $value }}" {{ old('service') === $value ? 'selected' : '' }}>
                                    {{ $svc['label'] }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                {{-- Department --}}
                <div>
                    <label for="deptid" class="form-label">Department</label>
                    @if(empty($departments))
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">No departments available. Please contact support directly.</p>
                        <input type="hidden" name="deptid" value="1">
                    @else
                        <select id="deptid" name="deptid" required class="form-input">
                            <option value="">Select a department…</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept['id'] }}" {{ old('deptid') == $dept['id'] ? 'selected' : '' }}>
                                    {{ $dept['name'] ?? 'Department ' . $dept['id'] }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                {{-- Priority --}}
                <div>
                    <label for="priority" class="form-label">Priority</label>
                    <select id="priority" name="priority" required class="form-input">
                        <option value="Low"    {{ old('priority', 'Medium') === 'Low'    ? 'selected' : '' }}>Low</option>
                        <option value="Medium" {{ old('priority', 'Medium') === 'Medium' ? 'selected' : '' }}>Medium</option>
                        <option value="High"   {{ old('priority', 'Medium') === 'High'   ? 'selected' : '' }}>High</option>
                    </select>
                </div>

                {{-- Subject --}}
                <div>
                    <label for="subject" class="form-label">Subject</label>
                    <input id="subject" name="subject" type="text" required
                           value="{{ old('subject') }}"
                           maxlength="200"
                           class="form-input"
                           placeholder="Brief description of your issue">
                </div>

                {{-- Message --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="message" class="form-label !mb-0">Message</label>
                        <span class="text-xs text-slate-400" x-text="charCount + ' characters'"></span>
                    </div>
                    <textarea id="message" name="message" required rows="8"
                              @input="charCount = $event.target.value.length"
                              class="form-input resize-y min-h-32"
                              placeholder="Please describe your issue in detail. Include any relevant information such as error messages, steps to reproduce, etc.">{{ old('message') }}</textarea>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Minimum 10 characters required.</p>
                </div>

                {{-- Submit --}}
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="loading" class="btn btn-primary">
                        <span x-show="!loading" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Submit Ticket
                        </span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <div class="spinner"></div>
                            Submitting…
                        </span>
                    </button>
                    <a href="{{ route('support.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
