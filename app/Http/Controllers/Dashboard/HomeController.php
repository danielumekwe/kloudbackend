<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\TicketService;
use App\Services\WhmcsService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private WhmcsService $whmcs, private TicketService $tickets) {}

    public function index(): View
    {
        $client = Client::findOrFail(session('clientId'));
        $clientId = $client->id;

        // The legacy "Servers" product line (shared hosting) is still WHMCS-backed —
        // out of scope for the Phase 3 invoicing cutover, unlike $invoices below.
        $services = $client->whmcs_client_id ? $this->whmcs->getClientServices($client->whmcs_client_id) : [];
        $invoices = Invoice::where('client_id', $clientId)->latest()->get();
        // Tickets are local since Phase 1 — keyed on the local client id directly.
        $tickets = $this->tickets->getTickets($clientId);

        $activeServices = collect($services)->where('status', 'Active')->count();
        $unpaidInvoices = $invoices->where('status', 'unpaid')->count();
        $openTickets    = collect($tickets)->filter(fn ($t) => in_array($t['status'] ?? '', ['Open', 'Answered', 'Customer-Reply']))->count();

        $recentInvoices = $invoices->take(5);
        $recentTickets  = array_slice($tickets, 0, 5);

        return view('dashboard.home', compact(
            'services',
            'activeServices',
            'unpaidInvoices',
            'openTickets',
            'recentInvoices',
            'recentTickets',
            'client',
            'clientId',
        ));
    }
}
