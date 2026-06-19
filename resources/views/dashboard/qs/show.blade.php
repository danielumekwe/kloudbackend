@extends('layouts.app')
@section('title', $live['qs_hostname'] ?? 'Quick Server #' . $order->id)
@section('breadcrumb')
    <a href="{{ route('qs.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">My Quick Servers</a>
    <span class="mx-2">/</span>
    <span class="text-slate-700 dark:text-slate-200">{{ $live['qs_hostname'] ?? 'Quick Server #' . $order->id }}</span>
@endsection

@section('content')
<div x-data="qsManage({{ $order->id }})">

    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $live['qs_hostname'] ?? 'Quick Server #' . $order->id }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Quick Server
                @if($order->status === 'provisioned')
                    · {{ $live['qs_ip'] ?? '' }}
                @endif
            </p>
        </div>
        <span class="badge {{ $order->status === 'provisioned' ? 'badge-active' : ($order->status === 'failed' ? 'badge-suspended' : 'badge-pending') }}">
            {{ $order->status === 'provisioned' ? ($live['qs_status'] ?? 'Active') : str_replace('_', ' ', $order->status) }}
        </span>
    </div>

    @if($order->status === 'pending_payment')
        <div class="card mb-6 flex items-start gap-3 bg-yellow-50 dark:bg-yellow-500/10 border-yellow-200 dark:border-yellow-500/20">
            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">Awaiting payment</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">Your Quick Server will be provisioned automatically as soon as this invoice is paid.</p>
                <a href="{{ route('billing.show', $order->whmcs_invoice_id) }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline mt-2 inline-block">View invoice →</a>
            </div>
        </div>
    @elseif($order->status === 'failed')
        <div class="card mb-6 flex items-start gap-3 bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">Provisioning failed</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">{{ $order->failure_reason ?? 'Please contact support.' }}</p>
                <a href="{{ route('support.create') }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline mt-2 inline-block">Contact support →</a>
            </div>
        </div>
    @endif

    @if($order->status === 'provisioned')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            <div x-show="message" class="text-sm p-3 rounded-lg" :class="success ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400'" x-text="message"></div>

            {{-- Lifecycle --}}
            <div class="card">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Power Controls</h3>
                <div class="flex flex-wrap gap-3">
                    <button @click="run('start')" :disabled="busy" class="btn btn-success">Start</button>
                    <button @click="run('stop')" :disabled="busy" class="btn btn-secondary">Stop</button>
                    <button @click="run('restart')" :disabled="busy" class="btn btn-warning">Restart</button>
                </div>
            </div>

            {{-- Change root password --}}
            <div class="card">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Change Root Password</h3>
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[220px]">
                        <label class="form-label">New password</label>
                        <input type="password" x-model="newPassword" class="form-input" placeholder="At least 8 characters">
                    </div>
                    <button @click="run('changepassword', { password: newPassword })" :disabled="busy || newPassword.length < 8" class="btn btn-primary">Set Password</button>
                </div>
            </div>

            {{-- Reinstall OS --}}
            <div class="card">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Reinstall Operating System</h3>
                <p class="text-sm text-red-600 dark:text-red-400 mb-4">Warning: this destroys all data on the server. There is no rollback.</p>
                <div class="space-y-3">
                    <div>
                        <label class="form-label">Template</label>
                        <input type="text" x-model="reinstallTemplate" class="form-input" placeholder="e.g. ubuntu24">
                    </div>
                    <div>
                        <label class="form-label">Your InterServer account password</label>
                        <input type="password" x-model="localPassword" class="form-input" placeholder="Required to confirm this destructive action">
                    </div>
                    <button @click="confirmReinstall()" :disabled="busy || !reinstallTemplate || !localPassword" class="btn btn-danger">Reinstall</button>
                </div>
            </div>

            {{-- Advanced --}}
            <div class="card">
                <button type="button" class="flex items-center justify-between w-full" @click="advancedOpen = !advancedOpen">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Advanced</h3>
                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="advancedOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="advancedOpen" class="mt-5 space-y-6">

                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Backups</h4>
                        <div class="flex flex-wrap gap-3 mb-3">
                            <button @click="run('backup')" :disabled="busy" class="btn btn-secondary">Create Snapshot</button>
                        </div>
                        <div class="flex flex-wrap gap-3 items-end">
                            <div class="flex-1 min-w-[200px]">
                                <label class="form-label">Backup filename</label>
                                <input type="text" x-model="backupFile" class="form-input" placeholder="e.g. backup-2026-06-17.tar.gz">
                            </div>
                            <button @click="run('downloadbackup', { file: backupFile })" :disabled="busy || !backupFile" class="btn btn-secondary">Download Link</button>
                            <button @click="confirmDeleteBackup()" :disabled="busy || !backupFile" class="btn btn-danger">Delete</button>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Restore From Backup</h4>
                        <p class="text-sm text-red-600 dark:text-red-400 mb-3">Warning: this overwrites the current disk. There is no rollback.</p>
                        <div class="flex flex-wrap gap-3 items-end">
                            <div class="flex-1 min-w-[200px]">
                                <label class="form-label">Backup filename</label>
                                <input type="text" x-model="restoreBackup" class="form-input">
                            </div>
                            <div class="flex-1 min-w-[200px]">
                                <label class="form-label">Your InterServer account password</label>
                                <input type="password" x-model="restorePassword" class="form-input">
                            </div>
                            <button @click="confirmRestore()" :disabled="busy || !restoreBackup || !restorePassword" class="btn btn-danger">Restore</button>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Hostname</h4>
                        <div class="flex flex-wrap gap-3 items-end">
                            <div class="flex-1 min-w-[200px]">
                                <input type="text" x-model="hostname" class="form-input" placeholder="server.example.com">
                            </div>
                            <button @click="run('changehostname', { hostname: hostname })" :disabled="busy || !hostname" class="btn btn-primary">Update</button>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Timezone</h4>
                        <div class="flex flex-wrap gap-3 items-end">
                            <div class="flex-1 min-w-[200px]">
                                <input type="text" x-model="timezone" class="form-input" placeholder="e.g. America/New_York">
                            </div>
                            <button @click="run('changetimezone', { timezone: timezone })" :disabled="busy || !timezone" class="btn btn-primary">Update</button>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Webuzo Control Panel Password</h4>
                        <div class="flex flex-wrap gap-3 items-end">
                            <div class="flex-1 min-w-[200px]">
                                <input type="password" x-model="webuzoPassword" class="form-input" placeholder="At least 8 characters">
                            </div>
                            <button @click="run('changewebuzopassword', { password: webuzoPassword })" :disabled="busy || webuzoPassword.length < 8" class="btn btn-primary">Set Password</button>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Virtual CD / ISO</h4>
                        <div class="flex flex-wrap gap-3 mb-3">
                            <button @click="run('disablecd')" :disabled="busy" class="btn btn-secondary">Disable CD Device</button>
                            <button @click="run('ejectcd')" :disabled="busy" class="btn btn-secondary">Eject ISO</button>
                        </div>
                        <div class="flex flex-wrap gap-3 items-end">
                            <div class="flex-1 min-w-[220px]">
                                <label class="form-label">ISO URL</label>
                                <input type="text" x-model="isoUrl" class="form-input" placeholder="https://...">
                            </div>
                            <button @click="run('insertcd', { url: isoUrl })" :disabled="busy || !isoUrl" class="btn btn-primary">Mount ISO</button>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Disk Quota</h4>
                        <div class="flex flex-wrap gap-3">
                            <button @click="run('enablequota')" :disabled="busy" class="btn btn-secondary">Enable Quota</button>
                            <button @click="run('disablequota')" :disabled="busy" class="btn btn-secondary">Disable Quota</button>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Security</h4>
                        <div class="flex flex-wrap gap-3">
                            <button @click="run('blocksmtp')" :disabled="busy" class="btn btn-secondary">Block Outbound SMTP</button>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Remote Console</h4>
                        <div class="flex flex-wrap gap-3 items-end mb-3">
                            <div class="flex-1 min-w-[200px]">
                                <label class="form-label">Allowed source IP for VNC</label>
                                <input type="text" x-model="vncIp" class="form-input">
                            </div>
                            <button @click="run('setupvnc', { vnc: vncIp })" :disabled="busy || !vncIp" class="btn btn-primary">Configure VNC</button>
                        </div>
                        <button @click="run('viewdesktop')" :disabled="busy" class="btn btn-secondary">Refresh Remote Desktop</button>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Traffic Usage</h4>
                        <button @click="run('trafficusage')" :disabled="busy" class="btn btn-secondary">Check Bandwidth Usage</button>
                        <pre x-show="lastData" class="mt-3 text-xs bg-slate-50 dark:bg-slate-900/50 rounded-lg p-3 overflow-x-auto" x-text="lastData"></pre>
                    </div>
                </div>
            </div>
        </div>

        <div class="card h-fit">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Details</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Hostname</span><span class="font-medium text-slate-900 dark:text-white">{{ $live['qs_hostname'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">IP Address</span><span class="font-medium text-slate-900 dark:text-white">{{ $live['qs_ip'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">OS</span><span class="font-medium text-slate-900 dark:text-white">{{ $order->config['os'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Price</span><span class="font-medium text-slate-900 dark:text-white">${{ number_format($order->price, 2) }}/mo</span></div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function qsManage(orderId) {
    return {
        busy: false,
        message: '',
        success: false,
        lastData: '',
        advancedOpen: false,

        newPassword: '',
        reinstallTemplate: '',
        localPassword: '',
        backupFile: '',
        restoreBackup: '',
        restorePassword: '',
        hostname: '',
        timezone: '',
        webuzoPassword: '',
        isoUrl: '',
        vncIp: '',

        async run(command, extra = {}) {
            this.busy = true;
            this.message = '';
            try {
                const res = await fetch(`/qs/${orderId}/action`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ command, ...extra }),
                });
                const data = await res.json();
                this.success = data.success;
                this.message = data.message;
                this.lastData = data.data ? JSON.stringify(data.data, null, 2) : '';
                if (data.success && (command === 'changepassword' || command === 'changewebuzopassword')) this.newPassword = this.webuzoPassword = '';
            } catch (e) {
                this.success = false;
                this.message = 'Could not reach the server. Please try again.';
            } finally {
                this.busy = false;
            }
        },

        confirmReinstall() {
            if (!confirm('This will permanently erase all data on this server and reinstall the OS. This cannot be undone. Continue?')) return;
            this.run('reinstall', { template: this.reinstallTemplate, localPassword: this.localPassword });
        },

        confirmRestore() {
            if (!confirm('This will overwrite the current disk with the chosen backup. This cannot be undone. Continue?')) return;
            this.run('restore', { backup: this.restoreBackup, password: this.restorePassword });
        },

        confirmDeleteBackup() {
            if (!confirm(`Permanently delete backup "${this.backupFile}"? This cannot be undone.`)) return;
            this.run('deletebackup', { file: this.backupFile });
        },
    };
}
</script>
@endpush
