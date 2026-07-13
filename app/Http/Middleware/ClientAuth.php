<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Support\CurrencyConverter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('clientId')) {
            if ($this->isSuspended((int) session('clientId'))) {
                return $this->rejectSuspended($request);
            }

            $this->ensureCurrencyDefault();
            return $next($request);
        }

        $rememberedId = $request->cookie('kloud101_remember');

        if ($rememberedId && is_numeric($rememberedId)) {
            $client = Client::find((int) $rememberedId);

            if ($client) {
                if ($client->isSuspended()) {
                    return $this->rejectSuspended($request);
                }

                session([
                    'clientId'  => $client->id,
                    'firstName' => $client->firstname,
                    'lastName'  => $client->lastname,
                    'email'     => $client->email,
                ]);
                $this->ensureCurrencyDefault();
                return $next($request);
            }
        }

        return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
    }

    /**
     * Checked on every request (not just at login) so suspending a client cuts off
     * an already-active session immediately, rather than only blocking future logins.
     */
    private function isSuspended(int $clientId): bool
    {
        return Client::where('id', $clientId)->whereNotNull('suspended_at')->exists();
    }

    private function rejectSuspended(Request $request): Response
    {
        session()->flush();

        return redirect()->route('login')
            ->withCookie(cookie()->forget('kloud101_remember'))
            ->with('error', 'This account has been suspended. Contact support for assistance.');
    }

    /**
     * Every authenticated request needs a valid session('currency'), seeded from
     * the configured default on first login.
     */
    private function ensureCurrencyDefault(): void
    {
        if (session()->has('currency')) {
            return;
        }

        session(['currency' => CurrencyConverter::default()['code']]);
    }
}
