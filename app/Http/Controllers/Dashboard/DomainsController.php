<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DomainOrder;
use App\Models\DomainRenewal;
use App\Services\InterServerService;
use App\Services\WhmcsService;
use App\Support\CurrencyConverter;
use App\Support\PricingConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainsController extends Controller
{
    public function __construct(
        private InterServerService $interserver,
        private WhmcsService $whmcs,
    ) {}

    /**
     * "My Domains" — registrations belonging to the logged-in client only.
     */
    public function index(): View
    {
        $clientId = session('clientId');

        $orders = DomainOrder::where('client_id', $clientId)
            ->whereIn('status', ['provisioned', 'paid', 'pending_payment', 'failed'])
            ->latest()
            ->get();

        return view('dashboard.domains.index', compact('orders'));
    }

    /**
     * Domain search — the real entry point for "Order a Plan → Domains".
     */
    public function search(): View
    {
        return view('dashboard.domains.search', [
            'currency' => CurrencyConverter::symbol(session('currency', 'USD')),
        ]);
    }

    /**
     * AJAX: live availability + price check for a single domain name.
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string'],
        ]);

        $result = $this->interserver->lookupDomain($validated['domain']);

        if ($result['error'] ?? false) {
            return response()->json(['error' => $result['message'] ?? 'Could not look up this domain.'], 422);
        }

        $tld = $this->extractTld($validated['domain']);
        $currencyCode = session('currency', 'USD');

        return response()->json([
            'available'     => $result['available'] ?? false,
            'premium'       => $result['premium'] ?? false,
            'whois_privacy' => $result['whois_privacy'] ?? false,
            'price'         => CurrencyConverter::convertFromUsd($this->computePrice($tld, (float) ($result['new'] ?? 0)), $currencyCode),
            'renewal_price' => CurrencyConverter::convertFromUsd($this->computePrice($tld, (float) ($result['renewal'] ?? 0)), $currencyCode),
            'fields'        => $result['fields'] ?? [],
        ]);
    }

    /**
     * Registrant-contact + years + whois-privacy order builder for a chosen domain.
     */
    public function catalog(Request $request): View|RedirectResponse
    {
        $domain = $request->query('domain');

        if (! $domain) {
            return redirect()->route('domains.search');
        }

        $lookup = $this->interserver->lookupDomain($domain);

        if (! ($lookup['available'] ?? false)) {
            return redirect()->route('domains.search')->with('error', 'That domain is not available to register.');
        }

        $tld = $this->extractTld($domain);
        $currencyCode = session('currency', 'USD');

        return view('dashboard.domains.catalog', [
            'domain'        => $domain,
            'tld'           => $tld,
            'fields'        => $lookup['fields'] ?? [],
            'whoisPrivacy'  => $lookup['whois_privacy'] ?? false,
            'price'         => CurrencyConverter::convertFromUsd($this->computePrice($tld, (float) ($lookup['new'] ?? 0)), $currencyCode),
            'currency'      => CurrencyConverter::symbol($currencyCode),
        ]);
    }

    /**
     * AJAX: recompute price for a chosen number of years / whois-privacy toggle.
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain'              => ['required', 'string'],
            'registration_years'  => ['required', 'integer', 'min:1', 'max:10'],
            'whois_privacy'       => ['nullable', 'boolean'],
        ]);

        $lookup = $this->interserver->lookupDomain($validated['domain']);

        if (! ($lookup['available'] ?? false)) {
            return response()->json(['error' => 'This domain is no longer available.'], 422);
        }

        $tld = $this->extractTld($validated['domain']);
        $priceUsd = $this->computePrice($tld, (float) ($lookup['new'] ?? 0)) * $validated['registration_years'];

        if ($validated['whois_privacy'] ?? false) {
            $priceUsd += PricingConfig::domainsWhoisPrivacyPrice();
        }

        $price = CurrencyConverter::convertFromUsd(round($priceUsd, 2), session('currency', 'USD'));

        return response()->json(['price' => $price]);
    }

    /**
     * Create a WHMCS invoice for a new registration. Real InterServer provisioning happens
     * only after the invoice is marked paid (see app/Console/Commands/ProvisionPaidDomain.php).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateOrderRequest($request);

        $clientId = session('clientId');
        $tld = $this->extractTld($validated['domain']);

        $lookup = $this->interserver->lookupDomain($validated['domain']);
        if (! ($lookup['available'] ?? false)) {
            return back()->withErrors('This domain is no longer available.')->withInput();
        }

        $unitPrice = $this->computePrice($tld, (float) ($lookup['new'] ?? 0));
        $priceUsd = $unitPrice * $validated['registration_years'];
        if ($validated['whois_privacy'] ?? false) {
            $priceUsd += PricingConfig::domainsWhoisPrivacyPrice();
        }
        $priceUsd = round($priceUsd, 2);

        $currencyCode = session('currency', 'USD');
        $currencyRate = CurrencyConverter::refreshFresh($currencyCode);
        $price        = round($priceUsd * $currencyRate, 2);

        if (! CurrencyConverter::ensureClientCurrency($clientId, $currencyCode)) {
            return back()->with('error', 'Could not switch your billing currency. Please try again or contact support.')->withInput();
        }

        $invoice = $this->whmcs->createInvoice(
            $clientId,
            "Domain Registration — {$validated['domain']} ({$validated['registration_years']}yr)",
            $price,
        );

        if (($invoice['result'] ?? '') !== 'success') {
            return back()->with('error', 'Could not create your invoice. Please contact support.')->withInput();
        }

        DomainOrder::create([
            'client_id'           => $clientId,
            'whmcs_invoice_id'    => $invoice['invoiceid'],
            'domain_name'         => explode('.', $validated['domain'], 2)[0],
            'tld'                 => $tld,
            'order_type'          => 'register',
            'registration_years'  => $validated['registration_years'],
            'status'              => 'pending_payment',
            'price'               => $price,
            'whois_privacy'       => $validated['whois_privacy'] ?? false,
            'registrant_contact'  => $validated['contact'],
            'config'              => [
                'currency'   => $currencyCode,
                'amount_usd' => $priceUsd,
            ],
        ]);

        return redirect()->route('billing.show', $invoice['invoiceid'])
            ->with('success', 'Your domain order has been created. It will be registered automatically as soon as this invoice is paid.');
    }

    /**
     * Create a WHMCS invoice for a transfer-in. Real InterServer provisioning happens
     * only after the invoice is marked paid.
     */
    public function transferStore(Request $request): RedirectResponse
    {
        $validated = $this->validateOrderRequest($request);
        $validated['auth_code'] = $request->validate(['auth_code' => ['required', 'string']])['auth_code'];

        $clientId = session('clientId');
        $tld = $this->extractTld($validated['domain']);

        $lookup = $this->interserver->lookupDomain($validated['domain']);
        $priceUsd = round($this->computePrice($tld, (float) ($lookup['transfer'] ?? $lookup['new'] ?? 0)), 2);

        $currencyCode = session('currency', 'USD');
        $currencyRate = CurrencyConverter::refreshFresh($currencyCode);
        $price        = round($priceUsd * $currencyRate, 2);

        if (! CurrencyConverter::ensureClientCurrency($clientId, $currencyCode)) {
            return back()->with('error', 'Could not switch your billing currency. Please try again or contact support.')->withInput();
        }

        $invoice = $this->whmcs->createInvoice(
            $clientId,
            "Domain Transfer — {$validated['domain']}",
            $price,
        );

        if (($invoice['result'] ?? '') !== 'success') {
            return back()->with('error', 'Could not create your invoice. Please contact support.')->withInput();
        }

        DomainOrder::create([
            'client_id'           => $clientId,
            'whmcs_invoice_id'    => $invoice['invoiceid'],
            'domain_name'         => explode('.', $validated['domain'], 2)[0],
            'tld'                 => $tld,
            'order_type'          => 'transfer',
            'registration_years'  => $validated['registration_years'],
            'status'              => 'pending_payment',
            'price'               => $price,
            'whois_privacy'       => $validated['whois_privacy'] ?? false,
            'registrant_contact'  => $validated['contact'],
            'config'              => [
                'auth_code'  => $validated['auth_code'],
                'currency'   => $currencyCode,
                'amount_usd' => $priceUsd,
            ],
        ]);

        return redirect()->route('billing.show', $invoice['invoiceid'])
            ->with('success', 'Your transfer request has been created. It will be initiated automatically as soon as this invoice is paid.');
    }

    /**
     * Domain detail / management page — scoped to the logged-in client's own domain.
     */
    public function show(int $orderId): View
    {
        $order = DomainOrder::where('client_id', session('clientId'))->findOrFail($orderId);

        $live = [];
        if ($order->status === 'provisioned' && $order->interserver_domain_id) {
            $live = $this->interserver->getDomain($order->interserver_domain_id);
        }

        return view('dashboard.domains.show', compact('order', 'live'));
    }

    /**
     * Simple one-shot actions, scoped to the logged-in client's own domain.
     */
    public function action(Request $request, int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $request->validate(['command' => ['required', 'string', 'in:cancel,resendwelcome']]);

        $result = match ($request->command) {
            'cancel'        => $this->interserver->cancelDomain($order->interserver_domain_id),
            'resendwelcome' => $this->interserver->getDomainWelcomeEmail($order->interserver_domain_id),
        };

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'The action could not be completed.'], 422);
        }

        if ($request->command === 'cancel') {
            $order->update(['status' => 'cancelled']);
        }

        return response()->json(['success' => true, 'message' => $result['text'] ?? 'Action completed successfully.']);
    }

    // -------------------------------------------------------------------------
    // Sub-resources
    // -------------------------------------------------------------------------

    public function contactShow(int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        return response()->json($this->interserver->getDomainContact($order->interserver_domain_id));
    }

    public function contactUpdate(Request $request, int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string'],
            'last_name'  => ['required', 'string'],
            'email'      => ['required', 'email'],
            'address1'   => ['required', 'string'],
            'address2'   => ['nullable', 'string'],
            'city'       => ['required', 'string'],
            'state'      => ['required', 'string'],
            'postal_code'=> ['required', 'string'],
            'country'    => ['required', 'string'],
            'phone'      => ['required', 'string'],
            'org_name'   => ['nullable', 'string'],
        ]);

        $result = $this->interserver->updateDomainContact($order->interserver_domain_id, $validated);

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Could not update contact info.'], 422);
        }

        $order->update(['registrant_contact' => $validated]);

        return response()->json(['success' => true, 'message' => 'Contact info updated.']);
    }

    public function dnssecIndex(int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        return response()->json($this->interserver->getDomainDnssec($order->interserver_domain_id));
    }

    public function dnssecStore(Request $request, int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $validated = $request->validate([
            'algorithm'   => ['required', 'array'],
            'digest_type' => ['required', 'array'],
            'digest'      => ['required', 'array'],
            'key_tag'     => ['required', 'array'],
        ]);

        $result = $this->interserver->addDomainDnssec($order->interserver_domain_id, $validated);

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Could not add DNSSEC record.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'DNSSEC record added.']);
    }

    public function dnssecDestroy(int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $result = $this->interserver->deleteDomainDnssec($order->interserver_domain_id);

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Could not clear DNSSEC records.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'DNSSEC records cleared.']);
    }

    public function nameserversIndex(int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        return response()->json($this->interserver->getDomainNameservers($order->interserver_domain_id));
    }

    public function nameserversUpdate(Request $request, int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $validated = $request->validate(['nameserver' => ['required', 'array']]);

        return response()->json($this->interserver->quoteDomainNameservers($order->interserver_domain_id, $validated['nameserver']));
    }

    public function nameserversStore(Request $request, int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $validated = $request->validate([
            'name'      => ['required', 'string'],
            'ipAddress' => ['required', 'ip'],
        ]);

        $result = $this->interserver->addDomainNameserver($order->interserver_domain_id, $validated['name'], $validated['ipAddress']);

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Could not add nameserver.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Nameserver added.']);
    }

    public function nameserversDestroy(Request $request, int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $result = $this->interserver->deleteDomainNameserver($order->interserver_domain_id, $request->all());

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Could not remove nameserver.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Nameserver removed.']);
    }

    public function whoisShow(int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        return response()->json($this->interserver->getDomainWhois($order->interserver_domain_id));
    }

    public function whoisUpdate(Request $request, int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $validated = $request->validate(['func' => ['required', 'string', 'in:enable,disable']]);

        $result = $this->interserver->setDomainWhois($order->interserver_domain_id, $validated);

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Could not update Whois privacy.'], 422);
        }

        $order->update(['whois_privacy' => $validated['func'] === 'enable']);

        return response()->json(['success' => true, 'message' => 'Whois privacy updated.']);
    }

    public function renewShow(int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        return response()->json($this->interserver->getDomainRenewInfo($order->interserver_domain_id));
    }

    /**
     * Create a separate WHMCS invoice for a renewal — tracked in domain_renewals so it
     * doesn't disturb the parent order's "currently active" status. Provisioned once paid
     * by app/Console/Commands/ProvisionPaidDomainRenewal.php.
     */
    public function renewStore(Request $request, int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $validated = $request->validate(['years' => ['required', 'integer', 'min:1', 'max:10']]);

        $renewInfo = $this->interserver->getDomainRenewInfo($order->interserver_domain_id);
        $unitPrice = $this->computePrice($order->tld, (float) ($renewInfo['renewal'] ?? $renewInfo['cost'] ?? 0));
        $priceUsd = round($unitPrice * $validated['years'], 2);

        $currencyCode = session('currency', 'USD');
        $currencyRate = CurrencyConverter::refreshFresh($currencyCode);
        $price        = round($priceUsd * $currencyRate, 2);

        // Renewal currency intentionally tracks the client's *current* session currency,
        // not whatever the original order/domain was invoiced in — each renewal is its
        // own transaction and the client may have switched currency since.
        if (! CurrencyConverter::ensureClientCurrency($order->client_id, $currencyCode)) {
            return response()->json(['success' => false, 'message' => 'Could not switch your billing currency. Please try again or contact support.'], 422);
        }

        $invoice = $this->whmcs->createInvoice(
            $order->client_id,
            "Domain Renewal — {$order->domain_name}.{$order->tld} ({$validated['years']}yr)",
            $price,
        );

        if (($invoice['result'] ?? '') !== 'success') {
            return response()->json(['success' => false, 'message' => 'Could not create your renewal invoice. Please contact support.'], 422);
        }

        DomainRenewal::create([
            'domain_order_id'  => $order->id,
            'whmcs_invoice_id' => $invoice['invoiceid'],
            'years'            => $validated['years'],
            'price'            => $price,
            'status'           => 'pending_payment',
            'config'           => [
                'currency'   => $currencyCode,
                'amount_usd' => $priceUsd,
            ],
        ]);

        return response()->json(['success' => true, 'message' => 'Renewal invoice created.', 'invoice_id' => $invoice['invoiceid']]);
    }

    public function transferShow(int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        return response()->json($this->interserver->getDomainTransferInfo($order->interserver_domain_id));
    }

    public function transferUpdate(int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        return response()->json($this->interserver->transferDomain($order->interserver_domain_id));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function ownedOrder(int $orderId): DomainOrder|JsonResponse
    {
        $order = DomainOrder::where('client_id', session('clientId'))->findOrFail($orderId);

        if ($order->status !== 'provisioned' || ! $order->interserver_domain_id) {
            return response()->json(['success' => false, 'message' => 'This domain is not active yet.'], 422);
        }

        return $order;
    }

    private function validateOrderRequest(Request $request): array
    {
        $validated = $request->validate([
            'domain'              => ['required', 'string'],
            'registration_years'  => ['required', 'integer', 'min:1', 'max:10'],
            'whois_privacy'       => ['nullable', 'boolean'],
            'first_name'          => ['required', 'string'],
            'last_name'           => ['required', 'string'],
            'email'               => ['required', 'email'],
            'address1'            => ['required', 'string'],
            'address2'            => ['nullable', 'string'],
            'city'                => ['required', 'string'],
            'state'               => ['required', 'string'],
            'postal_code'         => ['required', 'string'],
            'country'             => ['required', 'string'],
            'phone'               => ['required', 'string'],
            'org_name'            => ['nullable', 'string'],
        ]);

        $validated['contact'] = [
            'first_name'  => $validated['first_name'],
            'last_name'   => $validated['last_name'],
            'email'       => $validated['email'],
            'address1'    => $validated['address1'],
            'address2'    => $validated['address2'] ?? '',
            'city'        => $validated['city'],
            'state'       => $validated['state'],
            'postal_code' => $validated['postal_code'],
            'country'     => $validated['country'],
            'phone'       => $validated['phone'],
            'org_name'    => $validated['org_name'] ?? '',
        ];

        return $validated;
    }

    private function extractTld(string $domain): string
    {
        $parts = explode('.', $domain, 2);
        return $parts[1] ?? '';
    }

    private function computePrice(string $tld, float $liveCost): float
    {
        $overrides = PricingConfig::domainsTldOverrides();
        if (isset($overrides[$tld])) {
            return (float) $overrides[$tld];
        }

        $markup = PricingConfig::domainsMarkupPercent();

        return round($liveCost * (1 + $markup / 100), 2);
    }
}
