<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SupportAccessControlTest extends TestCase
{
    private function fakeWhmcs(array $ownedTicketIds): void
    {
        Http::fake(function (ClientRequest $request) use ($ownedTicketIds) {
            $action = $request->data()['action'] ?? null;

            return match ($action) {
                'GetTickets' => Http::response([
                    'result' => 'success',
                    'tickets' => ['ticket' => array_map(fn ($id) => ['id' => $id, 'tid' => $id], $ownedTicketIds)],
                ]),
                'GetTicket' => Http::response([
                    'result' => 'success',
                    'tid' => 55,
                    'ticketid' => 'TICK-55',
                    'subject' => 'Test',
                    'message' => 'Test message',
                    'status' => 'Open',
                    'priority' => 'Low',
                    'date' => '2026-06-01',
                    'department' => 'Support',
                    'deptname' => 'Support',
                    'replies' => ['reply' => []],
                ]),
                'AddTicketReply' => Http::response(['result' => 'success']),
                'CloseTicket' => Http::response(['result' => 'success']),
                'GetCurrencies' => Http::response(['result' => 'error']),
                default => Http::response(['result' => 'error'], 404),
            };
        });
    }

    public function test_client_cannot_view_another_clients_ticket(): void
    {
        $this->fakeWhmcs(ownedTicketIds: [10, 20]);

        $response = $this->withSession(['clientId' => 7])->get('/support/55');

        $response->assertStatus(404);
    }

    public function test_client_can_view_their_own_ticket(): void
    {
        $this->fakeWhmcs(ownedTicketIds: [55]);

        $response = $this->withSession(['clientId' => 7])->get('/support/55');

        $response->assertOk();
    }

    public function test_client_cannot_reply_to_another_clients_ticket(): void
    {
        $this->fakeWhmcs(ownedTicketIds: [10, 20]);

        $response = $this->withSession(['clientId' => 7])->post('/support/55/reply', ['message' => 'Hello there']);

        $response->assertStatus(404);
    }

    public function test_client_cannot_close_another_clients_ticket(): void
    {
        $this->fakeWhmcs(ownedTicketIds: [10, 20]);

        $response = $this->withSession(['clientId' => 7])->post('/support/55/close');

        $response->assertStatus(404);
    }
}
