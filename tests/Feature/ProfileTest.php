<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(): Client
    {
        return Client::create([
            'email' => 'jane@example.com', 'password' => Hash::make('password123'),
            'firstname' => 'Jane', 'lastname' => 'Doe', 'phonenumber' => '+2348000000000',
            'address1' => 'Old St', 'city' => 'Lagos', 'state' => 'Lagos', 'postcode' => '100001', 'country' => 'NG',
        ]);
    }

    /**
     * The shared layout's currency switcher calls WHMCS unconditionally on every
     * page (pre-existing, unrelated to client identity) — fake it so the test
     * doesn't depend on network access, and assert on the specific claim that
     * matters: ProfileController itself never touches WHMCS for client data.
     */
    private function assertNoClientIdentityCallsSentToWhmcs(): void
    {
        Http::assertNotSent(fn (ClientRequest $r) => in_array($r->data()['action'] ?? null, ['GetClientsDetails', 'UpdateClient'], true));
    }

    public function test_profile_page_shows_local_client_data_with_no_whmcs_calls(): void
    {
        $client = $this->makeClient();
        Http::fake();

        $response = $this->withSession(['clientId' => $client->id])->get('/profile');

        $response->assertOk();
        $response->assertSee('jane@example.com');
        $this->assertNoClientIdentityCallsSentToWhmcs();
    }

    public function test_profile_update_is_fully_local(): void
    {
        $client = $this->makeClient();
        Http::fake();

        $response = $this->withSession(['clientId' => $client->id])->post('/profile', [
            'firstname' => 'Janet', 'lastname' => 'Doe', 'email' => 'jane@example.com',
            'phonenumber' => '+2348000000001', 'address1' => 'New St', 'city' => 'Lagos',
            'state' => 'Lagos', 'postcode' => '100002', 'country' => 'ng',
        ]);

        $response->assertRedirect();
        $client->refresh();
        $this->assertSame('Janet', $client->firstname);
        $this->assertSame('New St', $client->address1);
        $this->assertSame('NG', $client->country);
        $this->assertNoClientIdentityCallsSentToWhmcs();
    }

    public function test_password_update_is_fully_local(): void
    {
        $client = $this->makeClient();
        Http::fake();

        $response = $this->withSession(['clientId' => $client->id])->post('/profile/password', [
            'password' => 'new-password-1', 'password_confirmation' => 'new-password-1',
        ]);

        $response->assertRedirect();
        $this->assertTrue($client->refresh()->checkPassword('new-password-1'));
        $this->assertNoClientIdentityCallsSentToWhmcs();
    }
}
