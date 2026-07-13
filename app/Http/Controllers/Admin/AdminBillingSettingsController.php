<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingSetting;
use App\Support\PricingConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBillingSettingsController extends Controller
{
    public function index(): View
    {
        $currencies = collect(config('currencies'))->map(fn ($c, $code) => [
            'code'    => $code,
            'label'   => $c['label'],
            'rate'    => PricingConfig::currencyRate($code),
            'default' => (bool) ($c['default'] ?? false),
        ])->values();

        return view('admin.billing-settings.index', [
            'currencies'    => $currencies,
            'taxRatePercent' => PricingConfig::taxRatePercent(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency'         => ['nullable', 'array'],
            'currency.*.rate'  => ['nullable', 'numeric', 'min:0'],
            'tax_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($validated['currency'] ?? [] as $code => $fields) {
            // The default currency (USD) is the fixed base every price is computed
            // in before conversion — its rate must stay 1.0, never admin-editable.
            if (config("currencies.{$code}.default")) {
                continue;
            }

            if (array_key_exists('rate', $fields) && $fields['rate'] !== null) {
                PricingSetting::set("currency.rates.{$code}", (float) $fields['rate']);
            }
        }

        PricingSetting::set('tax.rate_percent', (float) $validated['tax_rate_percent']);

        return back()->with('success', 'Billing settings updated.');
    }
}
