<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Client;
use App\Models\QsOrder;
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

class QsController extends Controller
{
    public function __construct(
        private InterServerService $interserver,
        private InvoiceService $invoices,
    ) {}

    /**
     * "My Quick Servers" — instances belonging to the logged-in client only.
     */
    public function index(): View
    {
        $clientId = session('clientId');

        $orders = QsOrder::where('client_id', $clientId)
            ->whereIn('status', ['provisioned', 'paid', 'pending_payment', 'failed'])
            ->latest()
            ->get();

        $instances = $orders->map(function (QsOrder $order) {
            $live = [];
            if ($order->status === 'provisioned' && $order->interserver_qs_id) {
                $live = $this->interserver->getQs($order->interserver_qs_id);
            }
            return [
                'order' => $order,
                'live'  => $live,
            ];
        });

        return view('dashboard.qs.index', compact('instances'));
    }

    /**
     * Quick Server order builder — pick from InterServer's live server inventory.
     */
    public function catalog(): View|RedirectResponse
    {
        $catalogData = $this->interserver->getQsOrderCatalog();

        if ($catalogData['error'] ?? false) {
            return back()->with('error', 'Unable to reach InterServer right now. Please try again shortly.');
        }

        $servers   = $catalogData['server_details'] ?? [];
        $templates = $catalogData['templates'] ?? [];

        $currencyCode = session('currency', 'USD');
        $currency     = CurrencyConverter::symbol($currencyCode);

        $prices = [];
        foreach ($servers as $serverId => $details) {
            $priceUsd = $this->computePrice($serverId, $details['cost'] ?? '0');
            $prices[$serverId] = ProductCatalog::price('qs', (string) $serverId, 1, $currencyCode, $priceUsd);
        }

        return view('dashboard.qs.catalog', compact('servers', 'templates', 'prices', 'currency'));
    }

    /**
     * AJAX: validate config against InterServer (stock/compatibility) and return OUR computed price.
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server' => ['required', 'integer'],
            'os'     => ['required', 'string'],
        ]);

        $catalogData = $this->interserver->getQsOrderCatalog();
        $servers = $catalogData['server_details'] ?? [];

        if (! isset($servers[$validated['server']])) {
            return response()->json(['error' => 'This server is no longer available.'], 422);
        }

        $check = $this->interserver->quoteQsOrder([
            'server'   => $validated['server'],
            'os'       => $validated['os'],
            'password' => 'Tmp1234!Tmp1234!', // placeholder for the stock-check; the real password is set at order time
            'tos'      => true,
        ]);

        if (! ($check['continue'] ?? true)) {
            return response()->json(['error' => implode(' ', $check['errors'] ?? ['This configuration is not available.'])], 422);
        }

        $priceUsd = $this->computePrice($validated['server'], $servers[$validated['server']]['cost'] ?? '0');
        $price    = ProductCatalog::price('qs', (string) $validated['server'], 1, session('currency', 'USD'), $priceUsd);

        return response()->json(['price' => $price]);
    }

    /**
     * Create a WHMCS invoice for the chosen config. Real InterServer provisioning happens
     * only after the invoice is marked paid (see app/Console/Commands/ProvisionPaidQs.php).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'server'   => ['required', 'integer'],
            'os'       => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
            'comment'  => ['nullable', 'string'],
        ]);

        $clientId = session('clientId');

        $catalogData = $this->interserver->getQsOrderCatalog();
        $servers = $catalogData['server_details'] ?? [];

        if (! isset($servers[$validated['server']])) {
            return back()->withErrors('This server is no longer available.')->withInput();
        }

        $check = $this->interserver->quoteQsOrder([
            'server'   => $validated['server'],
            'os'       => $validated['os'],
            'password' => $validated['password'],
            'comment'  => $validated['comment'] ?? '',
            'tos'      => true,
        ]);

        if (! ($check['continue'] ?? true)) {
            return back()->withErrors(implode(' ', $check['errors'] ?? ['This configuration is not available.']))->withInput();
        }

        $priceUsd = $this->computePrice($validated['server'], $servers[$validated['server']]['cost'] ?? '0');
        $label    = $servers[$validated['server']]['cpu'] ?? "Quick Server #{$validated['server']}";

        $currencyCode = session('currency', 'USD');
        $price = ProductCatalog::price('qs', (string) $validated['server'], 1, $currencyCode, $priceUsd);
        $client = Client::find($clientId);

        $orderDescription = "Quick Server — {$label}";

        $invoice = $this->invoices->createAt($client, $orderDescription, $price, $currencyCode);

        QsOrder::create([
            'client_id'     => $clientId,
            'invoice_id'    => $invoice->id,
            'status'        => 'pending_payment',
            'price'         => $invoice->total,
            'billing_cycle' => 1,
            'config'        => [
                'server'     => $validated['server'],
                'os'         => $validated['os'],
                'comment'    => $validated['comment'] ?? '',
                'password'   => Crypt::encryptString($validated['password']),
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
            ->with('success', 'Your order has been created. Your Quick Server will be provisioned automatically as soon as this invoice is paid.');
    }

    /**
     * Quick Server detail / management page — scoped to the logged-in client's own instance.
     */
    public function show(int $orderId): View
    {
        $order = QsOrder::where('client_id', session('clientId'))->findOrFail($orderId);

        $live = [];
        if ($order->status === 'provisioned' && $order->interserver_qs_id) {
            $live = $this->interserver->getQs($order->interserver_qs_id);
        }

        return view('dashboard.qs.show', compact('order', 'live'));
    }

