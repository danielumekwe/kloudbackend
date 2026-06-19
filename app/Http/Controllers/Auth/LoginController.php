<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\WhmcsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private WhmcsService $whmcs) {}

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

        $result = $this->whmcs->validateLogin($request->email, $request->password);

        if (($result['result'] ?? '') !== 'success') {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => $result['message'] ?? 'Invalid email or password.']);
        }

        $clientId = (int) $result['userid'];
        $details  = $this->whmcs->getClientDetails($clientId);
        $client   = $details['client'] ?? [];

        session()->regenerate();
        session([
            'clientId'  => $clientId,
            'firstName' => $client['firstname'] ?? '',
            'lastName'  => $client['lastname']  ?? '',
            'email'     => $client['email']      ?? $request->email,
        ]);

        $response = redirect()->intended(route('dashboard'));

        if ($request->boolean('remember')) {
            $response->withCookie(
                cookie('kloud101_remember', $clientId, 60 * 24 * 30) // 30 days
            );
        }

        return $response;
    }
}
