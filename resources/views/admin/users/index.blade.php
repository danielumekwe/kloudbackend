@extends('layouts.admin')
@section('title', 'Admin Users')
@section('breadcrumb', 'Admin Users')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">Admin Users</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Manage admin accounts and roles.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add Admin</a>
</div>

@if(session('success'))
<div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
</div>
@endif

@if($errors->any())
<div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
    @foreach($errors->all() as $error)
        <p class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
    @endforeach
</div>
@endif

<div class="card !p-0 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-white/[0.03] text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            <tr>
                <th class="px-5 py-3">Email</th>
                <th class="px-5 py-3">Role</th>
                <th class="px-5 py-3">2FA</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.05]">
            @foreach($admins as $a)
            <tr>
                <td class="px-5 py-3 text-slate-900 dark:text-white">
                    {{ $a->email }}
                    @if((int) session('adminId') === $a->id)
                        <span class="ml-1 text-xs text-slate-400">(you)</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $a->role->label() }}</td>
                <td class="px-5 py-3">
                    @if($a->hasTwoFactorEnabled())
                        <span class="text-green-600 dark:text-green-400 text-xs font-medium">Enabled</span>
                    @else
                        <span class="text-slate-400 text-xs">Off</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-right space-x-3">
                    <a href="{{ route('admin.users.edit', $a) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium">Edit</a>
                    @if((int) session('adminId') !== $a->id)
                    <form method="POST" action="{{ route('admin.users.destroy', $a) }}" class="inline" onsubmit="return confirm('Delete this admin account?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm font-medium">Delete</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
