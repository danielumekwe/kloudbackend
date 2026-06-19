<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\WhmcsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private WhmcsService $whmcs) {}

    public function index(): View
    {
        $details = $this->whmcs->getClientDetails(session('clientId'));
        $client  = $details['client'] ?? [];
        return view('dashboard.profile.index', compact('client'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'firstname'   => ['required', 'string', 'max:100'],
            'lastname'    => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:200'],
            'phonenumber' => ['required', 'string', 'max:30'],
            'address1'    => ['required', 'string', 'max:200'],
            'city'        => ['required', 'string', 'max:100'],
            'state'       => ['required', 'string', 'max:100'],
            'postcode'    => ['required', 'string', 'max:20'],
            'country'     => ['required', 'string', 'size:2'],
        ]);

        $result = $this->whmcs->updateClient(session('clientId'), [
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

        if (($result['result'] ?? '') !== 'success') {
            return back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Failed to update profile. Please try again.');
        }

        session([
            'firstName' => $request->firstname,
            'lastName'  => $request->lastname,
            'email'     => $request->email,
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $result = $this->whmcs->updateClient(session('clientId'), [
            'password2' => $request->password,
        ]);

        if (($result['result'] ?? '') !== 'success') {
            return back()->with('error', $result['message'] ?? 'Failed to update password. Please try again.');
        }

        return back()->with('success', 'Password changed successfully.');
    }
}
