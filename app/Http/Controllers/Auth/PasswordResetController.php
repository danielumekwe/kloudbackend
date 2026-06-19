<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    private const TOKEN_TTL_MINUTES = 60;

    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $client = Client::where('email', $request->email)->first();

        if ($client) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $client->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $client->email,
            ]);

            Mail::to($client->email)->send(
                new PasswordResetMail($resetUrl, $client->firstname)
            );
        }

        // Same message whether or not the email exists, so we don't leak which emails are registered.
        return back()->with('status', 'If an account exists for that email, we\'ve sent a password reset link.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
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

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (! $record
            || ! Hash::check($request->token, $record->token)
            || abs(now()->diffInMinutes($record->created_at)) > self::TOKEN_TTL_MINUTES
        ) {
            return back()->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        $client = Client::where('email', $request->email)->first();

        if (! $client) {
            return back()->withErrors(['email' => 'We couldn\'t find an account for that email.']);
        }

        $client->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('success', 'Your password has been reset. Please sign in.');
    }
}
