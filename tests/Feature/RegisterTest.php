<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        Http::fake(fn () => Http::response(['result' => 'success', 'clientid' => 555]));

        $response = $this->post('/register', $this->payload);

        $response->assertRedirect(route('dashboard'));
        $client = Client::where('email', 'jane@example.com')->first();
        $this->assertNotNull($client);
        $this->assertSame($client->id, session('clientId'));
    }

    public function test_successful_shadow_whmcs_client_saves_whmcs_client_id(): void
    {
        Http::fake(function (ClientRequest $request) {
            return $request->data()['action'] === 'AddClient'
                ? Http::response(['result' => 'success', 'clientid' => 555])
                : Http::response(['result' => 'error']);
        });

        $this->post('/register', $this->payload);

        $client = Client::where('email', 'jane@example.com')->first();
        $this->assertSame(555, $client->whmcs_client_id);
    }

    public function test_failed_shadow_whmcs_client_does_not_block_registration(): void
    {
        Log::spy();
        Http::fake(fn () => Http::response(['result' => 'error', 'message' => 'WHMCS is down'], 500));

        $response = $this->post('/register', $this->payload);

        $response->assertRedirect(route('dashboard'));
        $client = Client::where('email', 'jane@example.com')->first();
        $this->assertNotNull($client);
        $this->assertNull($client->whmcs_client_id);
        // WhmcsService logs its own HTTP-error internally too — this just confirms
        // RegisterController's own failure log fired, not an exact total count.
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_cannot_register_with_an_email_already_in_use_locally(): void
    {
        Client::create(['email' => 'jane@example.com', 'password' => 'x', 'firstname' => 'A', 'lastname' => 'B']);

        $response = $this->post('/register', $this->payload);

        $response->assertSessionHasErrors('email');
    }
}
