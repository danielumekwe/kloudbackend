<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductPrice;

/**
 * Resolves the admin-editable product catalog (see AdminProductsController,
 * /admin/products) on top of each product type's existing pricing engine.
 *
 * QS/SSL/Domains price as "live InterServer cost x markup %" by design, so
 * prices auto-track InterServer's real costs (see config/qs_pricing.php etc).
 * price() lets an admin pin an explicit per-currency/per-cycle price for a
 * specific item that overrides that formula; when no enabled override
 * exists, the live-cost-derived USD fallback is simply currency-converted.
 *
 * VPS has no such upstream dependency, so vpsSlicePrice() fully replaces the
 * old flat "price_per_slice x period discount" multiplier with an explicit
 * per-slice price per cycle/currency, falling back to that same formula only
 * when no override has been set for a given cycle/currency.
 */
class ProductCatalog
{
    public static function find(string $type, string $key): ?Product
    {
        return Product::query()->where('type', $type)->where('key', $key)->with('prices')->first();
    }

    public static function price(string $type, string $key, int $cycleMonths, string $currency, float $usdFallback): float
    {
        $override = static::resolveOverride($type, $key, $cycleMonths, $currency);

        return $override ?? CurrencyConverter::convertFromUsd($usdFallback, $currency);
    }

    public static function vpsSlicePrice(string $category, int $cycleMonths, string $currency, float $usdPerSliceFallback): float
    {
        $override = static::resolveOverride('vps', $category, $cycleMonths, $currency);

        if ($override !== null) {
            return $override;
        }

        $discounted = $usdPerSliceFallback * $cycleMonths * PricingConfig::vpsPeriodDiscount($cycleMonths);

        return CurrencyConverter::convertFromUsd($discounted, $currency);
    }

    private static function resolveOverride(string $type, string $key, int $cycleMonths, string $currency): ?float
    {
        $product = static::find($type, $key);

        if (! $product) {
            return null;
        }

        $row = $product->prices->first(fn (ProductPrice $p) => $p->currency === $currency && $p->billing_cycle_months === $cycleMonths);

        if ($row && $row->is_enabled && $row->price !== null) {
            return (float) $row->price;
        }

        return null;
    }

    public static function upsert(string $type, string $key, array $details): Product
    {
        return Product::query()->updateOrCreate(['type' => $type, 'key' => $key], $details);
    }
}
