<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingSetting;
use App\Services\WhmcsService;
use App\Support\PricingConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPricingController extends Controller
{
    public function __construct(private WhmcsService $whmcs) {}

    public function index(): View
    {
        $vpsCategories = collect(config('vps_pricing.categories'))->map(function ($plan, $category) {
            $live = PricingConfig::vpsPlan($category);
            return [
                'key'                  => $category,
                'label'                => $plan['label'],
                'price_per_slice'      => $live['price_per_slice'],
                'has_controlpanel'     => array_key_exists('controlpanel_price', $plan),
                'controlpanel_price'   => $live['controlpanel_price'] ?? null,
                'controlpanel_options' => $live['controlpanel_options'] ?? [],
            ];
        })->values();

        $vpsPeriods = collect(config('vps_pricing.period_months'))->map(fn ($p, $months) => [
            'months' => $months,
            'label'  => $p['label'],
            'discount' => PricingConfig::vpsPeriodDiscount((int) $months),
        ])->values();

        $sslPeriods = collect(config('ssl_pricing.period_months'))->map(fn ($p, $months) => [
            'months' => $months,
            'label'  => $p['label'],
            'discount' => PricingConfig::sslPeriodDiscount((int) $months),
        ])->values();

        $servers = collect($this->whmcs->getProducts())->map(function (array $product) {
            return [
                'name'    => $product['name'] ?? ('Product #' . ($product['pid'] ?? '?')),
                'pricing' => $product['pricing']['USD'] ?? [],
            ];
        })->values();

        return view('admin.pricing.index', [
            'vpsCategories'        => $vpsCategories,
            'vpsPeriods'           => $vpsPeriods,
            'qsMarkupPercent'      => PricingConfig::qsMarkupPercent(),
            'qsServerOverrides'    => json_encode(PricingConfig::qsServerOverrides(), JSON_PRETTY_PRINT),
            'sslMarkupPercent'     => PricingConfig::sslMarkupPercent(),
            'sslPackageOverrides' => json_encode(PricingConfig::sslPackageOverrides(), JSON_PRETTY_PRINT),
            'sslPeriods'           => $sslPeriods,
            'domainsMarkupPercent'    => PricingConfig::domainsMarkupPercent(),
            'domainsTldOverrides'    => json_encode(PricingConfig::domainsTldOverrides(), JSON_PRETTY_PRINT),
            'domainsWhoisPrivacyPrice' => PricingConfig::domainsWhoisPrivacyPrice(),
            'servers'              => $servers,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vps'                       => ['nullable', 'array'],
            'vps.*.price_per_slice'     => ['nullable', 'numeric', 'min:0'],
            'vps.*.controlpanel_price'  => ['nullable', 'numeric', 'min:0'],
            'vps.*.controlpanel_options' => ['nullable', 'array'],
            'vps.*.controlpanel_options.*' => ['nullable', 'numeric', 'min:0'],
            'vps_period'                => ['nullable', 'array'],
            'vps_period.*'              => ['nullable', 'numeric', 'min:0', 'max:2'],
            'qs_markup_percent'         => ['required', 'numeric', 'min:0'],
            'qs_server_overrides'       => ['nullable', 'string'],
            'ssl_markup_percent'        => ['required', 'numeric', 'min:0'],
            'ssl_package_overrides'     => ['nullable', 'string'],
            'ssl_period'                 => ['nullable', 'array'],
            'ssl_period.*'               => ['nullable', 'numeric', 'min:0', 'max:2'],
            'domains_markup_percent'    => ['required', 'numeric', 'min:0'],
            'domains_tld_overrides'     => ['nullable', 'string'],
            'domains_whois_privacy_price' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($validated['vps'] ?? [] as $category => $fields) {
            if (array_key_exists('price_per_slice', $fields) && $fields['price_per_slice'] !== null) {
                PricingSetting::set("vps.categories.{$category}.price_per_slice", (float) $fields['price_per_slice']);
            }
            if (array_key_exists('controlpanel_price', $fields) && $fields['controlpanel_price'] !== null) {
                PricingSetting::set("vps.categories.{$category}.controlpanel_price", (float) $fields['controlpanel_price']);
            }
            foreach ($fields['controlpanel_options'] ?? [] as $optionKey => $price) {
                if ($price !== null) {
                    PricingSetting::set("vps.categories.{$category}.controlpanel_options.{$optionKey}.price", (float) $price);
                }
            }
        }

        foreach ($validated['vps_period'] ?? [] as $months => $discount) {
            if ($discount !== null) {
                PricingSetting::set("vps.period_months.{$months}.discount", (float) $discount);
            }
        }

        foreach ($validated['ssl_period'] ?? [] as $months => $discount) {
            if ($discount !== null) {
                PricingSetting::set("ssl.period_months.{$months}.discount", (float) $discount);
            }
        }

        PricingSetting::set('qs.markup_percent', (float) $validated['qs_markup_percent']);
        PricingSetting::set('ssl.markup_percent', (float) $validated['ssl_markup_percent']);
        PricingSetting::set('domains.markup_percent', (float) $validated['domains_markup_percent']);
        PricingSetting::set('domains.whois_privacy_price', (float) $validated['domains_whois_privacy_price']);

        $jsonFields = [
            'qs_server_overrides'   => 'qs.server_overrides',
            'ssl_package_overrides' => 'ssl.package_overrides',
            'domains_tld_overrides' => 'domains.tld_overrides',
        ];

        foreach ($jsonFields as $field => $settingKey) {
            $raw = trim($validated[$field] ?? '');
            $decoded = $raw === '' ? [] : json_decode($raw, true);

            if (! is_array($decoded)) {
                return back()->withErrors([$field => 'Must be valid JSON (an object like {"id": 12.50}).'])->withInput();
            }

            PricingSetting::set($settingKey, $decoded);
        }

        return back()->with('success', 'Pricing updated.');
    }
}
