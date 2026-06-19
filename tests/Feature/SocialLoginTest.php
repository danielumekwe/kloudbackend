<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    private function mockSocialiteUser(string $email, string $name = 'Jane Doe'): void
    {
        $socialUser = Mockery::mock('Laravel\Socialite\Contracts\User');
        $socialUser->shouldReceive('getEmail')->andReturn($email);
        $socialUser->shouldReceive('getName')->andReturn($name);

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_callback_logs_in_an_existing_client_found_by_email(): void
    {
        $client = Client::create(['email' => 'jane@example.com', 'password' => 'x', 'firstname' => 'Jane', 'lastname' => 'Doe']);
        $this->mockSocialiteUser('jane@example.com');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard'));
        $this->assertSame($client->id, session('clientId'));
    }

    public function test_callback_sends_a_new_email_to_complete_registration(): void
    {
        $this->mockSocialiteUser('new-person@example.com');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('social.complete'));
        $this->assertSame('new-person@example.com', session('social_pending')['email']);
    }

    public function test_complete_registration_creates_a_local_client_and_dual_writes_to_whmcs(): void
    {
        Http::fake(function (ClientRequest $request) {
            return $request->data()['action'] === 'AddClient'
                ? Http::response(['result' => 'success', 'clientid' => 777])
                : Http::response(['result' => 'error']);
        });

        $response = $this->withSession(['social_pending' => [
            'provider' => 'google', 'email' => 'new-person@example.com', 'firstname' => 'New', 'lastname' => 'Person',
        ]])->post('/auth/social/complete', [
            'address1' => '1 Main St', 'city' => 'Lagos', 'state' => 'Lagos',
            'postcode' => '100001', 'country' => 'NG', 'phonenumber' => '+2348000000000',
        ]);

        $response->assertRedirect(route('dashboard'));
        $client = Client::where('email', 'new-person@example.com')->first();
        $this->assertNotNull($client);
        $this->assertSame(777, $client->whmcs_client_id);
        $this->assertSame($client->id, session('clientId'));
        $this->assertNull(session('social_pending'));
    }

    public function test_complete_registration_without_pending_session_redirects_to_login(): void
    {
        $response = $this->post('/auth/social/complete', [
            'address1' => '1 Main St', 'city' => 'Lagos', 'state' => 'Lagos',
            'postcode' => '100001', 'country' => 'NG', 'phonenumber' => '+2348000000000',
        ]);

        $response->assertRedirect(route('login'));
    }
}
