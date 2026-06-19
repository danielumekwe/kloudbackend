<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\WhmcsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function __construct(private WhmcsService $whmcs) {}

    public function index(): View
    {
        $tickets = $this->whmcs->getTickets(session('clientId'));
        return view('dashboard.support.index', compact('tickets'));
    }

    public function create(): View
    {
        $departments = $this->whmcs->getSupportDepartments();
        return view('dashboard.support.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'deptid'   => ['required', 'integer'],
            'subject'  => ['required', 'string', 'max:200'],
            'message'  => ['required', 'string', 'min:10'],
            'priority' => ['required', 'in:Low,Medium,High'],
        ]);

        $result = $this->whmcs->openTicket([
            'clientid' => session('clientId'),
            'deptid'   => $request->deptid,
            'subject'  => $request->subject,
            'message'  => $request->message,
            'priority' => $request->priority,
        ]);

        if (($result['result'] ?? '') !== 'success') {
            return back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Failed to open ticket. Please try again.');
        }

        return redirect()->route('support.index')
            ->with('success', 'Ticket #' . ($result['tid'] ?? '') . ' opened successfully.');
    }

    public function show(int $id): View
    {
        $this->ensureOwnsTicket($id);

        $ticket = $this->whmcs->getTicket($id);

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

        $result = $this->whmcs->replyTicket($id, session('clientId'), $request->message);

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

        $result = $this->whmcs->closeTicket($id);

        if (($result['result'] ?? '') !== 'success') {
            return back()->with('error', $result['message'] ?? 'Failed to close ticket.');
        }

        return redirect()->route('support.index')
            ->with('success', 'Ticket closed successfully.');
    }

    /**
     * GetTicket (single) takes no clientid filter, so without this check any logged-in
     * client could read/reply-to/close any other client's ticket by guessing its id.
     * GetTickets(clientid) is the one WHMCS endpoint that's actually scoped, so it
     * doubles as the ownership check here.
     */
    private function ensureOwnsTicket(int $id): void
    {
        $tickets = $this->whmcs->getTickets(session('clientId'));

        $owns = collect($tickets)->contains(fn (array $ticket) => (int) ($ticket['id'] ?? 0) === $id);

        if (! $owns) {
            abort(404, 'Ticket not found.');
        }
    }
}
