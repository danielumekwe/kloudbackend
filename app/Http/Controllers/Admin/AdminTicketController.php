<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTicketController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    public function index(): View
    {
        $tickets = $this->tickets->getAllTickets();

        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(int $id): View
    {
        $ticket = $this->tickets->getTicket($id);

        if (($ticket['result'] ?? '') !== 'success') {
            abort(404, 'Ticket not found.');
        }

        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'min:5'],
        ]);

        $result = $this->tickets->replyAsAdmin($id, (int) session('adminId'), $request->message);

        if (($result['result'] ?? '') !== 'success') {
            return back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Failed to send reply. Please try again.');
        }

        return redirect()->route('admin.tickets.show', $id)
            ->with('success', 'Reply sent to client.');
    }

    public function close(int $id): RedirectResponse
    {
        $result = $this->tickets->closeTicket($id);

        if (($result['result'] ?? '') !== 'success') {
            return back()->with('error', $result['message'] ?? 'Failed to close ticket.');
        }

        return redirect()->route('admin.tickets.index')
            ->with('success', 'Ticket closed.');
    }
}
