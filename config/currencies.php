<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currencies
    |--------------------------------------------------------------------------
    |
    | Replaces WHMCS's currency list (Setup > Payments > Currencies) now that
    | invoicing is fully local (Phase 3 of the WHMCS exit). Rates are USD-based
    | (1 USD = `rate` units of this currency) and admin-editable via
    | /admin/billing-settings — see PricingConfig::currencyRate(), which layers
    | a PricingSetting override on top of the default `rate` below. Exactly one
    | currency should have `default` => true.
    |
    */

    'USD' => [
        'label'   => 'US Dollar',
        'prefix'  => '$',
        'suffix'  => '',
        'format'  => 1,
        'rate'    => 1.0,
        'default' => true,
    ],

    'NGN' => [
        'label'   => 'Nigerian Naira',
        'prefix'  => '₦',
        'suffix'  => '',
        'format'  => 1,
        'rate'    => 1400.0,
        'default' => false,
    ],

];
