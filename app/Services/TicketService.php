<?php

namespace App\Services;

use App\Mail\TicketAnsweredMail;
use App\Mail\TicketClosedMail;
use App\Mail\TicketCreatedAdminMail;
use App\Mail\TicketCreatedMail;
use App\Models\DomainOrder;
use App\Models\QsOrder;
use App\Models\SslOrder;
use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\VpsOrder;
use Illuminate\Support\Facades\Mail;

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

    /**
     * Unfiltered, for the admin ticket list — every client's tickets.
     */
    public function getAllTickets(): array
    {
        return SupportTicket::with(['department', 'client'])
            ->latest()
            ->get()
            ->map(fn (SupportTicket $t) => array_merge($this->toListShape($t), [
                'client_name' => trim(($t->client->firstname ?? '') . ' ' . ($t->client->lastname ?? '')),
                'client_email' => $t->client->email ?? '',
            ]))
            ->all();
    }

    public function getTicket(int $ticketId): array
    {
        $ticket = SupportTicket::with(['department', 'client', 'replies.admin'])->find($ticketId);

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
            'related_service_type' => $data['related_service_type'] ?? null,
            'related_service_id' => $data['related_service_id'] ?? null,
        ]);

        $client = $ticket->client;

        if ($client) {
            Mail::to($client->email)->send(new TicketCreatedMail($ticket->id, $ticket->ticketCode(), $ticket->subject, $client->firstname));
        }

        if ($adminEmail = config('mail.admin_notification_email')) {
            Mail::to($adminEmail)->send(new TicketCreatedAdminMail(
                $ticket->id,
                $ticket->ticketCode(),
                $ticket->subject,
                trim(($client->firstname ?? '') . ' ' . ($client->lastname ?? '')),
                $client->email ?? '',
            ));
        }

        return ['result' => 'success', 'tid' => $ticket->id, 'code' => $ticket->ticketCode()];
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

    /**
     * Staff reply, posted from the admin ticket view — notifies the client that
     * their ticket has been answered.
     */
    public function replyAsAdmin(int $ticketId, int $adminId, string $message): array
    {
        $ticket = SupportTicket::with('client')->find($ticketId);

        if (! $ticket) {
            return ['result' => 'error', 'message' => 'Ticket not found.'];
        }

        TicketReply::create(['ticket_id' => $ticket->id, 'admin_id' => $adminId, 'message' => $message]);
        $ticket->update(['status' => 'Answered', 'last_reply_at' => now()]);

        if ($ticket->client) {
            Mail::to($ticket->client->email)->send(new TicketAnsweredMail(
                $ticket->id,
                $ticket->ticketCode(),
                $ticket->subject,
                $ticket->client->firstname,
                $message,
            ));
        }

        return ['result' => 'success'];
    }

    public function closeTicket(int $ticketId): array
    {
        $ticket = SupportTicket::with('client')->find($ticketId);

        if (! $ticket) {
            return ['result' => 'error', 'message' => 'Ticket not found.'];
        }

        $ticket->update(['status' => 'Closed']);

        if ($ticket->client) {
            Mail::to($ticket->client->email)->send(new TicketClosedMail($ticket->id, $ticket->ticketCode(), $ticket->subject, $ticket->client->firstname));
        }

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
            'code' => $ticket->ticketCode(),
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'date' => $ticket->created_at->toDateTimeString(),
            'lastreply' => ($ticket->last_reply_at ?? $ticket->created_at)->toDateTimeString(),
            'deptname' => $ticket->department->name ?? '',
            'service_label' => $this->resolveServiceLabel($ticket->related_service_type, $ticket->related_service_id),
        ];
    }

    private function toDetailShape(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'tid' => $ticket->id,
            'ticketid' => (string) $ticket->id,
            'code' => $ticket->ticketCode(),
            'subject' => $ticket->subject,
            'message' => $ticket->message,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'date' => $ticket->created_at->toDateTimeString(),
            'deptname' => $ticket->department->name ?? '',
            'department' => $ticket->department->name ?? '',
            'client_name' => trim(($ticket->client->firstname ?? '') . ' ' . ($ticket->client->lastname ?? '')),
            'client_email' => $ticket->client->email ?? '',
            'service_label' => $this->resolveServiceLabel($ticket->related_service_type, $ticket->related_service_id),
            'replies' => ['reply' => $ticket->replies->map(fn (TicketReply $r) => $this->toReplyShape($r))->all()],
        ];
    }

    /**
     * Looked up at display time (rather than denormalized onto the ticket) so it
     * always reflects the service's current state, e.g. if the hostname changes.
     */
    private function resolveServiceLabel(?string $type, ?int $id): ?string
    {
        if (! $type || ! $id) {
            return null;
        }

        return match ($type) {
            'vps' => ($order = VpsOrder::find($id)) ? 'VPS — ' . ($order->config['hostname'] ?? "#{$id}") : null,
            'ssl' => ($order = SslOrder::find($id)) ? 'SSL Certificate — ' . ($order->config['hostname'] ?? "#{$id}") : null,
            'domain' => ($order = DomainOrder::find($id)) ? 'Domain — ' . $order->domain_name . '.' . $order->tld : null,
            'qs' => ($order = QsOrder::find($id)) ? 'Quick Server — ' . ($order->config['os'] ?? "#{$id}") : null,
            default => null,
        };
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
