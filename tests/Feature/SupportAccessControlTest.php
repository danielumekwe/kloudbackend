<?php

namespace Tests\Feature;

use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(int $clientId): SupportTicket
    {
        $department = SupportDepartment::create(['name' => 'Billing']);

        return SupportTicket::create([
            'client_id' => $clientId,
            'department_id' => $department->id,
            'subject' => 'Test ticket',
            'message' => 'Test message',
        ]);
    }

    public function test_client_cannot_view_another_clients_ticket(): void
    {
        $ticket = $this->makeTicket(clientId: 10);

        $response = $this->withSession(['clientId' => 7])->get("/support/{$ticket->id}");

        $response->assertStatus(404);
    }

    public function test_client_can_view_their_own_ticket(): void
    {
        $ticket = $this->makeTicket(clientId: 7);

        $response = $this->withSession(['clientId' => 7])->get("/support/{$ticket->id}");

        $response->assertOk();
    }

    public function test_client_cannot_reply_to_another_clients_ticket(): void
    {
        $ticket = $this->makeTicket(clientId: 10);

        $response = $this->withSession(['clientId' => 7])->post("/support/{$ticket->id}/reply", ['message' => 'Hello there']);

        $response->assertStatus(404);
    }

    public function test_client_cannot_close_another_clients_ticket(): void
    {
        $ticket = $this->makeTicket(clientId: 10);

        $response = $this->withSession(['clientId' => 7])->post("/support/{$ticket->id}/close");

        $response->assertStatus(404);
    }
}
