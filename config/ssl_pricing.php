<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SSL Certificate pricing
    |--------------------------------------------------------------------------
    |
    | SSL packages are discrete tiers sourced live from InterServer's catalog
    | (GetNewSsl.serviceTypes[id].services_cost). We charge a percentage markup
    | over that live price rather than a static price list, since InterServer
    | can change package prices without our code changing.
    |
    */
    'markup_percent' => 20,

    // Optional flat override price (USD) per InterServer package id.
    'package_overrides' => [
        // 3000 => 29.00, // AlphaSSL
    ],

    'period_months' => [
        12 => ['label' => 'Annually',    'discount' => 1.00],
        24 => ['label' => 'Biennially',  'discount' => 0.95],
        36 => ['label' => 'Triennially', 'discount' => 0.90],
    ],

    'currency_symbol' => '$',
];
