<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'firstname' => 'Jane',
        'lastname' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'address1' => '123 Main St',
        'city' => 'Lagos',
        'state' => 'Lagos',
        'postcode' => '100001',
        'country' => 'NG',
        'phonenumber' => '+2348000000000',
    ];

    public function test_registration_creates_a_local_client_and_logs_in(): void
    {
        $response = $this->post('/register', $this->payload);

        $response->assertRedirect(route('dashboard'));
        $client = Client::where('email', 'jane@example.com')->first();
        $this->assertNotNull($client);
        $this->assertSame($client->id, session('clientId'));
    }

    public function test_registration_never_calls_whmcs(): void
    {
        Http::fake();

        $this->post('/register', $this->payload);

        $client = Client::where('email', 'jane@example.com')->first();
        $this->assertNotNull($client);
        $this->assertNull($client->whmcs_client_id);
        Http::assertNothingSent();
    }

    public function test_cannot_register_with_an_email_already_in_use_locally(): void
    {
        Client::create(['email' => 'jane@example.com', 'password' => 'x', 'firstname' => 'A', 'lastname' => 'B']);

        $response = $this->post('/register', $this->payload);

        $response->assertSessionHasErrors('email');
    }
}
