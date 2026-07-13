<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(int $id): View
    {
        $invoice = Invoice::with(['client', 'items', 'paymentTransactions'])->findOrFail($id);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function markPaid(int $id): RedirectResponse
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice is already paid.');
        }

        $invoice->update([
            'status'         => 'paid',
            'paid_at'        => now(),
            'payment_method' => 'manual',
        ]);

        return back()->with('success', 'Invoice #' . $id . ' marked as paid.');
    }

    public function cancel(int $id): RedirectResponse
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot cancel a paid invoice.');
        }

        $invoice->update(['status' => 'cancelled']);

        return back()->with('success', 'Invoice #' . $id . ' cancelled.');
    }
}
