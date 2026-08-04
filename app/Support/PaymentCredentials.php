<?php

namespace App\Support;

use App\Models\PaymentGatewaySetting;

/**
 * Resolves gateway credentials DB-first (admin-entered via the Payment Settings
 * page), falling back to .env/config/services.php so nothing breaks until an
 * admin fills the form in.
 */
class PaymentCredentials
{
    public static function get(string $gateway, string $keyType): ?string
    {
        $dbValue = PaymentGatewaySetting::get($gateway, $keyType);

        if ($dbValue !== null && $dbValue !== '') {
            return $dbValue;
        }

        return config("services.{$gateway}.{$keyType}");
    }
}
