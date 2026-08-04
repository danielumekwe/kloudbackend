<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginAlertMail;
use App\Models\Client;
use App\Models\LoginActivity;
use App\Services\IpLocationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $client = Client::where('email', $request->email)->first();

        if (! $client || ! $client->checkPassword($request->password)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        if ($client->isSuspended()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This account has been suspended. Contact support for assistance.']);
        }

        session()->regenerate();
        session([
            'clientId'  => $client->id,
            'firstName' => $client->firstname,
            'lastName'  => $client->lastname,
            'email'     => $client->email,
        ]);

        $location = app(IpLocationService::class)->locate($request->ip());

        LoginActivity::create([
            'client_id'  => $client->id,
            'ip_address' => $request->ip(),
            'location'   => $location,
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        Mail::to($client->email)->send(new LoginAlertMail(
            firstName: $client->firstname,
            ip: $request->ip(),
            location: $location,
            loggedInAt: now()->format('M j, Y \a\t g:i A'),
        ));

        $response = redirect()->intended(route('dashboard'));

        if ($request->boolean('remember')) {
            $response->withCookie(
                cookie('kloud101_remember', $client->id, 60 * 24 * 30) // 30 days
            );
        }

        return $response;
    }
}
