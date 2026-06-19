<?php

namespace App\Services;

use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use App\Models\TicketReply;

/**
 * Local replacement for WhmcsService's ticket methods (Phase 1 of the WHMCS
 * exit — see /Users/Apple/.claude/plans/hidden-baking-gem.md). Every return
 * value is shaped exactly like WHMCS's response envelope (`result`, `tid`,
 * `ticketid` as a string, etc.) so the existing Blade views need zero changes.
 */
class TicketService
{
    public function getOpenTicketCount(): int
    {
        return SupportTicket::where('status', 'Open')->count();
    }

    public function getSupportDepartments(): array
    {
        return SupportDepartment::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SupportDepartment $d) => ['id' => $d->id, 'name' => $d->name])
            ->all();
    }

    public function getTickets(int $clientId): array
    {
        return SupportTicket::where('client_id', $clientId)
            ->with('department')
            ->latest()
            ->get()
            ->map(fn (SupportTicket $t) => $this->toListShape($t))
            ->all();
    }

    public function getTicket(int $ticketId): array
    {
        $ticket = SupportTicket::with(['department', 'replies.admin'])->find($ticketId);

        if (! $ticket) {
            return ['result' => 'error', 'message' => 'Ticket not found.'];
        }

        return array_merge(['result' => 'success'], $this->toDetailShape($ticket));
    }

    public function openTicket(array $data): array
    {
        $department = SupportDepartment::find($data['deptid'] ?? null);

        if (! $department) {
            return ['result' => 'error', 'message' => 'Invalid department selected.'];
        }

        $ticket = SupportTicket::create([
            'client_id' => (int) $data['clientid'],
            'department_id' => $department->id,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'priority' => $data['priority'] ?? 'Medium',
        ]);

        return ['result' => 'success', 'tid' => $ticket->id];
    }

    public function replyTicket(int $ticketId, int $clientId, string $message): array
    {
        $ticket = SupportTicket::find($ticketId);

        if (! $ticket) {
            return ['result' => 'error', 'message' => 'Ticket not found.'];
        }

        if ($ticket->isClosed()) {
            return ['result' => 'error', 'message' => 'This ticket is closed and can no longer receive replies.'];
        }

        TicketReply::create(['ticket_id' => $ticket->id, 'client_id' => $clientId, 'message' => $message]);
        $ticket->update(['status' => 'Customer-Reply', 'last_reply_at' => now()]);

        return ['result' => 'success'];
    }

    public function closeTicket(int $ticketId): array
    {
        $ticket = SupportTicket::find($ticketId);

        if (! $ticket) {
            return ['result' => 'error', 'message' => 'Ticket not found.'];
        }

        $ticket->update(['status' => 'Closed']);

        return ['result' => 'success'];
    }

    public function ownsTicket(int $ticketId, int $clientId): bool
    {
        return SupportTicket::where('id', $ticketId)->where('client_id', $clientId)->exists();
    }

    private function toListShape(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'tid' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'date' => $ticket->created_at->toDateTimeString(),
            'lastreply' => ($ticket->last_reply_at ?? $ticket->created_at)->toDateTimeString(),
            'deptname' => $ticket->department->name ?? '',
        ];
    }

    private function toDetailShape(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'tid' => $ticket->id,
            'ticketid' => (string) $ticket->id,
            'subject' => $ticket->subject,
            'message' => $ticket->message,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'date' => $ticket->created_at->toDateTimeString(),
            'deptname' => $ticket->department->name ?? '',
            'department' => $ticket->department->name ?? '',
            'replies' => ['reply' => $ticket->replies->map(fn (TicketReply $r) => $this->toReplyShape($r))->all()],
        ];
    }

    private function toReplyShape(TicketReply $reply): array
    {
        return [
            'type' => $reply->isStaffReply() ? 'reply' : 'client',
            'admin' => $reply->isStaffReply() ? ($reply->admin->email ?? 'Support Team') : '',
            'name' => $reply->isStaffReply() ? ($reply->admin->email ?? 'Support Team') : '',
            'message' => $reply->message,
            'date' => $reply->created_at->toDateTimeString(),
        ];
    }
}
