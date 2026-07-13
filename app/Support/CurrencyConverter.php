<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Converts our internal USD prices into whatever currencies are configured
 * locally (config/currencies.php, admin-editable via /admin/billing-settings
 * — see PricingConfig::currencyRate()), and formats amounts using each
 * currency's prefix/suffix. Phase 3 of the WHMCS exit: this used to read from
 * WHMCS's own currency list; now it's fully local, so there's no more
 * live-vs-cached distinction worth a separate method — a cached read is
 * already as fresh as the admin's last edit.
 */
class CurrencyConverter
{
    private const CACHE_KEY = 'currencies';

    private const USD_FALLBACK = [
        'code'    => 'USD',
        'prefix'  => '$',
        'suffix'  => '',
        'format'  => 1,
        'rate'    => 1.0,
        'default' => true,
    ];

    /**
     * All configured currencies, cached briefly since this is read on every
     * dashboard page load (topbar switcher). Always returns at least the USD
     * fallback so callers never have to handle an empty list.
     */
    public static function available(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(15), function () {
            $currencies = config('currencies', []);

            if (empty($currencies)) {
                return [self::USD_FALLBACK];
            }

            return collect($currencies)->map(fn ($c, $code) => [
                'code'    => $code,
                'prefix'  => (string) ($c['prefix'] ?? ''),
                'suffix'  => (string) ($c['suffix'] ?? ''),
                'format'  => (int) ($c['format'] ?? 1),
                'rate'    => PricingConfig::currencyRate($code),
                'default' => (bool) ($c['default'] ?? false),
            ])->values()->all();
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
     * Inverse of convertFromUsd() — used for aggregating amounts that were charged
     * in different currencies (e.g. dashboard revenue totals) onto a common USD
     * base. Never used for invoicing math, only for reporting.
     */
    public static function convertToUsd(float $amount, string $code): float
    {
        $rate = self::rate($code);

        return $rate > 0 ? round($amount / $rate, 2) : $amount;
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
}