    /**
     * Lifecycle / maintenance actions, scoped to the logged-in client's own instance.
     */
    public function action(Request $request, int $orderId): JsonResponse
    {
        $order = QsOrder::where('client_id', session('clientId'))->findOrFail($orderId);

        if ($order->status !== 'provisioned' || ! $order->interserver_qs_id) {
            return response()->json(['success' => false, 'message' => 'This Quick Server is not active yet.'], 422);
        }

        $qsId = $order->interserver_qs_id;

        $request->validate([
            'command'       => ['required', 'string', 'in:' . implode(',', [
                'start', 'stop', 'restart', 'changepassword', 'reinstall',
                'backup', 'deletebackup', 'downloadbackup', 'restore',
                'blocksmtp', 'changehostname', 'changetimezone', 'changewebuzopassword',
                'disablecd', 'ejectcd', 'insertcd', 'disablequota', 'enablequota',
                'reversedns', 'setupvnc', 'viewdesktop', 'trafficusage',
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
        ]);

        $result = match ($request->command) {
            'start'   => $this->interserver->startQs($qsId),
            'stop'    => $this->interserver->stopQs($qsId),
            'restart' => $this->interserver->restartQs($qsId),
            'changepassword' => $request->filled('password')
                ? $this->interserver->changeQsRootPassword($qsId, $request->password)
                : ['error' => true, 'message' => 'A new password is required.'],
            'reinstall' => ($request->filled('template') && $request->filled('localPassword'))
                ? $this->interserver->reinstallQsOs($qsId, $request->template, $request->localPassword, $request->password)
                : ['error' => true, 'message' => 'A template and your InterServer account password are required to reinstall.'],
            'backup'          => $this->interserver->getQsBackup($qsId),
            'deletebackup'    => $request->filled('file')
                ? $this->interserver->deleteQsBackup($qsId, $request->file)
                : ['error' => true, 'message' => 'A backup filename is required.'],
            'downloadbackup'  => $request->filled('file')
                ? $this->interserver->downloadQsBackup($qsId, $request->file)
                : ['error' => true, 'message' => 'A backup filename is required.'],
            'restore'         => ($request->filled('backup') && $request->filled('password'))
                ? $this->interserver->restoreQs($qsId, $request->backup, $request->password)
                : ['error' => true, 'message' => 'A backup filename and your InterServer account password are required to restore.'],
            'blocksmtp'       => $this->interserver->blockSmtpQs($qsId),
            'changehostname'  => $request->filled('hostname')
                ? $this->interserver->changeQsHostname($qsId, $request->hostname)
                : ['error' => true, 'message' => 'A hostname is required.'],
            'changetimezone'  => $request->filled('timezone')
                ? $this->interserver->changeQsTimezone($qsId, $request->timezone)
                : ['error' => true, 'message' => 'A timezone is required.'],
            'changewebuzopassword' => $request->filled('password')
                ? $this->interserver->changeQsWebuzoPassword($qsId, $request->password)
                : ['error' => true, 'message' => 'A new password is required.'],
            'disablecd'       => $this->interserver->disableQsCd($qsId),
            'ejectcd'         => $this->interserver->ejectQsCd($qsId),
            'insertcd'        => $request->filled('url')
                ? $this->interserver->insertQsCd($qsId, $request->url)
                : ['error' => true, 'message' => 'An ISO URL is required.'],
            'disablequota'    => $this->interserver->disableQsQuota($qsId),
            'enablequota'     => $this->interserver->enableQsQuota($qsId),
            'reversedns'      => $request->filled('ips')
                ? $this->interserver->setQsReverseDns($qsId, $request->ips)
                : ['error' => true, 'message' => 'PTR records are required.'],
            'setupvnc'        => $request->filled('vnc')
                ? $this->interserver->setupQsVnc($qsId, $request->vnc)
                : ['error' => true, 'message' => 'A source IP is required.'],
            'viewdesktop'     => $this->interserver->refreshQsViewDesktop($qsId),
            'trafficusage'    => $this->interserver->getQsTrafficUsage($qsId),
        };

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'The action could not be completed.'], 422);
        }

        return response()->json(['success' => true, 'message' => $result['text'] ?? 'Action completed successfully.', 'data' => $result]);
    }

    private function computePrice(int|string $serverId, string $rawCost): float
    {
        $cost = (float) preg_replace('/[^0-9.]/', '', $rawCost);

        $overrides = PricingConfig::qsServerOverrides();
        if (isset($overrides[$serverId])) {
            return (float) $overrides[$serverId];
        }

        return round($cost * (1 + PricingConfig::qsMarkupPercent() / 100), 2);
    }
}
