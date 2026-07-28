<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private array $step1 = [
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    private array $step2 = [
        'firstname' => 'Jane',
        'lastname' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Lagos',
        'state' => 'Lagos',
        'postcode' => '100001',
        'country' => 'NG',
        'phonenumber' => '+2348000000000',
    ];

    public function test_step_one_stores_pending_registration_and_redirects_to_complete(): void
    {
        $response = $this->post('/register', $this->step1);

        $response->assertRedirect(route('register.complete'));
        $this->assertSame('jane@example.com', session('register_pending')['email']);
        $this->assertNull(Client::where('email', 'jane@example.com')->first());
    }

    public function test_step_two_creates_a_local_client_and_logs_in(): void
    {
        $response = $this->withSession(['register_pending' => [
            'email' => 'jane@example.com', 'password' => bcrypt('password123'),
        ]])->post('/register/complete', $this->step2);

        $response->assertRedirect(route('dashboard'));
        $client = Client::where('email', 'jane@example.com')->first();
        $this->assertNotNull($client);
        $this->assertSame($client->id, session('clientId'));
        $this->assertNull(session('register_pending'));
    }

    public function test_step_two_never_calls_whmcs(): void
    {
        Http::fake();

        $this->withSession(['register_pending' => [
            'email' => 'jane@example.com', 'password' => bcrypt('password123'),
        ]])->post('/register/complete', $this->step2);

        $client = Client::where('email', 'jane@example.com')->first();
        $this->assertNotNull($client);
        $this->assertNull($client->whmcs_client_id);
        Http::assertNothingSent();
    }

    public function test_step_two_without_pending_session_redirects_to_register(): void
    {
        $response = $this->post('/register/complete', $this->step2);

        $response->assertRedirect(route('register'));
    }

    public function test_cannot_register_with_an_email_already_in_use_locally(): void
    {
        Client::create(['email' => 'jane@example.com', 'password' => 'x', 'firstname' => 'A', 'lastname' => 'B']);

        $response = $this->post('/register', $this->step1);

        $response->assertSessionHasErrors('email');
    }
}
