<?php

namespace Tests\Feature;

use App\Mail\AdminPasswordResetMail;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_fails_safely_for_an_unrecognized_password_hash_format(): void
    {
        $admin = Admin::create(['email' => 'admin@admin.com', 'password' => 'not-a-real-hash']);

        $this->assertFalse($admin->checkPassword('anything'));
    }

    public function test_login_succeeds_with_a_legacy_md5_password(): void
    {
        Admin::create(['email' => 'admin@admin.com', 'password' => md5('12345678')]);

        $response = $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => '12345678']);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(session('isAdmin'));
    }

    public function test_login_succeeds_with_a_bcrypt_password(): void
    {
        Admin::create(['email' => 'admin@admin.com', 'password' => Hash::make('a-strong-password')]);

        $response = $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => 'a-strong-password']);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(session('isAdmin'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        Admin::create(['email' => 'admin@admin.com', 'password' => md5('12345678')]);

        $response = $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => 'wrong-password']);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(session('isAdmin', false));
    }

    public function test_login_fails_for_unknown_email(): void
    {
        $response = $this->post('/admin/login', ['email' => 'nobody@admin.com', 'password' => 'whatever123']);

        $response->assertSessionHasErrors('email');
    }

    public function test_logout_clears_admin_session(): void
    {
        Admin::create(['email' => 'admin@admin.com', 'password' => md5('12345678')]);
        $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => '12345678']);

        $this->post('/admin/logout');

        $this->assertFalse(session('isAdmin', false));
    }

    public function test_forgot_password_sends_reset_link_for_known_admin_email(): void
    {
        Mail::fake();
        Admin::create(['email' => 'admin@admin.com', 'password' => md5('12345678')]);

        $response = $this->post('/admin/forgot-password', ['email' => 'admin@admin.com']);

        $response->assertSessionHas('status');
        Mail::assertSent(AdminPasswordResetMail::class);
        $this->assertDatabaseHas('admin_password_reset_tokens', ['email' => 'admin@admin.com']);
    }

    public function test_forgot_password_does_not_leak_whether_email_exists(): void
    {
        Mail::fake();

        $response = $this->post('/admin/forgot-password', ['email' => 'nobody@admin.com']);

        $response->assertSessionHas('status');
        Mail::assertNothingSent();
    }

    public function test_reset_password_with_valid_token_updates_password_to_bcrypt(): void
    {
        $admin = Admin::create(['email' => 'admin@admin.com', 'password' => md5('12345678')]);

        $token = 'a-valid-reset-token';
        DB::table('admin_password_reset_tokens')->insert([
            'email' => $admin->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertRedirect(route('admin.login'));

        $admin->refresh();
        $this->assertTrue(Hash::check('brand-new-password', $admin->password));
        $this->assertDatabaseMissing('admin_password_reset_tokens', ['email' => $admin->email]);
    }

    public function test_reset_password_rejects_an_expired_token(): void
    {
        $admin = Admin::create(['email' => 'admin@admin.com', 'password' => md5('12345678')]);

        $token = 'an-old-reset-token';
        DB::table('admin_password_reset_tokens')->insert([
            'email' => $admin->email,
            'token' => Hash::make($token),
            'created_at' => now()->subMinutes(61),
        ]);

        $response = $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertSessionHasErrors('email');
        $admin->refresh();
        $this->assertSame(md5('12345678'), $admin->password);
    }

    public function test_login_is_rate_limited_after_repeated_attempts(): void
    {
        Admin::create(['email' => 'admin@admin.com', 'password' => md5('12345678')]);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => 'wrong-password']);
        }

        $response = $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => 'wrong-password']);

        $response->assertStatus(429);
    }
}
