<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_tickets_is_scoped_to_the_client(): void
    {
        $department = SupportDepartment::create(['name' => 'Billing']);
        SupportTicket::create(['client_id' => 7, 'department_id' => $department->id, 'subject' => 'mine', 'message' => 'x']);
        SupportTicket::create(['client_id' => 8, 'department_id' => $department->id, 'subject' => 'not mine', 'message' => 'x']);

        $tickets = app(TicketService::class)->getTickets(7);

        $this->assertCount(1, $tickets);
        $this->assertSame('mine', $tickets[0]['subject']);
    }

    public function test_get_ticket_returns_an_error_envelope_when_missing(): void
    {
        $result = app(TicketService::class)->getTicket(999);

        $this->assertSame('error', $result['result']);
    }

    public function test_open_ticket_persists_and_returns_success_envelope(): void
    {
        $department = SupportDepartment::create(['name' => 'Billing']);

        $result = app(TicketService::class)->openTicket([
            'clientid' => 7,
            'deptid' => $department->id,
            'subject' => 'Help',
            'message' => 'Something is broken',
            'priority' => 'High',
        ]);

        $this->assertSame('success', $result['result']);
        $this->assertDatabaseHas('support_tickets', [
            'id' => $result['tid'], 'client_id' => 7, 'subject' => 'Help', 'priority' => 'High', 'status' => 'Open',
        ]);
    }

    public function test_open_ticket_rejects_an_invalid_department(): void
    {
        $result = app(TicketService::class)->openTicket([
            'clientid' => 7, 'deptid' => 999, 'subject' => 'Help', 'message' => 'x', 'priority' => 'Low',
        ]);

        $this->assertSame('error', $result['result']);
        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_reply_ticket_bumps_status_and_last_reply_at(): void
    {
        $department = SupportDepartment::create(['name' => 'Billing']);
        $ticket = SupportTicket::create(['client_id' => 7, 'department_id' => $department->id, 'subject' => 'x', 'message' => 'x']);

        $result = app(TicketService::class)->replyTicket($ticket->id, 7, 'More details here');

        $this->assertSame('success', $result['result']);
        $ticket->refresh();
        $this->assertSame('Customer-Reply', $ticket->status);
        $this->assertNotNull($ticket->last_reply_at);
        $this->assertDatabaseHas('ticket_replies', ['ticket_id' => $ticket->id, 'client_id' => 7, 'admin_id' => null]);
    }

    public function test_reply_ticket_rejects_a_closed_ticket(): void
    {
        $department = SupportDepartment::create(['name' => 'Billing']);
        $ticket = SupportTicket::create(['client_id' => 7, 'department_id' => $department->id, 'subject' => 'x', 'message' => 'x', 'status' => 'Closed']);

        $result = app(TicketService::class)->replyTicket($ticket->id, 7, 'Reopen please');

        $this->assertSame('error', $result['result']);
        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_close_ticket_sets_status(): void
    {
        $department = SupportDepartment::create(['name' => 'Billing']);
        $ticket = SupportTicket::create(['client_id' => 7, 'department_id' => $department->id, 'subject' => 'x', 'message' => 'x']);

        app(TicketService::class)->closeTicket($ticket->id);

        $this->assertSame('Closed', $ticket->refresh()->status);
    }

    public function test_get_open_ticket_count_counts_only_open_status(): void
    {
        $department = SupportDepartment::create(['name' => 'Billing']);
        SupportTicket::create(['client_id' => 1, 'department_id' => $department->id, 'subject' => 'a', 'message' => 'a', 'status' => 'Open']);
        SupportTicket::create(['client_id' => 1, 'department_id' => $department->id, 'subject' => 'b', 'message' => 'b', 'status' => 'Open']);
        SupportTicket::create(['client_id' => 1, 'department_id' => $department->id, 'subject' => 'c', 'message' => 'c', 'status' => 'Closed']);
        SupportTicket::create(['client_id' => 1, 'department_id' => $department->id, 'subject' => 'd', 'message' => 'd', 'status' => 'Customer-Reply']);

        $this->assertSame(2, app(TicketService::class)->getOpenTicketCount());
    }

    public function test_get_support_departments_respects_active_flag_and_order(): void
    {
        SupportDepartment::create(['name' => 'Z Last', 'sort_order' => 2]);
        SupportDepartment::create(['name' => 'Hidden', 'is_active' => false, 'sort_order' => 0]);
        SupportDepartment::create(['name' => 'A First', 'sort_order' => 1]);

        $departments = app(TicketService::class)->getSupportDepartments();

        $this->assertSame(['A First', 'Z Last'], array_column($departments, 'name'));
    }

    public function test_get_ticket_includes_staff_and_client_replies_in_order(): void
    {
        $department = SupportDepartment::create(['name' => 'Billing']);
        $admin = Admin::create(['email' => 'staff@admin.com', 'password' => 'x', 'role' => 'support_agent']);
        $ticket = SupportTicket::create(['client_id' => 7, 'department_id' => $department->id, 'subject' => 'x', 'message' => 'x']);

        TicketReply::create(['ticket_id' => $ticket->id, 'client_id' => 7, 'message' => 'first']);
        TicketReply::create(['ticket_id' => $ticket->id, 'admin_id' => $admin->id, 'message' => 'second']);

        $result = app(TicketService::class)->getTicket($ticket->id);

        $replies = $result['replies']['reply'];
        $this->assertCount(2, $replies);
        $this->assertSame('client', $replies[0]['type']);
        $this->assertSame('reply', $replies[1]['type']);
        $this->assertSame('staff@admin.com', $replies[1]['admin']);
    }

    public function test_owns_ticket(): void
    {
        $department = SupportDepartment::create(['name' => 'Billing']);
        $ticket = SupportTicket::create(['client_id' => 7, 'department_id' => $department->id, 'subject' => 'x', 'message' => 'x']);

        $service = app(TicketService::class);

        $this->assertTrue($service->ownsTicket($ticket->id, 7));
        $this->assertFalse($service->ownsTicket($ticket->id, 8));
    }
}
