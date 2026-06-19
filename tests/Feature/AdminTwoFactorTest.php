<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): Admin
    {
        $admin = Admin::create(['email' => 'admin@admin.com', 'password' => Hash::make('password123'), 'role' => 'super_admin']);

        session(['isAdmin' => true, 'adminId' => $admin->id, 'adminEmail' => $admin->email, 'adminRole' => $admin->role->value]);

        return $admin;
    }

    public function test_login_without_two_factor_enabled_goes_straight_to_dashboard(): void
    {
        Admin::create(['email' => 'admin@admin.com', 'password' => Hash::make('password123'), 'role' => 'super_admin']);

        $response = $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => 'password123']);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(session('isAdmin'));
    }

    public function test_login_with_two_factor_enabled_redirects_to_challenge_without_logging_in_yet(): void
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        Admin::create([
            'email' => 'admin@admin.com', 'password' => Hash::make('password123'), 'role' => 'super_admin',
            'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => 'password123']);

        $response->assertRedirect(route('admin.two-factor.challenge'));
        $this->assertFalse(session('isAdmin', false));
    }

    public function test_two_factor_challenge_succeeds_with_a_valid_code(): void
    {
        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();
        $admin = Admin::create([
            'email' => 'admin@admin.com', 'password' => Hash::make('password123'), 'role' => 'super_admin',
            'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => 'password123']);

        $response = $this->post('/admin/two-factor-challenge', ['code' => $google2fa->getCurrentOtp($secret)]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(session('isAdmin'));
        $this->assertSame($admin->id, session('adminId'));
    }

    public function test_two_factor_challenge_rejects_an_invalid_code(): void
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        Admin::create([
            'email' => 'admin@admin.com', 'password' => Hash::make('password123'), 'role' => 'super_admin',
            'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => 'password123']);

        $response = $this->post('/admin/two-factor-challenge', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertFalse(session('isAdmin', false));
    }

    public function test_two_factor_challenge_accepts_a_recovery_code_and_consumes_it(): void
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        $admin = Admin::create([
            'email' => 'admin@admin.com', 'password' => Hash::make('password123'), 'role' => 'super_admin',
            'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['AAAA-BBBB', 'CCCC-DDDD'],
        ]);

        $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => 'password123']);

        $response = $this->post('/admin/two-factor-challenge', ['code' => 'AAAA-BBBB']);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(session('isAdmin'));

        $admin->refresh();
        $this->assertSame(['CCCC-DDDD'], $admin->two_factor_recovery_codes);
    }

    public function test_setup_generates_a_qr_code_and_confirm_enables_two_factor(): void
    {
        $this->loginAsAdmin();

        $setupResponse = $this->get('/admin/security/two-factor/setup');
        $setupResponse->assertOk();

        $secret = session('admin_2fa_setup_secret');
        $this->assertNotNull($secret);

        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $confirmResponse = $this->post('/admin/security/two-factor/confirm', ['code' => $code]);
        $confirmResponse->assertRedirect(route('admin.security'));

        $admin = Admin::where('email', 'admin@admin.com')->first();
        $this->assertTrue($admin->hasTwoFactorEnabled());
        $this->assertCount(8, $admin->two_factor_recovery_codes);
    }

    public function test_disable_requires_the_correct_password(): void
    {
        $admin = $this->loginAsAdmin();
        $admin->update([
            'two_factor_secret' => app(Google2FA::class)->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['AAAA-BBBB'],
        ]);

        $wrongPassword = $this->post('/admin/security/two-factor/disable', ['password' => 'wrong']);
        $wrongPassword->assertSessionHasErrors('password');
        $this->assertTrue($admin->refresh()->hasTwoFactorEnabled());

        $rightPassword = $this->post('/admin/security/two-factor/disable', ['password' => 'password123']);
        $rightPassword->assertRedirect(route('admin.security'));
        $this->assertFalse($admin->refresh()->hasTwoFactorEnabled());
    }
}
