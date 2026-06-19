<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\WhmcsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private WhmcsService $whmcs) {}

    public function show(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname'  => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:200'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'address1'  => ['required', 'string', 'max:200'],
            'city'      => ['required', 'string', 'max:100'],
            'state'     => ['required', 'string', 'max:100'],
            'postcode'  => ['required', 'string', 'max:20'],
            'country'   => ['required', 'string', 'size:2'],
            'phonenumber' => ['required', 'string', 'max:30'],
        ]);

        $result = $this->whmcs->addClient([
            'firstname'   => $request->firstname,
            'lastname'    => $request->lastname,
            'email'       => $request->email,
            'password2'   => $request->password,
            'address1'    => $request->address1,
            'city'        => $request->city,
            'state'       => $request->state,
            'postcode'    => $request->postcode,
            'country'     => strtoupper($request->country),
            'phonenumber' => $request->phonenumber,
        ]);

        if (($result['result'] ?? '') !== 'success') {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => $result['message'] ?? 'Registration failed. Please try again.']);
        }

        $clientId = (int) $result['clientid'];

        session()->regenerate();
        session([
            'clientId'  => $clientId,
            'firstName' => $request->firstname,
            'lastName'  => $request->lastname,
            'email'     => $request->email,
        ]);

        return redirect()->route('dashboard')->with('success', 'Welcome to Kloud101! Your account has been created.');
    }
}
