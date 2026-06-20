<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Client;
use App\Models\SslOrder;
use App\Services\InterServerService;
use App\Services\WhmcsService;
use App\Support\CurrencyConverter;
use App\Support\PricingConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SslController extends Controller
{
    public function __construct(
        private InterServerService $interserver,
        private WhmcsService $whmcs,
    ) {}

    /**
     * "My SSL Certificates" — certificates belonging to the logged-in client only.
     */
    public function index(): View
    {
        $clientId = session('clientId');

        $orders = SslOrder::where('client_id', $clientId)
            ->whereIn('status', ['provisioned', 'paid', 'pending_payment', 'failed'])
            ->latest()
            ->get();

        $instances = $orders->map(function (SslOrder $order) {
            $live = [];
            if ($order->status === 'provisioned' && $order->interserver_ssl_id) {
                $live = $this->interserver->getSsl($order->interserver_ssl_id);
            }
            return [
                'order' => $order,
                'live'  => $live,
            ];
        });

        return view('dashboard.ssl.index', compact('instances'));
    }

    /**
     * SSL order builder — package picker + hostname/approver/CSR fields.
     */
    public function catalog(): View|RedirectResponse
    {
        $catalogData = $this->interserver->getSslOrderCatalog();

        if ($catalogData['error'] ?? false) {
            return back()->with('error', 'Unable to reach InterServer right now. Please try again shortly.');
        }

        $packages = collect($catalogData['serviceTypes'] ?? [])
            ->filter(fn ($p) => ($p['services_buyable'] ?? false) && ($p['services_id'] ?? null))
            ->values();

        $currencyCode = session('currency', 'USD');
        $currency     = CurrencyConverter::symbol($currencyCode);

        $prices = $packages->mapWithKeys(fn ($p) => [
            $p['services_id'] => CurrencyConverter::convertFromUsd(
                $this->computePrice((int) $p['services_id'], (float) $p['services_cost'], 12),
                $currencyCode,
            ),
        ]);

        $periods  = config('ssl_pricing.period_months');

        return view('dashboard.ssl.catalog', compact('packages', 'prices', 'periods', 'currency'));
    }

    /**
     * AJAX: validate config against InterServer (dry run) and return OUR computed price.
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id'     => ['required', 'integer'],
            'hostname'       => ['required', 'string'],
            'approver_email' => ['required', 'email'],
            'frequency'      => ['required', 'integer', 'in:12,24,36'],
            'csr_type'       => ['nullable', 'string', 'in:generated,provided'],
            'csr'            => ['nullable', 'string'],
        ]);

        $catalogData = $this->interserver->getSslOrderCatalog();
        $cost = $catalogData['packageCosts'][$validated['package_id']] ?? null;

        if ($cost === null) {
            return response()->json(['error' => 'This package is no longer available.'], 422);
        }

        $check = $this->interserver->quoteSslOrder([
            'ssl'            => $validated['package_id'],
            'hostname'       => $validated['hostname'],
            'approver_email' => $validated['approver_email'],
            'frequency'      => $validated['frequency'],
            'csr_type'       => $validated['csr_type'] ?? 'generated',
            'csr'            => $validated['csr'] ?? '',
        ]);

        if (! ($check['continue'] ?? true)) {
            return response()->json(['error' => implode(' ', $check['errors'] ?? ['This configuration is not available.'])], 422);
        }

        $priceUsd = $this->computePrice($validated['package_id'], (float) $cost, $validated['frequency']);
        $price    = CurrencyConverter::convertFromUsd($priceUsd, session('currency', 'USD'));

        return response()->json(['price' => $price]);
    }

    /**
     * Create a WHMCS invoice for the chosen config. Real InterServer provisioning happens
     * only after the invoice is marked paid (see app/Console/Commands/ProvisionPaidSsl.php).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package_id'     => ['required', 'integer'],
            'hostname'       => ['required', 'string'],
            'approver_email' => ['required', 'email'],
            'frequency'      => ['required', 'integer', 'in:12,24,36'],
            'csr_type'       => ['required', 'string', 'in:generated,provided'],
            'csr'            => ['required_if:csr_type,provided', 'nullable', 'string'],
            'firstname'      => ['nullable', 'string'],
            'lastname'       => ['nullable', 'string'],
            'email'          => ['nullable', 'email'],
            'address'        => ['nullable', 'string'],
            'city'           => ['nullable', 'string'],
            'state'          => ['nullable', 'string'],
            'zip'            => ['nullable', 'string'],
            'country'        => ['nullable', 'string'],
            'phone'          => ['nullable', 'string'],
            'company'        => ['nullable', 'string'],
            'department'     => ['nullable', 'string'],
        ]);

        $clientId = session('clientId');

        $catalogData = $this->interserver->getSslOrderCatalog();
        $cost = $catalogData['packageCosts'][$validated['package_id']] ?? null;
        $packageName = $catalogData['serviceTypes'][$validated['package_id']]['services_name'] ?? "Package #{$validated['package_id']}";

        if ($cost === null) {
            return back()->withErrors('This package is no longer available.')->withInput();
        }

        $orderConfig = $validated;

        $check = $this->interserver->quoteSslOrder(array_merge($orderConfig, ['ssl' => $validated['package_id']]));

        if (! ($check['continue'] ?? true)) {
            return back()->withErrors(implode(' ', $check['errors'] ?? ['This configuration is not available.']))->withInput();
        }

        $priceUsd = $this->computePrice($validated['package_id'], (float) $cost, $validated['frequency']);

        $currencyCode = session('currency', 'USD');
        $currencyRate = CurrencyConverter::refreshFresh($currencyCode);
        $price        = round($priceUsd * $currencyRate, 2);

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

        $orderDescription = "SSL Certificate — {$packageName} — {$validated['hostname']}";

        $invoice = $this->whmcs->createInvoice(
            $whmcsClientId,
            $orderDescription,
            $price,
        );

        if (($invoice['result'] ?? '') !== 'success') {
            return back()->with('error', 'Could not create your invoice. Please contact support.')->withInput();
        }

        SslOrder::create([
            'client_id'        => $clientId,
            'whmcs_invoice_id' => $invoice['invoiceid'],
            'status'           => 'pending_payment',
            'price'            => $price,
            'billing_cycle'    => $validated['frequency'],
            'config'           => array_merge($orderConfig, [
                'package_id' => $validated['package_id'],
                'currency'   => $currencyCode,
                'amount_usd' => $priceUsd,
            ]),
        ]);

        Mail::to($client->email)->send(new OrderConfirmationMail(
            $client->firstname,
            $orderDescription,
            $price,
            $currencyCode,
            $invoice['invoiceid'],
        ));

        return redirect()->route('billing.show', $invoice['invoiceid'])
            ->with('success', 'Your order has been created. Your certificate will be issued automatically as soon as this invoice is paid.');
    }

    /**
     * SSL certificate detail page — scoped to the logged-in client's own certificate.
     */
    public function show(int $orderId): View
    {
        $order = SslOrder::where('client_id', session('clientId'))->findOrFail($orderId);

        $live = [];
        if ($order->status === 'provisioned' && $order->interserver_ssl_id) {
            $live = $this->interserver->getSsl($order->interserver_ssl_id);
        }

        return view('dashboard.ssl.show', compact('order', 'live'));
    }

    /**
     * Maintenance actions, scoped to the logged-in client's own certificate.
     */
    public function action(Request $request, int $orderId): JsonResponse
    {
        $order = SslOrder::where('client_id', session('clientId'))->findOrFail($orderId);

        if ($order->status !== 'provisioned' || ! $order->interserver_ssl_id) {
            return response()->json(['success' => false, 'message' => 'This certificate is not active yet.'], 422);
        }

        $sslId = $order->interserver_ssl_id;

        $request->validate([
            'command' => ['required', 'string', 'in:cancel,resendwelcome'],
        ]);

        $result = match ($request->command) {
            'cancel'        => $this->interserver->cancelSsl($sslId),
            'resendwelcome' => $this->interserver->getSslWelcomeEmail($sslId),
        };

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'The action could not be completed.'], 422);
        }

        if ($request->command === 'cancel') {
            $order->update(['status' => 'cancelled']);
        }

        return response()->json(['success' => true, 'message' => $result['text'] ?? 'Action completed successfully.']);
    }

    private function computePrice(int $packageId, float $baseAnnualCost, int $frequencyMonths): float
    {
        $overrides = PricingConfig::sslPackageOverrides();
        if (isset($overrides[$packageId])) {
            return (float) $overrides[$packageId];
        }

        $markup   = PricingConfig::sslMarkupPercent();
        $marked   = $baseAnnualCost * (1 + $markup / 100);
        $years    = $frequencyMonths / 12;
        $discount = PricingConfig::sslPeriodDiscount($frequencyMonths);

        return round($marked * $years * $discount, 2);
    }
}
