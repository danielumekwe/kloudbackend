<?php

return [

    /*
     * Paths that should have CORS headers applied.
     * The marketing site at kloud101.com calls /api/* endpoints.
     */
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    /*
     * Allow the production marketing site and local development.
     * Add additional origins here if needed (e.g. staging domains).
     */
    'allowed_origins' => [
        'https://kloud101.com',
        'https://www.kloud101.com',
        'http://localhost:3000',
        'http://localhost:3001',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
