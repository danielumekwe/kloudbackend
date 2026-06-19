<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $client = Client::findOrFail(session('clientId'));

        return view('dashboard.profile.index', ['client' => $client]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'firstname'   => ['required', 'string', 'max:100'],
            'lastname'    => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:200', 'unique:clients,email,' . session('clientId')],
            'phonenumber' => ['required', 'string', 'max:30'],
            'address1'    => ['required', 'string', 'max:200'],
            'city'        => ['required', 'string', 'max:100'],
            'state'       => ['required', 'string', 'max:100'],
            'postcode'    => ['required', 'string', 'max:20'],
            'country'     => ['required', 'string', 'size:2'],
        ]);

        $client = Client::findOrFail(session('clientId'));

        $client->update([
            'firstname'   => $request->firstname,
            'lastname'    => $request->lastname,
            'email'       => $request->email,
            'phonenumber' => $request->phonenumber,
            'address1'    => $request->address1,
            'city'        => $request->city,
            'state'       => $request->state,
            'postcode'    => $request->postcode,
            'country'     => strtoupper($request->country),
        ]);

        session([
            'firstName' => $client->firstname,
            'lastName'  => $client->lastname,
            'email'     => $client->email,
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Client::findOrFail(session('clientId'))->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully.');
    }
}
