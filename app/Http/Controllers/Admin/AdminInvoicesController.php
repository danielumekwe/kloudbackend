<?php

namespace App\Http\Controllers\Admin;

use App\Events\InvoicePaid;
use App\Http\Controllers\Controller;
use App\Mail\InvoiceCancelledMail;
use App\Mail\InvoicePaidMail;
use App\Mail\InvoiceRefundedMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminInvoicesController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $invoices = Invoice::with('client')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'unpaid'    => Invoice::where('status', 'unpaid')->count(),
            'paid'      => Invoice::where('status', 'paid')->count(),
            'cancelled' => Invoice::where('status', 'cancelled')->count(),
            'revenue'   => Invoice::where('status', 'paid')->sum('total'),
        ];

        return view('admin.invoices.index', compact('invoices', 'stats', 'status'));
    }

    public function create(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        $clients = $query !== ''
            ? Client::where('email', 'like', "%{$query}%")
                ->orWhere('firstname', 'like', "%{$query}%")
                ->orWhere('lastname', 'like', "%{$query}%")
                ->limit(20)->get()
            : collect();

        return view('admin.invoices.create', compact('clients', 'query'));
    }

    public function store(Request $request, InvoiceService $invoices): RedirectResponse
    {
        $validated = $request->validate([
            'client_id'   => ['required', 'exists:clients,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
        ]);

        $client = Client::findOrFail($validated['client_id']);

        $invoice = $invoices->createAt($client, $validated['description'], (float) $validated['amount'], 'NGN');

        return redirect()->route('admin.invoices.show', $invoice->id)->with('success', 'Invoice created.');
    }

    public function show(int $id): View
    {
        $invoice = Invoice::with(['client', 'items', 'paymentTransactions'])->findOrFail($id);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function markPaid(int $id): RedirectResponse
    {
        $invoice = Invoice::with('client')->findOrFail($id);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice is already paid.');
        }

        $invoice->update([
            'status'         => 'paid',
            'paid_at'        => now(),
            'payment_method' => 'manual',
        ]);

        if ($invoice->client) {
            Mail::to($invoice->client->email)->send(new InvoicePaidMail(
                firstName: $invoice->client->firstname,
                invoiceId: $invoice->id,
                amount: (float) $invoice->total,
                currency: $invoice->currency_code,
                paidAt: $invoice->paid_at->format('M j, Y \a\t g:i A'),
            ));
        }

        InvoicePaid::dispatch($invoice);

        return back()->with('success', 'Invoice #' . $id . ' marked as paid.');
    }

    public function markPending(int $id): RedirectResponse
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status !== 'paid') {
            return back()->with('error', 'Only paid invoices can be reverted to pending.');
        }

        $invoice->update(['status' => 'unpaid', 'paid_at' => null, 'payment_method' => null]);

        return back()->with('success', 'Invoice #' . $id . ' reverted to pending.');
    }

    public function cancel(int $id): RedirectResponse
    {
        $invoice = Invoice::with('client')->findOrFail($id);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot cancel a paid invoice.');
        }

        $invoice->update(['status' => 'cancelled']);

        if ($invoice->client) {
            Mail::to($invoice->client->email)->send(new InvoiceCancelledMail(
                firstName: $invoice->client->firstname,
                invoiceId: $invoice->id,
                amount: (float) $invoice->total,
                currency: $invoice->currency_code,
            ));
        }

        return back()->with('success', 'Invoice #' . $id . ' cancelled.');
    }

    /**
     * Marks a paid invoice as refunded — bookkeeping only, no gateway-side
     * reversal is triggered here (each gateway's dashboard remains the
     * authoritative place to actually reverse the charge).
     */
    public function refund(int $id): RedirectResponse
    {
        $invoice = Invoice::with('client')->findOrFail($id);

        if ($invoice->status !== 'paid') {
            return back()->with('error', 'Only paid invoices can be refunded.');
        }

        $invoice->update(['status' => 'refunded']);

        if ($invoice->client) {
            Mail::to($invoice->client->email)->send(new InvoiceRefundedMail(
                firstName: $invoice->client->firstname,
                invoiceId: $invoice->id,
                amount: (float) $invoice->total,
                currency: $invoice->currency_code,
            ));
        }

        return back()->with('success', 'Invoice #' . $id . ' marked as refunded.');
    }
}
