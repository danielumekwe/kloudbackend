<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Services\WhmcsService;
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

    public function __construct(private WhmcsService $whmcs) {}

    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $result = $this->whmcs->getClientByEmail($request->email);

        if (($result['result'] ?? '') === 'success') {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $request->email,
            ]);

            Mail::to($request->email)->send(
                new PasswordResetMail($resetUrl, $result['client']['firstname'] ?? 'there')
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

        $client = $this->whmcs->getClientByEmail($request->email);

        if (($client['result'] ?? '') !== 'success') {
            return back()->withErrors(['email' => 'We couldn\'t find an account for that email.']);
        }

        $clientId = (int) $client['client']['id'];

        $update = $this->whmcs->updateClient($clientId, ['password2' => $request->password]);

        if (($update['result'] ?? '') !== 'success') {
            return back()->withErrors(['email' => $update['message'] ?? 'Could not reset password. Please try again.']);
        }

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('success', 'Your password has been reset. Please sign in.');
    }
}
