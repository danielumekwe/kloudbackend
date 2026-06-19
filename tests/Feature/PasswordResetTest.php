<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function fakeWhmcsClient(string $email): void
    {
        Http::fake(function (ClientRequest $request) use ($email) {
            $action = $request->data()['action'] ?? null;

            return match ($action) {
                'GetClientsDetails' => Http::response([
                    'result' => 'success',
                    'client' => ['id' => 7, 'firstname' => 'Jane', 'email' => $email],
                ]),
                'UpdateClient' => Http::response(['result' => 'success']),
                default => Http::response(['result' => 'error'], 404),
            };
        });
    }

    public function test_reset_link_request_does_not_leak_whether_email_exists(): void
    {
        Mail::fake();
        Http::fake(fn () => Http::response(['result' => 'error']));

        $response = $this->post('/password/forgot', ['email' => 'nobody@example.com']);

        $response->assertSessionHas('status');
        Mail::assertNothingSent();
    }

    public function test_reset_password_rejects_an_expired_token(): void
    {
        $this->fakeWhmcsClient('jane@example.com');

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
        $this->fakeWhmcsClient('jane@example.com');

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
    }
}
