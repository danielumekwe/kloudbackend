@extends('layouts.admin')
@section('title', 'Add Admin')
@section('breadcrumb', 'Admin Users')

@section('content')
<h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">Add Admin</h1>
<p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Create a new admin account and assign a role.</p>

@if($errors->any())
<div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20">
    @foreach($errors->all() as $error)
        <p class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
    @endforeach
</div>
@endif

<form method="POST" action="{{ route('admin.users.store') }}" class="card max-w-lg space-y-5">
    @csrf

    <div>
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="form-input" placeholder="staff@example.com">
    </div>

    <div>
        <label for="password" class="form-label">Password</label>
        <input id="password" name="password" type="password" required minlength="8" class="form-input" placeholder="At least 8 characters">
    </div>

    <div>
        <label for="password_confirmation" class="form-label">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" class="form-input">
    </div>

    <div>
        <label for="role" class="form-label">Role</label>
        <select id="role" name="role" required class="form-input">
            @foreach($roles as $role)
                <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Create Admin</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
