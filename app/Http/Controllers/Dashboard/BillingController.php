<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\WhmcsService;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(private WhmcsService $whmcs) {}

    public function index(): View
    {
        $client = Client::findOrFail(session('clientId'));
        $invoices = $client->whmcs_client_id ? $this->whmcs->getInvoices($client->whmcs_client_id) : [];
        $credit = (float) $client->credit_balance;

        return view('dashboard.billing.index', compact('invoices', 'credit'));
    }

    public function show(int $id): View
    {
        $client = Client::findOrFail(session('clientId'));
        $invoice = $this->whmcs->getInvoice($id);

        // invoice['userid'] is WHMCS's own client id, not necessarily our local one
        // (they only coincide for clients migrated before the WHMCS exit) — compare
        // against the client's tracked whmcs_client_id, never against the local id.
        if (($invoice['result'] ?? '') !== 'success' || (int) ($invoice['userid'] ?? 0) !== (int) $client->whmcs_client_id) {
            abort(404, 'Invoice not found.');
        }

        // The invoice's own currency at creation time — not the client's current
        // session currency, since switching currency later doesn't change past invoices.
        $invoiceCurrency = $invoice['currencycode'] ?? 'USD';
        // Payment is collected inside this app (Paystack/Flutterwave/crypto) — clients
        // never see WHMCS's own hosted invoice page.
        $paystackPublicKey = config('services.paystack.public_key');
        $flutterwavePublicKey = config('services.flutterwave.public_key');

        return view('dashboard.billing.show', compact('invoice', 'invoiceCurrency', 'paystackPublicKey', 'flutterwavePublicKey'));
    }
}
