<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(): Client
    {
        return Client::create([
            'email' => 'client-' . uniqid() . '@example.com', 'password' => 'x',
            'firstname' => 'Jane', 'lastname' => 'Doe',
        ]);
    }

    public function test_client_cannot_view_another_clients_invoice(): void
    {
        $client = $this->makeClient();
        $otherClient = $this->makeClient();
        $invoice = Invoice::create([
            'client_id' => $otherClient->id, 'status' => 'unpaid', 'currency_code' => 'USD',
            'subtotal' => 10.00, 'total' => 10.00,
        ]);

        $response = $this->withSession(['clientId' => $client->id])->get("/billing/{$invoice->id}");

        $response->assertStatus(404);
    }

    public function test_client_can_view_their_own_invoice(): void
    {
        $client = $this->makeClient();
        $invoice = Invoice::create([
            'client_id' => $client->id, 'status' => 'unpaid', 'currency_code' => 'USD',
            'subtotal' => 10.00, 'total' => 10.00,
        ]);

        $response = $this->withSession(['clientId' => $client->id])->get("/billing/{$invoice->id}");

        $response->assertOk();
    }
}
