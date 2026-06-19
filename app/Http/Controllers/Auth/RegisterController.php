<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\WhmcsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            'email'     => ['required', 'email', 'max:200', 'unique:clients,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'address1'  => ['required', 'string', 'max:200'],
            'city'      => ['required', 'string', 'max:100'],
            'state'     => ['required', 'string', 'max:100'],
            'postcode'  => ['required', 'string', 'max:20'],
            'country'   => ['required', 'string', 'size:2'],
            'phonenumber' => ['required', 'string', 'max:30'],
        ]);

        $client = Client::create([
            'firstname'   => $request->firstname,
            'lastname'    => $request->lastname,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'address1'    => $request->address1,
            'city'        => $request->city,
            'state'       => $request->state,
            'postcode'    => $request->postcode,
            'country'     => strtoupper($request->country),
            'phonenumber' => $request->phonenumber,
        ]);

        $this->createShadowWhmcsClient($client, $request->password);

        session()->regenerate();
        session([
            'clientId'  => $client->id,
            'firstName' => $client->firstname,
            'lastName'  => $client->lastname,
            'email'     => $client->email,
        ]);

        return redirect()->route('dashboard')->with('success', 'Welcome to Kloud101! Your account has been created.');
    }

    /**
     * Best-effort — invoicing is still WHMCS-backed until Phase 3 of the WHMCS
     * exit, so every client needs a WHMCS row to attach future invoices to. Never
     * blocks registration: a WHMCS hiccup here is logged for follow-up rather than
     * failing the signup outright, which is actually a reliability improvement over
     * the old WHMCS-only flow (a WHMCS outage used to block every signup).
     *
     * The WHMCS id this returns is NOT the same as $client->id — WHMCS assigns its
     * own, unrelated id. It's saved separately as whmcs_client_id specifically so
     * later WHMCS calls (createInvoice, GetClientsProducts, etc.) never accidentally
     * use the local id and hit a different, unrelated WHMCS client.
     */
    private function createShadowWhmcsClient(Client $client, string $password): void
    {
        $result = $this->whmcs->addClient([
            'firstname'   => $client->firstname,
            'lastname'    => $client->lastname,
            'email'       => $client->email,
            'password2'   => $password,
            'address1'    => $client->address1,
            'city'        => $client->city,
            'state'       => $client->state,
            'postcode'    => $client->postcode,
            'country'     => $client->country,
            'phonenumber' => $client->phonenumber,
        ]);

        if (($result['result'] ?? '') !== 'success') {
            Log::error('Failed to create shadow WHMCS client during registration — invoicing for this client will fail until this is resolved manually.', [
                'client_id' => $client->id,
                'email' => $client->email,
                'result' => $result,
            ]);
            return;
        }

        $client->update(['whmcs_client_id' => (int) $result['clientid']]);
    }
}
