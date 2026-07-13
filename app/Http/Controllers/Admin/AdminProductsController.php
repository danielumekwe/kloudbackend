<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\InterServerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * WHMCS-style product catalog editor (/admin/products) sitting alongside the
 * existing /admin/pricing engine-wide knobs (markup %, discount defaults).
 * See App\Support\ProductCatalog for how these per-item overrides are
 * resolved against each product type's live pricing formula.
 */
class AdminProductsController extends Controller
{
    private const TYPES = ['vps', 'qs', 'ssl', 'domain'];

    public function __construct(private InterServerService $interserver) {}

    public function index(string $type): View
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        $products = Product::query()->where('type', $type)->get()->keyBy('key');

        $items = match ($type) {
            'vps'    => $this->vpsItems($products),
            'qs'     => $this->qsItems($products),
            'ssl'    => $this->sslItems($products),
            'domain' => $this->domainItems($products),
        };

        return view('admin.products.index', [
            'type'  => $type,
            'items' => $items,
        ]);
    }

    public function edit(string $type, string $key): View
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        $product = Product::query()->where('type', $type)->where('key', $key)->with('prices')->first();

        $priceGrid = [];
        foreach ($product?->prices ?? [] as $row) {
            $priceGrid[$row->currency][$row->billing_cycle_months] = $row;
        }

        return view('admin.products.edit', [
            'type'        => $type,
            'key'         => $key,
            'product'     => $product,
            'defaultName' => $product->name ?? $this->defaultName($type, $key),
            'currencies'  => array_keys(config('currencies', ['USD' => []])),
            'cycles'      => $this->cyclesFor($type),
            'priceGrid'   => $priceGrid,
        ]);
    }

    public function updateDetails(Request $request, string $type, string $key): RedirectResponse
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'tagline'     => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_hidden'   => ['nullable', 'boolean'],
            'is_retired'  => ['nullable', 'boolean'],
        ]);

        Product::query()->updateOrCreate(['type' => $type, 'key' => $key], [
            'name'        => $validated['name'],
            'tagline'     => $validated['tagline'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_hidden'   => (bool) ($validated['is_hidden'] ?? false),
            'is_retired'  => (bool) ($validated['is_retired'] ?? false),
        ]);

        return back()->with('success', 'Product details updated.');
    }

    public function updatePricing(Request $request, string $type, string $key): RedirectResponse
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        $validated = $request->validate([
            'prices'                  => ['nullable', 'array'],
            'prices.*.*.price'        => ['nullable', 'numeric', 'min:0'],
            'prices.*.*.is_enabled'   => ['nullable', 'boolean'],
        ]);

        $product = Product::query()->firstOrCreate(
            ['type' => $type, 'key' => $key],
            ['name' => $this->defaultName($type, $key)],
        );

        foreach ($validated['prices'] ?? [] as $currency => $cycles) {
            foreach ($cycles as $cycleMonths => $cell) {
                ProductPrice::query()->updateOrCreate(
                    ['product_id' => $product->id, 'currency' => $currency, 'billing_cycle_months' => (int) $cycleMonths],
                    [
                        'price'      => $cell['price'] !== '' && $cell['price'] !== null ? (float) $cell['price'] : null,
                        'is_enabled' => (bool) ($cell['is_enabled'] ?? false),
                    ],
                );
            }
        }

        return back()->with('success', 'Pricing updated.');
    }

    private function vpsItems($products): array
    {
        return collect(config('vps_pricing.categories'))->map(function (array $cat, string $key) use ($products) {
            return [
                'key'   => $key,
                'label' => $products[$key]->name ?? $cat['label'],
                'product' => $products[$key] ?? null,
            ];
        })->values()->all();
    }

    private function qsItems($products): array
    {
        $catalog = $this->interserver->getQsOrderCatalog();
        $servers = $catalog['server_details'] ?? [];

        return collect($servers)->map(function (array $details, $serverId) use ($products) {
            $key = (string) $serverId;
            return [
                'key'   => $key,
                'label' => $products[$key]->name ?? ($details['cpu'] ?? "Quick Server #{$key}"),
                'product' => $products[$key] ?? null,
            ];
        })->values()->all();
    }

    private function sslItems($products): array
    {
        $catalog = $this->interserver->getSslOrderCatalog();
        $packages = collect($catalog['serviceTypes'] ?? [])
            ->filter(fn ($p) => ($p['services_buyable'] ?? false) && ($p['services_id'] ?? null));

        return $packages->map(function (array $p) use ($products) {
            $key = (string) $p['services_id'];
            return [
                'key'   => $key,
                'label' => $products[$key]->name ?? ($p['services_name'] ?? "SSL Package #{$key}"),
                'product' => $products[$key] ?? null,
            ];
        })->values()->all();
    }

    private function domainItems($products): array
    {
        return $products->map(fn (Product $p) => [
            'key'     => $p->key,
            'label'   => $p->name,
            'product' => $p,
        ])->values()->all();
    }

    private function defaultName(string $type, string $key): string
    {
        return match ($type) {
            'vps'    => config("vps_pricing.categories.{$key}.label", $key),
            'qs'     => $this->interserver->getQsOrderCatalog()['server_details'][$key]['cpu'] ?? "Quick Server #{$key}",
            'ssl'    => collect($this->interserver->getSslOrderCatalog()['serviceTypes'] ?? [])
                ->firstWhere('services_id', $key)['services_name'] ?? "SSL Package #{$key}",
            'domain' => strtoupper($key),
        };
    }

    private function cyclesFor(string $type): array
    {
        return match ($type) {
            'vps'    => collect(config('vps_pricing.period_months'))->map(fn ($p, $m) => ['months' => (int) $m, 'label' => $p['label']])->values()->all(),
            'ssl'    => collect(config('ssl_pricing.period_months'))->map(fn ($p, $m) => ['months' => (int) $m, 'label' => $p['label']])->values()->all(),
            'qs'     => [['months' => 1, 'label' => 'Monthly']],
            'domain' => [
                ['months' => 12, 'label' => '1 Year'],
                ['months' => 24, 'label' => '2 Years'],
                ['months' => 36, 'label' => '3 Years'],
                ['months' => 60, 'label' => '5 Years'],
                ['months' => 120, 'label' => '10 Years'],
            ],
        };
    }
}
