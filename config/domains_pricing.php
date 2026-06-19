<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain pricing
    |--------------------------------------------------------------------------
    |
    | Domain registration/renewal/transfer prices come live from InterServer's
    | per-TLD lookup (GetDomainLookup.new / .renewal / .transfer). We charge a
    | percentage markup over that live price rather than a static per-TLD
    | price list, since registry pricing changes outside of our control.
    |
    */
    'markup_percent' => 25,

    // Optional flat override price (USD) per TLD, e.g. 'com' => 12.99.
    'tld_overrides' => [
        //
    ],

    // Flat add-on price (USD) for Whois Privacy Protection.
    'whois_privacy_price' => 5,

    'currency_symbol' => '$',
];
