<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\WhmcsService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private WhmcsService $whmcs) {}

    public function index(): View
    {
        $clientId = session('clientId');

        $services = $this->whmcs->getClientServices($clientId);
        $invoices = $this->whmcs->getInvoices($clientId);
        $tickets  = $this->whmcs->getTickets($clientId);
        $details  = $this->whmcs->getClientDetails($clientId);
        $client   = $details['client'] ?? [];

        $activeServices   = collect($services)->where('status', 'Active')->count();
        $unpaidInvoices   = collect($invoices)->where('status', 'Unpaid')->count();
        $openTickets      = collect($tickets)->filter(fn ($t) => in_array($t['status'] ?? '', ['Open', 'Answered', 'Customer-Reply']))->count();

        $recentInvoices = array_slice($invoices, 0, 5);
        $recentTickets  = array_slice($tickets,  0, 5);

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
