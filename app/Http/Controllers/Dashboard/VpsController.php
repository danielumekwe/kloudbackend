<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Client;
use App\Models\VpsOrder;
use App\Services\InterServerService;
use App\Services\WhmcsService;
use App\Support\CurrencyConverter;
use App\Support\PricingConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class VpsController extends Controller
{
    public function __construct(
        private InterServerService $interserver,
        private WhmcsService $whmcs,
    ) {}

    /**
     * "My VPS" — instances belonging to the logged-in client only.
     */
    public function index(): View
    {
        $clientId = session('clientId');

        $orders = VpsOrder::where('client_id', $clientId)
            ->whereIn('status', ['provisioned', 'paid', 'pending_payment', 'failed', 'cancelled'])
            ->latest()
            ->get();

        // Enrich provisioned orders with live status/IP from InterServer.
        $instances = $orders->map(function (VpsOrder $order) {
            $live = [];
            if ($order->status === 'provisioned' && $order->interserver_vps_id) {
                $live = $this->interserver->getVps($order->interserver_vps_id);
            }
            return [
                'order' => $order,
                'live'  => $live,
            ];
        });

        // Cancelled first, then anything needing attention (failed/awaiting payment),
        // then active — recency (already sorted via latest() above) breaks ties within
        // each group since sortBy() is a stable sort.
        $statusOrder = [
            'cancelled'       => 0,
            'failed'          => 1,
            'pending_payment' => 2,
            'paid'            => 2,
            'provisioned'     => 3,
        ];
        $instances = $instances->sortBy(fn ($item) => $statusOrder[$item['order']->status] ?? 99)->values();

        return view('dashboard.vps.index', compact('instances'));
    }

    /**
     * Catalog / order builder for a given sidebar category (linux-vps, managed-vps, storage-vps).
     */
    public function catalog(string $category): View|RedirectResponse
    {
        $plan = PricingConfig::vpsPlan($category);

        if (! $plan) {
            abort(404);
        }

        $catalogData = $this->interserver->getOrderCatalog();

        if ($catalogData['error'] ?? false) {
            return back()->with('error', 'Unable to reach InterServer right now. Please try again shortly.');
        }

        $locations  = $catalogData['locationNames'] ?? [];
        $stock      = $catalogData['locationStock'] ?? [];
        $osNames    = $catalogData['osNames'] ?? [];
        $templates  = $catalogData['templates'][$plan['platform']] ?? [];
        $maxSlices  = (int) ($catalogData['maxSlices'] ?? 8);
        $minSlices  = (int) ($plan['min_slices'] ?? 1);
        $recommendedMinSlices = (int) ($plan['recommended_min_slices'] ?? $minSlices);
        $periods    = config('vps_pricing.period_months');

        $currencyCode = session('currency', 'USD');
        $currency     = CurrencyConverter::find($currencyCode) ?? CurrencyConverter::default();

        $controlpanelOptions = $plan['controlpanel_options'] ?? [];
        foreach ($controlpanelOptions as $key => $option) {
            $controlpanelOptions[$key]['price'] = CurrencyConverter::convertFromUsd($option['price'], $currency['code']);
        }

        return view('dashboard.vps.catalog', compact(
            'category', 'plan', 'locations', 'stock', 'osNames', 'templates',
            'maxSlices', 'minSlices', 'recommendedMinSlices', 'controlpanelOptions', 'periods', 'currency',
        ));
    }

    /**
     * AJAX: validate config against InterServer (stock/compatibility) and return OUR computed price.
     */
    public function quote(Request $request): JsonResponse
    {
        $plan = PricingConfig::vpsPlan((string) $request->input('category'));
        if (! $plan) {
            return response()->json(['error' => 'Unknown plan category.'], 422);
        }

        $minSlices = (int) ($plan['min_slices'] ?? 1);

        $validated = $request->validate([
            'category' => ['required', 'string'],
            'osDistro' => ['required', 'string'],
            'osVersion' => ['required', 'string'],
            'slices'   => ['required', 'integer', "min:{$minSlices}", 'max:32'],
            'location' => ['required', 'integer'],
            'period'   => ['required', 'integer', 'in:1,6,12,24,36'],
            'hostname' => ['required', 'string'],
            'controlpanel' => $this->controlpanelRule($plan),
        ]);

        $check = $this->interserver->quoteOrder([
            'vpsPlatform'  => $plan['platform'],
            'osDistro'     => $validated['osDistro'],
            'osVersion'    => $validated['osVersion'],
            'slices'       => $validated['slices'],
            'location'     => $validated['location'],
            'period'       => $validated['period'],
            'controlpanel' => $plan['controlpanel'],
            'hostname'     => $validated['hostname'],
            'rootpass'     => 'Tmp1234!Tmp1234!', // placeholder for the stock-check; the real password is set at order time
        ]);

        if (! ($check['continue'] ?? false)) {
            return response()->json(['error' => implode(' ', $check['errors'] ?? ['This configuration is not available.'])], 422);
        }

        $priceUsd = $this->computePrice($plan, (int) $validated['slices'], (int) $validated['period'], $validated['controlpanel'] ?? null);
        $price    = CurrencyConverter::convertFromUsd($priceUsd, session('currency', 'USD'));

        return response()->json(['price' => $price]);
    }

    /**
     * Create a WHMCS invoice for the chosen config. Real InterServer provisioning happens
     * only after the invoice is marked paid (see app/Console/Commands/ProvisionPaidVps.php).
     */
    public function store(Request $request, string $category): RedirectResponse
    {
        $plan = PricingConfig::vpsPlan($category);
        if (! $plan) {
            abort(404);
        }

        $minSlices = (int) ($plan['min_slices'] ?? 1);

        $validated = $request->validate([
            'osDistro'  => ['required', 'string'],
            'osVersion' => ['required', 'string'],
            'slices'    => ['required', 'integer', "min:{$minSlices}", 'max:32'],
            'location'  => ['required', 'integer'],
            'period'    => ['required', 'integer', 'in:1,6,12,24,36'],
            'hostname'  => ['required', 'string', 'regex:/^.*\..*\..*$/'],
            'rootpass'  => ['required', 'string', 'min:8'],
            'controlpanel' => $this->controlpanelRule($plan),
        ]);
        $validated['category'] = $category;

        $clientId = session('clientId');

        $check = $this->interserver->quoteOrder([
            'vpsPlatform'  => $plan['platform'],
            'osDistro'     => $validated['osDistro'],
            'osVersion'    => $validated['osVersion'],
            'slices'       => $validated['slices'],
            'location'     => $validated['location'],
            'period'       => $validated['period'],
            'controlpanel' => $plan['controlpanel'],
            'hostname'     => $validated['hostname'],
            'rootpass'     => $validated['rootpass'],
        ]);

        if (! ($check['continue'] ?? false)) {
            return back()->withErrors(implode(' ', $check['errors'] ?? ['This configuration is not available.']))->withInput();
        }

        $priceUsd = $this->computePrice($plan, (int) $validated['slices'], (int) $validated['period'], $validated['controlpanel'] ?? null);

        $currencyCode   = session('currency', 'USD');
        $currencyRate   = CurrencyConverter::refreshFresh($currencyCode); // live rate — real money, never use the cached one here
        $price          = round($priceUsd * $currencyRate, 2);

        // ensureClientCurrency/createInvoice are WHMCS-bound and must resolve through
        // whmcs_client_id, never the local id directly — they only coincide for
        // clients migrated before the WHMCS exit (see the migration plan).
        $client = Client::find($clientId);
        $whmcsClientId = $client?->whmcs_client_id;

        if (! $whmcsClientId) {
            return back()->with('error', 'We\'re still setting up your billing account. Please try again shortly or contact support.')->withInput();
        }

        if (! CurrencyConverter::ensureClientCurrency($whmcsClientId, $currencyCode)) {
            return back()->with('error', 'Could not switch your billing currency. Please try again or contact support.')->withInput();
        }

        $orderDescription = "{$plan['label']} — {$validated['slices']} slice(s) — {$validated['hostname']}";

        $invoice = $this->whmcs->createInvoice(
            $whmcsClientId,
            $orderDescription,
            $price,
        );

        if (($invoice['result'] ?? '') !== 'success') {
            return back()->with('error', 'Could not create your invoice. Please contact support.')->withInput();
        }

        VpsOrder::create([
            'client_id'        => $clientId,
            'category'         => $validated['category'],
            'whmcs_invoice_id' => $invoice['invoiceid'],
            'status'           => 'pending_payment',
            'price'            => $price,
            'billing_cycle'    => $validated['period'],
            'config'           => [
                'platform'     => $plan['platform'],
                'controlpanel' => $plan['controlpanel'], // value sent to InterServer (we license panels ourselves)
                'panelLicense' => $validated['controlpanel'] ?? null,
                'osDistro'     => $validated['osDistro'],
                'osVersion'    => $validated['osVersion'],
                'slices'       => $validated['slices'],
                'location'     => $validated['location'],
                'period'       => $validated['period'],
                'hostname'     => $validated['hostname'],
                'rootpass'     => Crypt::encryptString($validated['rootpass']),
                'currency'     => $currencyCode, // currency the client was actually billed in
                'amount_usd'   => $priceUsd, // USD cost basis for InterServer reconciliation, independent of rate drift
            ],
        ]);

        Mail::to($client->email)->send(new OrderConfirmationMail(
            $client->firstname,
            $orderDescription,
            $price,
            $currencyCode,
            $invoice['invoiceid'],
        ));

        return redirect()->route('billing.show', $invoice['invoiceid'])
            ->with('success', 'Your order has been created. Your VPS will be provisioned automatically as soon as this invoice is paid.');
    }

    /**
     * VPS detail / management page — scoped to the logged-in client's own instance.
     */
    public function show(int $orderId): View
    {
        $order = VpsOrder::where('client_id', session('clientId'))->findOrFail($orderId);

        $live = [];
        if ($order->status === 'provisioned' && $order->interserver_vps_id) {
            $live = $this->interserver->getVps($order->interserver_vps_id);
        }

        return view('dashboard.vps.show', compact('order', 'live'));
    }

    /**
     * Lifecycle / maintenance actions, scoped to the logged-in client's own instance.
     */
    public function action(Request $request, int $orderId): JsonResponse
    {
        $order = VpsOrder::where('client_id', session('clientId'))->findOrFail($orderId);

        if ($order->status !== 'provisioned' || ! $order->interserver_vps_id) {
            return response()->json(['success' => false, 'message' => 'This VPS is not active yet.'], 422);
        }

        $vpsId = $order->interserver_vps_id;

        $request->validate([
            'command'       => ['required', 'string', 'in:' . implode(',', [
                'start', 'stop', 'restart', 'changepassword', 'reinstall',
                'backup', 'deletebackup', 'downloadbackup', 'restore',
                'blocksmtp', 'changehostname', 'changetimezone', 'changewebuzopassword',
                'disablecd', 'ejectcd', 'insertcd', 'disablequota', 'enablequota',
                'reversedns', 'setupvnc', 'viewdesktop', 'trafficusage',
                'buyhdspace', 'buyip', 'resizeslices', 'cancel',
            ])],
            'password'      => ['nullable', 'string', 'min:8'],
            'localPassword' => ['nullable', 'string'],
            'template'      => ['nullable', 'string'],
            'file'          => ['nullable', 'string'],
            'backup'        => ['nullable', 'string'],
            'hostname'      => ['nullable', 'string'],
            'timezone'      => ['nullable', 'string'],
            'url'           => ['nullable', 'string'],
            'vnc'           => ['nullable', 'string'],
            'ips'           => ['nullable', 'array'],
            'size'          => ['nullable', 'integer'],
            'slices'        => ['nullable', 'integer'],
        ]);

        $result = match ($request->command) {
            'start'   => $this->interserver->startVps($vpsId),
            'stop'    => $this->interserver->stopVps($vpsId),
            'restart' => $this->interserver->restartVps($vpsId),
            'changepassword' => $request->filled('password')
                ? $this->interserver->changeRootPassword($vpsId, $request->password)
                : ['error' => true, 'message' => 'A new password is required.'],
            'reinstall' => ($request->filled('template') && $request->filled('localPassword'))
                ? $this->interserver->reinstallOs($vpsId, $request->template, $request->localPassword, $request->password)
                : ['error' => true, 'message' => 'A template and your InterServer account password are required to reinstall.'],
            'backup'          => $this->interserver->getVpsBackup($vpsId),
            'deletebackup'    => $request->filled('file')
                ? $this->interserver->deleteVpsBackup($vpsId, $request->file)
                : ['error' => true, 'message' => 'A backup filename is required.'],
            'downloadbackup'  => $request->filled('file')
                ? $this->interserver->downloadVpsBackup($vpsId, $request->file)
                : ['error' => true, 'message' => 'A backup filename is required.'],
            'restore'         => ($request->filled('backup') && $request->filled('password'))
                ? $this->interserver->restoreVps($vpsId, $request->backup, $request->password)
                : ['error' => true, 'message' => 'A backup filename and your InterServer account password are required to restore.'],
            'blocksmtp'       => $this->interserver->blockSmtp($vpsId),
            'changehostname'  => $request->filled('hostname')
                ? $this->interserver->changeHostname($vpsId, $request->hostname)
                : ['error' => true, 'message' => 'A hostname is required.'],
            'changetimezone'  => $request->filled('timezone')
                ? $this->interserver->changeTimezone($vpsId, $request->timezone)
                : ['error' => true, 'message' => 'A timezone is required.'],
            'changewebuzopassword' => $request->filled('password')
                ? $this->interserver->changeWebuzoPassword($vpsId, $request->password)
                : ['error' => true, 'message' => 'A new password is required.'],
            'disablecd'       => $this->interserver->disableCd($vpsId),
            'ejectcd'         => $this->interserver->ejectCd($vpsId),
            'insertcd'        => $request->filled('url')
                ? $this->interserver->insertCd($vpsId, $request->url)
                : ['error' => true, 'message' => 'An ISO URL is required.'],
            'disablequota'    => $this->interserver->disableQuota($vpsId),
            'enablequota'     => $this->interserver->enableQuota($vpsId),
            'reversedns'      => $request->filled('ips')
                ? $this->interserver->setReverseDns($vpsId, $request->ips)
                : ['error' => true, 'message' => 'PTR records are required.'],
            'setupvnc'        => $request->filled('vnc')
                ? $this->interserver->setupVnc($vpsId, $request->vnc)
                : ['error' => true, 'message' => 'A source IP is required.'],
            'viewdesktop'     => $this->interserver->refreshViewDesktop($vpsId),
            'trafficusage'    => $this->interserver->getTrafficUsage($vpsId),
            'buyhdspace'      => $request->filled('size')
                ? $this->interserver->buyHdSpace($vpsId, (int) $request->size)
                : ['error' => true, 'message' => 'A target disk size in GB is required.'],
            'buyip'           => $this->interserver->buyIp($vpsId),
            'resizeslices'    => $request->filled('slices')
                ? $this->interserver->resizeSlices($vpsId, (int) $request->slices)
                : ['error' => true, 'message' => 'A target slice count is required.'],
            'cancel'          => $this->interserver->cancelVps($vpsId),
        };

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'The action could not be completed.'], 422);
        }

        if ($request->command === 'cancel') {
            $order->update(['status' => 'cancelled']);
        }

        return response()->json(['success' => true, 'message' => $result['text'] ?? 'Action completed successfully.', 'data' => $result]);
    }

    /**
     * Validation rule for the customer-chosen control panel license. Required and
     * restricted to the plan's configured options when the plan offers any.
     */
    private function controlpanelRule(array $plan): array
    {
        $options = $plan['controlpanel_options'] ?? [];

        if (empty($options)) {
            return ['nullable', 'string'];
        }

        return ['required', 'string', 'in:' . implode(',', array_keys($options))];
    }

    private function computePrice(array $plan, int $slices, int $period, ?string $controlpanel = null): float
    {
        $monthly = $plan['price_per_slice'] * $slices;

        if (! empty($plan['controlpanel_options'])) {
            $monthly += $plan['controlpanel_options'][$controlpanel]['price'] ?? 0;
        } elseif (($plan['controlpanel'] ?? 'none') !== 'none') {
            $monthly += $plan['controlpanel_price'] ?? 0;
        }

        $discount = PricingConfig::vpsPeriodDiscount($period);

        return round($monthly * $period * $discount, 2);
    }
}
