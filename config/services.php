<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whmcs' => [
        'url'        => env('WHMCS_URL', ''),
        'identifier' => env('WHMCS_API_IDENTIFIER', ''),
        'secret'     => env('WHMCS_API_SECRET', ''),
        // Currencies with a working payment gateway attached in WHMCS — purely a UI
        // hint on the currency switcher, not enforced. Update manually as gateways
        // are added; not derivable from the WHMCS API.
        'payable_currencies' => array_filter(array_map('trim', explode(',', env('WHMCS_PAYABLE_CURRENCIES', 'NGN')))),
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect'      => env('GOOGLE_REDIRECT_URI', ''),
    ],

    'facebook' => [
        'client_id'     => env('FACEBOOK_CLIENT_ID', ''),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET', ''),
        'redirect'      => env('FACEBOOK_REDIRECT_URI', ''),
    ],

    'interserver' => [
        'url'     => env('INTERSERVER_URL', 'https://my.interserver.net/apiv2'),
        'api_key' => env('INTERSERVER_API_KEY', ''),
    ],

    'admin' => [
        'password' => env('ADMIN_PASSWORD', ''),
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY', ''),
        'secret_key' => env('PAYSTACK_SECRET_KEY', ''),
    ],

    'flutterwave' => [
        'public_key'  => env('FLUTTERWAVE_PUBLIC_KEY', ''),
        'secret_key'  => env('FLUTTERWAVE_SECRET_KEY', ''),
        // Static pre-shared hash configured in the Flutterwave dashboard, used to
        // verify webhook authenticity (not HMAC-of-body like Paystack/NOWPayments).
        'webhook_hash' => env('FLUTTERWAVE_WEBHOOK_HASH', ''),
    ],

    'nowpayments' => [
        'api_key'     => env('NOWPAYMENTS_API_KEY', ''),
        'ipn_secret'  => env('NOWPAYMENTS_IPN_SECRET', ''),
        'url'         => env('NOWPAYMENTS_URL', 'https://api.nowpayments.io/v1'),
    ],

];
