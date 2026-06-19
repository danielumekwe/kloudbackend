<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
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

        $result = $this->tickets->openTicket([
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
}
