<?php

namespace App\Http\Middleware;

use App\Services\WhmcsService;
use App\Support\CurrencyConverter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WhmcsAuth
{
    public function __construct(private WhmcsService $whmcs) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('clientId')) {
            $this->ensureCurrencyDefault();
            return $next($request);
        }

        $rememberedId = $request->cookie('kloud101_remember');

        if ($rememberedId && is_numeric($rememberedId)) {
            $client = $this->whmcs->getClientDetails((int) $rememberedId);

            if (($client['result'] ?? '') === 'success') {
                $data = $client['client'];
                session([
                    'clientId'  => (int) $rememberedId,
                    'firstName' => $data['firstname'] ?? '',
                    'lastName'  => $data['lastname'] ?? '',
                    'email'     => $data['email'] ?? '',
                ]);
                $this->ensureCurrencyDefault();
                return $next($request);
            }
        }

        return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
    }

    /**
     * Every authenticated request needs a valid session('currency'). We also track
     * session('whmcs_account_currency_id') as our cached belief of what WHMCS's
     * tblclients.currency is currently set to — updated only when
     * WhmcsService::switchClientCurrency() confirms success (see order controllers).
     * Nothing else in this app mutates a client's WHMCS currency, so this cache is
     * safe to trust without re-fetching on every request.
     */
    private function ensureCurrencyDefault(): void
    {
        $needsCurrency  = ! session()->has('currency');
        $needsAccountId = ! session()->has('whmcs_account_currency_id');

        if (! $needsCurrency && ! $needsAccountId) {
            return;
        }

        // Every client's WHMCS account currency is assumed to already be the default
        // (true for every existing client, since this app never switched anyone's
        // currency before this feature existed) — this avoids an unnecessary/no-op
        // switchClientCurrency() call on a client's very first order after this ships.
        $default = CurrencyConverter::default();

        if ($needsCurrency) {
            session(['currency' => $default['code']]);
        }
        if ($needsAccountId) {
            session(['whmcs_account_currency_id' => $default['id']]);
        }
    }
}
