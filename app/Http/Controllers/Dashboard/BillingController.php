<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\WhmcsService;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(private WhmcsService $whmcs) {}

    public function index(): View
    {
        $clientId = session('clientId');
        $invoices = $this->whmcs->getInvoices($clientId);
        $details  = $this->whmcs->getClientDetails($clientId);
        $credit   = $details['client']['credit'] ?? '0.00';

        return view('dashboard.billing.index', compact('invoices', 'credit'));
    }

    public function show(int $id): View
    {
        $invoice = $this->whmcs->getInvoice($id);

        if (($invoice['result'] ?? '') !== 'success' || (int) ($invoice['userid'] ?? 0) !== (int) session('clientId')) {
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
