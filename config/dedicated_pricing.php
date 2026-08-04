<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dedicated Server pricing
    |--------------------------------------------------------------------------
    |
    | Like Quick Servers, Dedicated Servers are ordered from a live inventory
    | of pre-built physical machines (InterServer's Rapid Deploy / Buy-It-Now
    | marketplace, keyed by asset id) billed monthly. We charge a percentage
    | markup over whatever InterServer's live "price" field returns for each
    | listing, so pricing never drifts from their real cost.
    |
    */
    'markup_percent' => 20,

    // Optional flat override price (USD/month) per InterServer marketplace
    // asset id, when you want a specific listing priced outside the standard
    // markup. Note: marketplace inventory churns as listings sell — an
    // override only applies while that exact asset id is still in stock.
    'server_overrides' => [
        // 10467 => 89.00,
    ],

    'currency_symbol' => '$',
];
