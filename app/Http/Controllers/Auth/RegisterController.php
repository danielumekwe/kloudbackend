<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email', 'max:200', 'unique:clients,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        session(['register_pending' => [
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]]);

        return redirect()->route('register.complete');
    }

    public function showComplete(): View|RedirectResponse
    {
        $pending = session('register_pending');

        if (! $pending) {
            return redirect()->route('register');
        }

        return view('auth.register-complete', ['pending' => $pending]);
    }

    public function storeComplete(Request $request): RedirectResponse
    {
        $pending = session('register_pending');

        if (! $pending) {
            return redirect()->route('register');
        }

        $request->validate([
            'firstname'   => ['required', 'string', 'max:100'],
            'lastname'    => ['required', 'string', 'max:100'],
            'phonenumber' => ['required', 'string', 'max:30'],
            'address1'    => ['required', 'string', 'max:200'],
            'city'        => ['required', 'string', 'max:100'],
            'state'       => ['required', 'string', 'max:100'],
            'postcode'    => ['required', 'string', 'max:20'],
            'country'     => ['required', 'string', 'size:2'],
        ]);

        $client = Client::create([
            'firstname'   => $request->firstname,
            'lastname'    => $request->lastname,
            'email'       => $pending['email'],
            'password'    => $pending['password'],
            'address1'    => $request->address1,
            'city'        => $request->city,
            'state'       => $request->state,
            'postcode'    => $request->postcode,
            'country'     => strtoupper($request->country),
            'phonenumber' => $request->phonenumber,
        ]);

        EmailVerificationController::send($client);

        session()->forget('register_pending');
        session()->regenerate();
        session([
            'clientId'  => $client->id,
            'firstName' => $client->firstname,
            'lastName'  => $client->lastname,
            'email'     => $client->email,
        ]);

        return redirect()->route('dashboard')->with('success', 'Welcome to Kloud101! Your account has been created.');
    }
}
