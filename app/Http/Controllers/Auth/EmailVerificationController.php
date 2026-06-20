<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailMail;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * Reached via a signed, time-limited URL mailed by send() — works whether or not
     * the client is currently logged in on this device, same as a password reset link.
     * The route's `signed` middleware already rejects a missing/expired/tampered
     * signature before this runs.
     */
    public function verify(int $id): RedirectResponse
    {
        $client = Client::find($id);

        if ($client && ! $client->isEmailVerified()) {
            $client->update(['email_verified_at' => now()]);
        }

        return redirect()->route(session()->has('clientId') ? 'dashboard' : 'login')
            ->with('success', 'Your email address has been verified.');
    }

    public function resend(): RedirectResponse
    {
        $client = Client::find(session('clientId'));

        if (! $client || $client->isEmailVerified()) {
            return back();
        }

        self::send($client);

        return back()->with('success', 'Verification email sent — check your inbox.');
    }

    public static function send(Client $client): void
    {
        $verifyUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $client->id]);

        Mail::to($client->email)->send(new VerifyEmailMail($verifyUrl, $client->firstname));
    }
}
