<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(string $email): Client
    {
        return Client::create([
            'email' => $email, 'password' => Hash::make('old-password'),
            'firstname' => 'Jane', 'lastname' => 'Doe',
        ]);
    }

    public function test_reset_link_request_does_not_leak_whether_email_exists(): void
    {
        Mail::fake();

        $response = $this->post('/password/forgot', ['email' => 'nobody@example.com']);

        $response->assertSessionHas('status');
        Mail::assertNothingSent();
    }

    public function test_reset_link_request_sends_mail_for_a_known_email(): void
    {
        Mail::fake();
        $this->makeClient('jane@example.com');

        $response = $this->post('/password/forgot', ['email' => 'jane@example.com']);

        $response->assertSessionHas('status');
        Mail::assertSent(PasswordResetMail::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'jane@example.com']);
    }

    public function test_reset_password_rejects_an_expired_token(): void
    {
        $this->makeClient('jane@example.com');

        $token = 'an-old-reset-token';
        DB::table('password_reset_tokens')->insert([
            'email' => 'jane@example.com',
            'token' => Hash::make($token),
            'created_at' => now()->subMinutes(61),
        ]);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_reset_password_succeeds_with_a_valid_unexpired_token(): void
    {
        $client = $this->makeClient('jane@example.com');

        $token = 'a-valid-reset-token';
        DB::table('password_reset_tokens')->insert([
            'email' => 'jane@example.com',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'jane@example.com']);
        $this->assertTrue($client->refresh()->checkPassword('brand-new-password'));
    }
}
