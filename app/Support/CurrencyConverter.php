<?php

namespace App\Support;

use App\Services\WhmcsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Converts our internal USD prices into whatever currencies are configured in
 * WHMCS (Setup > Payments > Currencies), and formats amounts using WHMCS's own
 * prefix/suffix for that currency. Falls back to a synthetic USD-only entry
 * when WHMCS has no currencies configured (or is unreachable) so the rest of
 * the app degrades to today's USD-only behavior instead of breaking.
 */
class CurrencyConverter
{
    private const CACHE_KEY = 'whmcs.currencies';

    private const USD_FALLBACK = [
        'id'      => 0,
        'code'    => 'USD',
        'prefix'  => '$',
        'suffix'  => '',
        'format'  => 1,
        'rate'    => 1.0,
        'default' => true,
    ];

    /**
     * All currencies configured in WHMCS, cached briefly since this is read on
     * every dashboard page load (topbar switcher). Always returns at least the
     * USD fallback so callers never have to handle an empty list.
     */
    public static function available(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(15), function () {
            $currencies = app(WhmcsService::class)->getCurrencies();

            if (empty($currencies)) {
                Cache::put(self::CACHE_KEY, [self::USD_FALLBACK], now()->addMinute());
                return [self::USD_FALLBACK];
            }

            return array_map(fn ($c) => [
                'id'      => (int) ($c['id'] ?? 0),
                'code'    => (string) ($c['code'] ?? 'USD'),
                'prefix'  => (string) ($c['prefix'] ?? ''),
                'suffix'  => (string) ($c['suffix'] ?? ''),
                'format'  => (int) ($c['format'] ?? 1),
                'rate'    => (float) ($c['rate'] ?? 1.0),
                'default' => (bool) ($c['default'] ?? false),
            ], $currencies);
        });
    }

    public static function find(string $code): ?array
    {
        foreach (self::available() as $currency) {
            if ($currency['code'] === $code) {
                return $currency;
            }
        }
        return null;
    }

    public static function findById(int $id): ?array
    {
        foreach (self::available() as $currency) {
            if ($currency['id'] === $id) {
                return $currency;
            }
        }
        return null;
    }

    public static function default(): array
    {
        foreach (self::available() as $currency) {
            if ($currency['default']) {
                return $currency;
            }
        }
        return self::USD_FALLBACK;
    }

    public static function rate(string $code): float
    {
        return self::find($code)['rate'] ?? 1.0;
    }

    /**
     * @param float $usdAmount Our internal price, always computed in USD.
     */
    public static function convertFromUsd(float $usdAmount, string $code): float
    {
        return round($usdAmount * self::rate($code), 2);
    }

    /**
     * A simple prefix-only symbol for older catalog views (qs/ssl/domains) that
     * concatenate the symbol and amount themselves rather than calling format().
     * Falls back to "CODE " when the currency has no prefix configured.
     */
    public static function symbol(string $code): string
    {
        $currency = self::find($code) ?? self::default();

        return $currency['prefix'] !== '' ? $currency['prefix'] : $currency['code'] . ' ';
    }

    public static function format(float $amount, string $code): string
    {
        $currency = self::find($code);

        if (! $currency) {
            return $code . ' ' . number_format($amount, 2);
        }

        if ($currency['prefix'] !== '') {
            return $currency['prefix'] . number_format($amount, 2);
        }

        if ($currency['suffix'] !== '') {
            return number_format($amount, 2) . ' ' . $currency['suffix'];
        }

        return $currency['code'] . ' ' . number_format($amount, 2);
    }

    /**
     * Ensures the client's WHMCS account currency matches $code before an invoice is
     * created — WHMCS invoices always use whatever currency is currently set on the
     * account, with no per-invoice override. Switches it via UpdateClient only when
     * our cached belief (session('whmcs_account_currency_id')) doesn't already match;
     * that session value is updated only here, on confirmed success, per the
     * invariant documented in WhmcsAuth::ensureCurrencyDefault().
     *
     * Returns false if a switch was needed but failed — callers MUST abort their
     * order flow without creating an invoice in that case, to avoid silently
     * invoicing in the wrong currency.
     */
    public static function ensureClientCurrency(int $clientId, string $code): bool
    {
        $currency = self::find($code) ?? self::default();

        if (session('whmcs_account_currency_id') === $currency['id']) {
            return true;
        }

        $result = app(WhmcsService::class)->switchClientCurrency($clientId, $currency['id']);

        if (($result['result'] ?? '') !== 'success') {
            Log::error('Failed to switch client WHMCS currency before invoicing', [
                'client_id'        => $clientId,
                'target_currency'  => $code,
                'result'           => $result,
            ]);
            return false;
        }

        session(['whmcs_account_currency_id' => $currency['id']]);
        return true;
    }

    /**
     * Bypasses the cache entirely — used only at order-placement time, where a
     * stale rate is an unacceptable risk since real money changes hands. Falls
     * back to the cached rate (logged) only if the live fetch itself fails.
     */
    public static function refreshFresh(string $code): float
    {
        $currencies = app(WhmcsService::class)->getCurrencies();

        foreach ($currencies as $currency) {
            if (($currency['code'] ?? null) === $code) {
                return (float) ($currency['rate'] ?? 1.0);
            }
        }

        Log::warning('CurrencyConverter::refreshFresh could not fetch a live rate, falling back to cached rate', ['code' => $code]);

        return self::rate($code);
    }
}
