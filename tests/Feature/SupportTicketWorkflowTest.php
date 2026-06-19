<?php

namespace Tests\Feature;

use App\Models\SupportDepartment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_client_can_open_a_ticket_reply_to_it_and_close_it(): void
    {
        $department = SupportDepartment::create(['name' => 'Billing']);

        $store = $this->withSession(['clientId' => 7])->post('/support', [
            'deptid' => $department->id,
            'subject' => 'Cannot access my VPS',
            'message' => 'I am unable to SSH into my server since this morning.',
            'priority' => 'High',
        ]);
        $store->assertRedirect(route('support.index'));
        $this->assertDatabaseHas('support_tickets', ['client_id' => 7, 'subject' => 'Cannot access my VPS', 'status' => 'Open']);

        $ticketId = \App\Models\SupportTicket::first()->id;

        $index = $this->withSession(['clientId' => 7])->get('/support');
        $index->assertOk();
        $index->assertSee('Cannot access my VPS');

        $show = $this->withSession(['clientId' => 7])->get("/support/{$ticketId}");
        $show->assertOk();
        $show->assertSee('Cannot access my VPS');

        $reply = $this->withSession(['clientId' => 7])->post("/support/{$ticketId}/reply", [
            'message' => 'Still broken, any update?',
        ]);
        $reply->assertRedirect(route('support.show', $ticketId));
        $this->assertDatabaseHas('ticket_replies', ['ticket_id' => $ticketId, 'message' => 'Still broken, any update?']);
        $this->assertDatabaseHas('support_tickets', ['id' => $ticketId, 'status' => 'Customer-Reply']);

        $close = $this->withSession(['clientId' => 7])->post("/support/{$ticketId}/close");
        $close->assertRedirect(route('support.index'));
        $this->assertDatabaseHas('support_tickets', ['id' => $ticketId, 'status' => 'Closed']);

        $replyAfterClose = $this->withSession(['clientId' => 7])->post("/support/{$ticketId}/reply", ['message' => 'one more thing']);
        $replyAfterClose->assertSessionHas('error');
        $this->assertDatabaseCount('ticket_replies', 1);
    }
}
