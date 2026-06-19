<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
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

        // services/invoices are still WHMCS-backed (Phase 3 of the WHMCS exit moves
        // invoicing local) — resolve through whmcs_client_id, never the local id
        // directly, since they only coincide for clients migrated before the exit.
        $services = $client->whmcs_client_id ? $this->whmcs->getClientServices($client->whmcs_client_id) : [];
        $invoices = $client->whmcs_client_id ? $this->whmcs->getInvoices($client->whmcs_client_id) : [];
        // Tickets are local since Phase 1 — keyed on the local client id directly.
        $tickets = $this->tickets->getTickets($clientId);

        $activeServices = collect($services)->where('status', 'Active')->count();
        $unpaidInvoices = collect($invoices)->where('status', 'Unpaid')->count();
        $openTickets    = collect($tickets)->filter(fn ($t) => in_array($t['status'] ?? '', ['Open', 'Answered', 'Customer-Reply']))->count();

        $recentInvoices = array_slice($invoices, 0, 5);
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
