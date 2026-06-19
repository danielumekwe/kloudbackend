@extends('layouts.admin')
@section('title', 'Edit Admin')
@section('breadcrumb', 'Admin Users')

@section('content')
<h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">Edit Admin</h1>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-6">{{ $admin->email }}</p>

@if($errors->any())
<div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
    @foreach($errors->all() as $error)
        <p class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
    @endforeach
</div>
@endif

<form method="POST" action="{{ route('admin.users.update', $admin) }}" class="card max-w-lg space-y-5">
    @csrf
    @method('PUT')

    <div>
        <label for="role" class="form-label">Role</label>
        <select id="role" name="role" required class="form-input">
            @foreach($roles as $role)
                <option value="{{ $role->value }}" {{ old('role', $admin->role->value) === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
