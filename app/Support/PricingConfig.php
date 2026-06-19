<?php

namespace App\Support;

use App\Models\PricingSetting;

/**
 * Reads pricing knobs with DB overrides (set via the admin dashboard) taking
 * precedence over the config/*_pricing.php file defaults.
 */
class PricingConfig
{
    public static function vpsPlan(string $category): ?array
    {
        $plan = config("vps_pricing.categories.{$category}");

        if (! $plan) {
            return null;
        }

        $plan['price_per_slice'] = PricingSetting::get("vps.categories.{$category}.price_per_slice", $plan['price_per_slice'] ?? 0);

        if (array_key_exists('controlpanel_price', $plan)) {
            $plan['controlpanel_price'] = PricingSetting::get("vps.categories.{$category}.controlpanel_price", $plan['controlpanel_price']);
        }

        if (array_key_exists('controlpanel_options', $plan)) {
            foreach ($plan['controlpanel_options'] as $key => $option) {
                $plan['controlpanel_options'][$key]['price'] = PricingSetting::get(
                    "vps.categories.{$category}.controlpanel_options.{$key}.price",
                    $option['price'] ?? 0
                );
            }
        }

        return $plan;
    }

    public static function vpsPeriodDiscount(int $months): float
    {
        $default = config("vps_pricing.period_months.{$months}.discount", 1);

        return (float) PricingSetting::get("vps.period_months.{$months}.discount", $default);
    }

    public static function qsMarkupPercent(): float
    {
        return (float) PricingSetting::get('qs.markup_percent', config('qs_pricing.markup_percent', 0));
    }

    public static function qsServerOverrides(): array
    {
        return (array) PricingSetting::get('qs.server_overrides', config('qs_pricing.server_overrides', []));
    }

    public static function sslMarkupPercent(): float
    {
        return (float) PricingSetting::get('ssl.markup_percent', config('ssl_pricing.markup_percent', 0));
    }

    public static function sslPackageOverrides(): array
    {
        return (array) PricingSetting::get('ssl.package_overrides', config('ssl_pricing.package_overrides', []));
    }

    public static function sslPeriodDiscount(int $months): float
    {
        $default = config("ssl_pricing.period_months.{$months}.discount", 1);

        return (float) PricingSetting::get("ssl.period_months.{$months}.discount", $default);
    }

    public static function domainsMarkupPercent(): float
    {
        return (float) PricingSetting::get('domains.markup_percent', config('domains_pricing.markup_percent', 0));
    }

    public static function domainsTldOverrides(): array
    {
        return (array) PricingSetting::get('domains.tld_overrides', config('domains_pricing.tld_overrides', []));
    }

    public static function domainsWhoisPrivacyPrice(): float
    {
        return (float) PricingSetting::get('domains.whois_privacy_price', config('domains_pricing.whois_privacy_price', 5));
    }
}
