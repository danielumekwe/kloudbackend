<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Client;
use App\Models\DedicatedServerOrder;
use App\Services\InterServerService;
use App\Services\InvoiceService;
use App\Support\CurrencyConverter;
use App\Support\PricingConfig;
use App\Support\ProductCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class DedicatedServerController extends Controller
{
    public function __construct(
        private InterServerService $interserver,
        private InvoiceService $invoices,
    ) {}

    /**
     * "My Dedicated Servers" — instances belonging to the logged-in client only.
     */
    public function index(): View
    {
        $clientId = session('clientId');

        $orders = DedicatedServerOrder::where('client_id', $clientId)
            ->whereIn('status', ['provisioned', 'paid', 'pending_payment', 'failed', 'cancelled'])
            ->latest()
            ->get();

        $instances = $orders->map(function (DedicatedServerOrder $order) {
            $live = [];
            if ($order->status === 'provisioned' && $order->interserver_server_id) {
                $live = $this->interserver->getServer($order->interserver_server_id);
            }
            return [
                'order' => $order,
                'live'  => $live,
            ];
        });

        return view('dashboard.dedicated.index', compact('instances'));
    }

    /**
     * Browse InterServer's live Rapid Deploy / Buy-It-Now marketplace inventory.
     */
    public function catalog(): View|RedirectResponse
    {
        $listings = $this->interserver->getMarketplaceServers();

        if (! is_array($listings)) {
            return back()->with('error', 'Unable to reach InterServer right now. Please try again shortly.');
        }

        $servers = collect($listings)->keyBy('server_id')->all();

        $currencyCode = session('currency', 'USD');
        $currency     = CurrencyConverter::symbol($currencyCode);

        $prices = [];
        foreach ($servers as $assetId => $listing) {
            $priceUsd = $this->computePrice((int) $assetId, (string) ($listing['price'] ?? '0'));
            $prices[$assetId] = ProductCatalog::price('dedicated', (string) $assetId, 1, $currencyCode, $priceUsd);
        }

        return view('dashboard.dedicated.catalog', compact('servers', 'prices', 'currency'));
    }

    /**
     * AJAX: fetch configurable options (bandwidth/OS/control panel/RAID) for one
     * marketplace asset, plus our computed price for it.
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'integer'],
        ]);

        $listings = $this->interserver->getMarketplaceServers();
        $listing  = collect($listings)->firstWhere('server_id', (string) $validated['asset_id']);

        if (! $listing) {
            return response()->json(['error' => 'This server is no longer available.'], 422);
        }

        $options = $this->interserver->getBuyNowOptions((int) $validated['asset_id']);

        if ($options['error'] ?? false) {
            return response()->json(['error' => $options['message'] ?? 'This configuration is not available.'], 422);
        }

        $priceUsd = $this->computePrice((int) $validated['asset_id'], (string) ($listing['price'] ?? '0'));
        $price    = ProductCatalog::price('dedicated', (string) $validated['asset_id'], 1, session('currency', 'USD'), $priceUsd);

        return response()->json([
            'price'      => $price,
            'bandwidth'  => $options['bandwidth'] ?? [],
            'ips'        => $options['ips'] ?? [],
            'os'         => $options['os'] ?? [],
            'cp'         => $options['cp'] ?? [],
            'raid'       => $options['raid'] ?? [],
        ]);
    }

    /**
     * Create a local invoice for the chosen marketplace listing. Real InterServer
     * provisioning happens only after the invoice is marked paid (see
     * app/Console/Commands/ProvisionPaidDedicated.php).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id'  => ['required', 'integer'],
            'hostname'  => ['required', 'string', 'regex:/^.*\..*\..*$/'],
            'rootpass'  => ['required', 'string', 'min:8'],
            'os'        => ['nullable', 'integer'],
            'bandwidth' => ['nullable', 'integer'],
            'ips'       => ['nullable', 'integer'],
            'cp'        => ['nullable', 'integer'],
            'raid'      => ['nullable', 'integer'],
            'comment'   => ['nullable', 'string'],
        ]);

        $clientId = session('clientId');

        $listings = $this->interserver->getMarketplaceServers();
        $listing  = collect($listings)->firstWhere('server_id', (string) $validated['asset_id']);

        if (! $listing) {
            return back()->withErrors('This server is no longer available.')->withInput();
        }

        $priceUsd = $this->computePrice((int) $validated['asset_id'], (string) ($listing['price'] ?? '0'));
        $cpuLabel = is_array($listing['cpu'] ?? null) ? ($listing['cpu'][0] ?? 'Dedicated Server') : ($listing['cpu'] ?? 'Dedicated Server');

        $currencyCode = session('currency', 'USD');
        $price = ProductCatalog::price('dedicated', (string) $validated['asset_id'], 1, $currencyCode, $priceUsd);
        $client = Client::find($clientId);

        $orderDescription = "Dedicated Server — {$cpuLabel} ({$validated['hostname']})";

        $invoice = $this->invoices->createAt($client, $orderDescription, $price, $currencyCode);

        DedicatedServerOrder::create([
            'client_id'     => $clientId,
            'invoice_id'    => $invoice->id,
            'status'        => 'pending_payment',
            'price'         => $invoice->total,
            'billing_cycle' => 1,
            'config'        => [
                'asset_id'   => $validated['asset_id'],
                'hostname'   => $validated['hostname'],
                'rootpass'   => Crypt::encryptString($validated['rootpass']),
                'os'         => $validated['os'] ?? null,
                'bandwidth'  => $validated['bandwidth'] ?? null,
                'ips'        => $validated['ips'] ?? null,
                'cp'         => $validated['cp'] ?? null,
                'raid'       => $validated['raid'] ?? null,
                'comment'    => $validated['comment'] ?? '',
                'listing'    => $listing,
                'currency'   => $currencyCode,
                'amount_usd' => $priceUsd,
            ],
        ]);

        Mail::to($client->email)->send(new OrderConfirmationMail(
            $client->firstname,
            $orderDescription,
            $invoice->total,
            $currencyCode,
            $invoice->id,
        ));

        return redirect()->route('billing.show', $invoice->id)
            ->with('success', 'Your order has been created. Your Dedicated Server will be provisioned automatically as soon as this invoice is paid.');
    }

    /**
     * Dedicated Server detail page — scoped to the logged-in client's own order.
     */
    public function show(int $orderId): View
    {
        $order = DedicatedServerOrder::where('client_id', session('clientId'))->findOrFail($orderId);

        $live = [];
        if ($order->status === 'provisioned' && $order->interserver_server_id) {
            $live = $this->interserver->getServer($order->interserver_server_id);
        }

        return view('dashboard.dedicated.show', compact('order', 'live'));
    }

    /**
     * Cancellation — the only lifecycle action exposed here. Power/console
     * management on bare metal goes through InterServer's IPMI endpoints, which
     * are out of scope for this dashboard's self-service actions today.
     */
    public function action(Request $request, int $orderId): JsonResponse
    {
        $order = DedicatedServerOrder::where('client_id', session('clientId'))->findOrFail($orderId);

        $request->validate([
            'command' => ['required', 'string', 'in:cancel'],
        ]);

        if ($order->status !== 'provisioned' || ! $order->interserver_server_id) {
            return response()->json(['success' => false, 'message' => 'This server is not active yet.'], 422);
        }

        $result = $this->interserver->cancelServer($order->interserver_server_id);

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'The action could not be completed.'], 422);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => $result['text'] ?? 'Server cancelled.', 'data' => $result]);
    }

    private function computePrice(int $assetId, string $rawPrice): float
    {
        $cost = (float) preg_replace('/[^0-9.]/', '', $rawPrice);

        $overrides = PricingConfig::dedicatedServerOverrides();
        if (isset($overrides[$assetId])) {
            return (float) $overrides[$assetId];
        }

        return round($cost * (1 + PricingConfig::dedicatedMarkupPercent() / 100), 2);
    }
}
