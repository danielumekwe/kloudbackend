<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClientAuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(array $overrides = []): Client
    {
        return Client::create(array_merge([
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'firstname' => 'Jane',
            'lastname' => 'Doe',
        ], $overrides));
    }

    public function test_login_succeeds_with_a_bcrypt_password(): void
    {
        $client = $this->makeClient();

        $response = $this->post('/login', ['email' => 'jane@example.com', 'password' => 'password123']);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame($client->id, session('clientId'));
    }

    public function test_login_succeeds_with_a_legacy_md5_password_and_upgrades_it(): void
    {
        $client = $this->makeClient(['password' => md5('password123')]);

        $response = $this->post('/login', ['email' => 'jane@example.com', 'password' => 'password123']);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Hash::check('password123', $client->refresh()->password));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeClient();

        $response = $this->post('/login', ['email' => 'jane@example.com', 'password' => 'wrongpassword']);

        $response->assertSessionHasErrors('email');
        $this->assertNull(session('clientId'));
    }

    public function test_login_fails_for_unknown_email(): void
    {
        $response = $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'whatever1']);

        $response->assertSessionHasErrors('email');
    }

    public function test_remember_cookie_restores_session(): void
    {
        Http::fake(fn () => Http::response(['result' => 'error']));
        $client = $this->makeClient();

        $response = $this->withCookie('kloud101_remember', (string) $client->id)->get('/dashboard');

        $response->assertOk();
        $this->assertSame($client->id, session('clientId'));
    }

    public function test_remember_cookie_for_a_deleted_client_redirects_to_login(): void
    {
        $response = $this->withCookie('kloud101_remember', '999999')->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_logout_clears_session_and_remember_cookie(): void
    {
        $client = $this->makeClient();
        $this->withSession(['clientId' => $client->id]);

        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertNull(session('clientId'));
    }
}
