<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $adminPassword = config('services.admin.password');

        if (! $adminPassword || ! hash_equals($adminPassword, $request->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        session()->regenerate();
        session(['isAdmin' => true]);

        return redirect()->intended(route('admin.pricing'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('isAdmin');

        return redirect()->route('admin.login');
    }
}
