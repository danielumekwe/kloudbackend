<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewaySetting;
use App\Support\PaymentCredentials;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPaymentSettingsController extends Controller
{
    /**
     * gateway => [key_type => is this a secret (masked) or public (shown in full)]
     */
    private const FIELDS = [
        'paystack' => [
            'public_key' => false,
            'secret_key' => true,
        ],
        'flutterwave' => [
            'public_key'   => false,
            'secret_key'   => true,
            'webhook_hash' => true,
        ],
        'nowpayments' => [
            'api_key'    => true,
            'ipn_secret' => true,
        ],
    ];

    public function index(): View
    {
        $gateways = [];

        foreach (self::FIELDS as $gateway => $fields) {
            $rows = [];

            foreach ($fields as $keyType => $isSecret) {
                $dbValue = PaymentGatewaySetting::get($gateway, $keyType);
                $hasDbValue = $dbValue !== null && $dbValue !== '';
                $envValue = config("services.{$gateway}.{$keyType}");
                $hasEnvValue = $envValue !== null && $envValue !== '';

                $rows[$keyType] = [
                    'is_secret' => $isSecret,
                    'display'   => $isSecret
                        ? ($hasDbValue
                            ? '•••• ' . substr($dbValue, -4)
                            : ($hasEnvValue ? 'Not set (using .env)' : 'Not configured'))
                        : PaymentCredentials::get($gateway, $keyType),
                ];
            }

            $gateways[$gateway] = $rows;
        }

        return view('admin.payment-settings.index', compact('gateways'));
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (self::FIELDS as $gateway => $fields) {
            foreach (array_keys($fields) as $keyType) {
                $rules["{$gateway}.{$keyType}"] = ['nullable', 'string'];
            }
        }

        $validated = $request->validate($rules);

        foreach (self::FIELDS as $gateway => $fields) {
            foreach (array_keys($fields) as $keyType) {
                $value = $validated[$gateway][$keyType] ?? null;

                if ($value !== null && $value !== '') {
                    PaymentGatewaySetting::set($gateway, $keyType, $value);
                }
            }
        }

        return back()->with('success', 'Payment gateway settings updated.');
    }
}
