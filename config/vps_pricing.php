<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VPS Sidebar Categories
    |--------------------------------------------------------------------------
    |
    | Each key is a sidebar sub-category. It maps to an InterServer platform
    | tag (from GetNewVps.platformNames: kvm, kvmstorage, hyperv, ...) plus an
    | optional forced control panel. Edit "price_per_slice" / "controlpanel
    | price" below to set what we charge clients — this is independent of
    | whatever InterServer charges us internally.
    |
    */
    'categories' => [
        'linux-vps' => [
            'label'        => 'Linux VPS',
            'platform'     => 'kvm',
            'controlpanel' => 'none',
            'price_per_slice' => 6.00,
        ],
        'managed-vps' => [
            'label'        => 'Managed VPS',
            'platform'     => 'kvm',
            // We license/bill all control panels ourselves — InterServer just
            // provisions the bare VPS, so this is always "none".
            'controlpanel' => 'none',
            'price_per_slice' => 6.00,
            'min_slices'   => 8,
            'controlpanel_options' => [
                'cpanel' => [
                    'label' => 'cPanel Ultimate Pack',
                    'price' => 10.00,
                    'features' => [
                        'Unlimited cPanel/WHM Accounts',
                        'Softaculous',
                        'ImunifyAV',
                        'SitePad',
                        'LiteSpeed',
                        'WHM Reseller',
                        'CloudLinux License',
                    ],
                ],
                'plesk' => [
                    'label' => 'Plesk Webhost License',
                    'price' => 5.00,
                    'features' => [],
                ],
                'directadmin' => [
                    'label' => 'DirectAdmin License',
                    'price' => 5.00,
                    'features' => [],
                ],
            ],
        ],
        'storage-vps' => [
            'label'        => 'Storage VPS',
            'platform'     => 'kvmstorage',
            'controlpanel' => 'none',
            'price_per_slice' => 6.00,
        ],
        'windows-vps' => [
            'label'        => 'Windows VPS',
            // InterServer orders Windows under its own "hyperv" platform.
            'platform'     => 'hyperv',
            'controlpanel' => 'none',
            'price_per_slice' => 6.00,
            // Not hard-enforced — InterServer's own Windows minimum is much lower than 8.
            // "recommended_min_slices" only drives the on-screen "starts from 8 slices" note.
            'min_slices'   => 1,
            'recommended_min_slices' => 8,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing cycle discounts
    |--------------------------------------------------------------------------
    |
    | Multiplier applied to the monthly price when a longer cycle is chosen.
    | Keys are number of months (matches InterServer's "period" field).
    |
    */
    'period_months' => [
        1  => ['label' => 'Monthly',      'discount' => 1.00],
        6  => ['label' => 'Semi-Annually', 'discount' => 0.95],
        12 => ['label' => 'Annually',      'discount' => 0.90],
        24 => ['label' => 'Biennially',    'discount' => 0.85],
        36 => ['label' => 'Triennially',   'discount' => 0.80],
    ],

    'currency_symbol' => '$',
];
