<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DomainOrder;
use App\Models\QsOrder;
use App\Models\SslOrder;
use App\Models\VpsOrder;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    public function index(): View
    {
        $tickets = $this->tickets->getTickets(session('clientId'));
        return view('dashboard.support.index', compact('tickets'));
    }

    public function create(): View
    {
        $departments = $this->tickets->getSupportDepartments();
        $services = $this->clientServices(session('clientId'));
        return view('dashboard.support.create', compact('departments', 'services'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'deptid'       => ['required', 'integer'],
            'subject'      => ['required', 'string', 'max:200'],
            'message'      => ['required', 'string', 'min:10'],
            'priority'     => ['required', 'in:Low,Medium,High'],
            'service'      => ['nullable', 'string'],
        ]);

        [$serviceType, $serviceId] = $this->parseServiceSelection($request->service, session('clientId'));

        $result = $this->tickets->openTicket([
            'clientid' => session('clientId'),
            'deptid'   => $request->deptid,
            'subject'  => $request->subject,
            'message'  => $request->message,
            'priority' => $request->priority,
            'related_service_type' => $serviceType,
            'related_service_id'   => $serviceId,
        ]);

        if (($result['result'] ?? '') !== 'success') {
            return back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Failed to open ticket. Please try again.');
        }

        return redirect()->route('support.index')
            ->with('success', 'Ticket ' . ($result['code'] ?? '') . ' opened successfully.');
    }

    public function show(int $id): View
    {
        $this->ensureOwnsTicket($id);

        $ticket = $this->tickets->getTicket($id);

        if (($ticket['result'] ?? '') !== 'success') {
            abort(404, 'Ticket not found.');
        }

        return view('dashboard.support.show', compact('ticket'));
    }

    public function reply(Request $request, int $id): RedirectResponse
    {
        $this->ensureOwnsTicket($id);

        $request->validate([
            'message' => ['required', 'string', 'min:5'],
        ]);

        $result = $this->tickets->replyTicket($id, session('clientId'), $request->message);

        if (($result['result'] ?? '') !== 'success') {
            return back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Failed to send reply. Please try again.');
        }

        return redirect()->route('support.show', $id)
            ->with('success', 'Reply posted successfully.');
    }

    public function close(int $id): RedirectResponse
    {
        $this->ensureOwnsTicket($id);

        $result = $this->tickets->closeTicket($id);

        if (($result['result'] ?? '') !== 'success') {
            return back()->with('error', $result['message'] ?? 'Failed to close ticket.');
        }

        return redirect()->route('support.index')
            ->with('success', 'Ticket closed successfully.');
    }

    private function ensureOwnsTicket(int $id): void
    {
        if (! $this->tickets->ownsTicket($id, session('clientId'))) {
            abort(404, 'Ticket not found.');
        }
    }

    /**
     * Flat list of the client's services across all order types, for the "what is
     * this ticket about" dropdown on the new-ticket form.
     */
    private function clientServices(int $clientId): array
    {
        $services = [];

        foreach (VpsOrder::where('client_id', $clientId)->get() as $order) {
            $services[] = ['type' => 'vps', 'id' => $order->id, 'label' => 'VPS — ' . ($order->config['hostname'] ?? "#{$order->id}")];
        }

        foreach (SslOrder::where('client_id', $clientId)->get() as $order) {
            $services[] = ['type' => 'ssl', 'id' => $order->id, 'label' => 'SSL Certificate — ' . ($order->config['hostname'] ?? "#{$order->id}")];
        }

        foreach (DomainOrder::where('client_id', $clientId)->get() as $order) {
            $services[] = ['type' => 'domain', 'id' => $order->id, 'label' => 'Domain — ' . $order->domain_name . '.' . $order->tld];
        }

        foreach (QsOrder::where('client_id', $clientId)->get() as $order) {
            $services[] = ['type' => 'qs', 'id' => $order->id, 'label' => 'Quick Server — ' . ($order->config['os'] ?? "#{$order->id}")];
        }

        return $services;
    }

    /**
     * "service" comes in from the form as "{type}:{id}" (e.g. "vps:3"), or empty if
     * the client didn't pick one. Re-verifies ownership server-side rather than
     * trusting the submitted id — it's just a context label on the ticket, not an
     * access grant, but a tampered id would otherwise mislabel the ticket with
     * someone else's service.
     */
    private function parseServiceSelection(?string $service, int $clientId): array
    {
        if (! $service || ! str_contains($service, ':')) {
            return [null, null];
        }

        [$type, $id] = explode(':', $service, 2);

        if (! ctype_digit($id)) {
            return [null, null];
        }

        $id = (int) $id;

        $owned = match ($type) {
            'vps'    => VpsOrder::where('id', $id)->where('client_id', $clientId)->exists(),
            'ssl'    => SslOrder::where('id', $id)->where('client_id', $clientId)->exists(),
            'domain' => DomainOrder::where('id', $id)->where('client_id', $clientId)->exists(),
            'qs'     => QsOrder::where('id', $id)->where('client_id', $clientId)->exists(),
            default  => false,
        };

        return $owned ? [$type, $id] : [null, null];
    }
}
