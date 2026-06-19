<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminPasswordResetMail;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    private const TOKEN_TTL_MINUTES = 60;

    public function showLogin(): View
    {
        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (! $admin || ! $admin->checkPassword($request->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        session()->regenerate();
        session(['isAdmin' => true, 'adminId' => $admin->id, 'adminEmail' => $admin->email]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['isAdmin', 'adminId', 'adminEmail']);

        return redirect()->route('admin.login');
    }

    public function showForgot(): View
    {
        return view('admin.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $admin = Admin::where('email', $request->email)->first();

        if ($admin) {
            $token = Str::random(64);

            DB::table('admin_password_reset_tokens')->updateOrInsert(
                ['email' => $admin->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $resetUrl = route('admin.password.reset', [
                'token' => $token,
                'email' => $admin->email,
            ]);

            Mail::to($admin->email)->send(new AdminPasswordResetMail($resetUrl));
        }

        // Same message whether or not the email matches an admin, so we don't leak it.
        return back()->with('status', 'If that email matches an admin account, we\'ve sent a password reset link.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('admin.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('admin_password_reset_tokens')->where('email', $request->email)->first();

        if (! $record
            || ! Hash::check($request->token, $record->token)
            || abs(now()->diffInMinutes($record->created_at)) > self::TOKEN_TTL_MINUTES
        ) {
            return back()->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        $admin = Admin::where('email', $request->email)->first();

        if (! $admin) {
            return back()->withErrors(['email' => 'We couldn\'t find an admin account for that email.']);
        }

        $admin->update(['password' => Hash::make($request->password)]);

        DB::table('admin_password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('admin.login')->with('success', 'The admin password has been reset. Please sign in.');
    }
}
