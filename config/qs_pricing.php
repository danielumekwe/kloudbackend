<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Quick Server pricing
    |--------------------------------------------------------------------------
    |
    | Unlike VPS, Quick Servers are a fixed inventory of physical machines
    | (InterServer's GetNewQs.server_details, keyed by server id) billed
    | monthly with no slice/period selection. We charge a percentage markup
    | over whatever InterServer's live "cost" field returns for each server,
    | so pricing never drifts from their real cost.
    |
    */
    'markup_percent' => 20,

    // Optional flat override price (USD/month) per InterServer server id,
    // when you want a specific tier priced outside the standard markup.
    'server_overrides' => [
        // 208 => 99.00,
    ],

    'currency_symbol' => '$',
];
