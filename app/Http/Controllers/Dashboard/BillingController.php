<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(): View
    {
        $client = Client::findOrFail(session('clientId'));
        $invoices = Invoice::where('client_id', $client->id)->latest()->get();
        $credit = (float) $client->credit_balance;

        return view('dashboard.billing.index', compact('invoices', 'credit'));
    }

    public function show(int $id): View
    {
        $clientId = session('clientId');
        $invoice = Invoice::where('client_id', $clientId)->with(['items', 'paymentTransactions'])->findOrFail($id);

        // Payment is collected inside this app (Paystack/Flutterwave/crypto) — clients
        // never see a third-party hosted invoice page.
        $paystackPublicKey = config('services.paystack.public_key');
        $flutterwavePublicKey = config('services.flutterwave.public_key');

        return view('dashboard.billing.show', compact('invoice', 'paystackPublicKey', 'flutterwavePublicKey'));
    }
}
