<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'admins' => Admin::orderBy('email')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', ['roles' => AdminRole::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', Rule::in(array_column(AdminRole::cases(), 'value'))],
        ]);

        Admin::create([
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Admin account created.');
    }

    public function edit(Admin $admin): View
    {
        return view('admin.users.edit', ['admin' => $admin, 'roles' => AdminRole::cases()]);
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_column(AdminRole::cases(), 'value'))],
        ]);

        if ((int) session('adminId') === $admin->id && $validated['role'] !== AdminRole::SuperAdmin->value) {
            return back()->withErrors(['role' => 'You cannot remove your own Super Admin role.']);
        }

        if ($admin->role === AdminRole::SuperAdmin
            && $validated['role'] !== AdminRole::SuperAdmin->value
            && $this->wouldRemoveLastSuperAdmin($admin)
        ) {
            return back()->withErrors(['role' => 'At least one Super Admin must remain.']);
        }

        $admin->update(['role' => $validated['role']]);

        return redirect()->route('admin.users.index')->with('success', 'Role updated.');
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        if ((int) session('adminId') === $admin->id) {
            return back()->withErrors(['email' => 'You cannot delete your own account.']);
        }

        if ($admin->role === AdminRole::SuperAdmin && $this->wouldRemoveLastSuperAdmin($admin)) {
            return back()->withErrors(['email' => 'At least one Super Admin must remain.']);
        }

        $admin->delete();

        return redirect()->route('admin.users.index')->with('success', 'Admin account deleted.');
    }

    private function wouldRemoveLastSuperAdmin(Admin $excluding): bool
    {
        return Admin::where('role', AdminRole::SuperAdmin->value)
            ->where('id', '!=', $excluding->id)
            ->doesntExist();
    }
}
