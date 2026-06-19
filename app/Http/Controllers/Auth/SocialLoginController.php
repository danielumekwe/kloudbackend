<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\WhmcsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SocialLoginController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    public function __construct(private WhmcsService $whmcs) {}

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSupported($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->ensureSupported($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception) {
            return redirect()->route('login')->withErrors(['email' => 'Could not sign in with ' . ucfirst($provider) . '. Please try again.']);
        }

        $email = $socialUser->getEmail();

        if (! $email) {
            return redirect()->route('login')->withErrors(['email' => 'Your ' . ucfirst($provider) . ' account has no email address we can use.']);
        }

        $client = Client::where('email', $email)->first();

        if ($client) {
            $this->logIn($client);

            return redirect()->intended(route('dashboard'));
        }

        [$firstName, $lastName] = $this->splitName($socialUser->getName() ?: $email);

        session(['social_pending' => [
            'provider'  => $provider,
            'email'     => $email,
            'firstname' => $firstName,
            'lastname'  => $lastName,
        ]]);

        return redirect()->route('social.complete');
    }

    public function showComplete(): View|RedirectResponse
    {
        $pending = session('social_pending');

        if (! $pending) {
            return redirect()->route('login');
        }

        return view('auth.social-complete', ['pending' => $pending]);
    }

    public function storeComplete(Request $request): RedirectResponse
    {
        $pending = session('social_pending');

        if (! $pending) {
            return redirect()->route('login');
        }

        $request->validate([
            'address1'    => ['required', 'string', 'max:200'],
            'city'        => ['required', 'string', 'max:100'],
            'state'       => ['required', 'string', 'max:100'],
            'postcode'    => ['required', 'string', 'max:20'],
            'country'     => ['required', 'string', 'size:2'],
            'phonenumber' => ['required', 'string', 'max:30'],
        ]);

        // No password from the social provider — generate a throwaway one. It's
        // never used for real login (this account only ever signs in via OAuth),
        // and the same value is reused for the WHMCS shadow client below.
        $generatedPassword = Str::password(20);

        $client = Client::create([
            'firstname'   => $pending['firstname'],
            'lastname'    => $pending['lastname'],
            'email'       => $pending['email'],
            'password'    => Hash::make($generatedPassword),
            'address1'    => $request->address1,
            'city'        => $request->city,
            'state'       => $request->state,
            'postcode'    => $request->postcode,
            'country'     => strtoupper($request->country),
            'phonenumber' => $request->phonenumber,
        ]);

        $this->createShadowWhmcsClient($client, $generatedPassword);

        session()->forget('social_pending');
        $this->logIn($client);

        return redirect()->route('dashboard')->with('success', 'Welcome to Kloud101! Your account has been created.');
    }

    private function ensureSupported(string $provider): void
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw new NotFoundHttpException();
        }
    }

    private function logIn(Client $client): void
    {
        session()->regenerate();
        session([
            'clientId'  => $client->id,
            'firstName' => $client->firstname,
            'lastName'  => $client->lastname,
            'email'     => $client->email,
        ]);
    }

    /**
     * Best-effort — see RegisterController::createShadowWhmcsClient() for the full
     * rationale (invoicing is still WHMCS-backed until Phase 3 of the WHMCS exit;
     * never blocks account creation; whmcs_client_id is intentionally separate from
     * the local id since WHMCS assigns its own).
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
            Log::error('Failed to create shadow WHMCS client during social registration — invoicing for this client will fail until this is resolved manually.', [
                'client_id' => $client->id,
                'email' => $client->email,
                'result' => $result,
            ]);
            return;
        }

        $client->update(['whmcs_client_id' => (int) $result['clientid']]);
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [$parts[0] ?? $name, $parts[1] ?? ''];
    }
}
