<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\PaymentGatewaySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function loginAs(AdminRole $role): Admin
    {
        $admin = Admin::create([
            'email' => $role->value . '@admin.com',
            'password' => Hash::make('password123'),
            'role' => $role->value,
        ]);

        session([
            'isAdmin' => true,
            'adminId' => $admin->id,
            'adminEmail' => $admin->email,
            'adminRole' => $admin->role->value,
        ]);

        return $admin;
    }

    public function test_super_admin_can_view_payment_settings(): void
    {
        $this->loginAs(AdminRole::SuperAdmin);

        $response = $this->get('/admin/payment-settings');

        $response->assertOk();
    }

    public function test_finance_manager_cannot_view_payment_settings(): void
    {
        $this->loginAs(AdminRole::FinanceManager);

        $response = $this->get('/admin/payment-settings');

        $response->assertStatus(403);
    }

    public function test_support_agent_cannot_view_payment_settings(): void
    {
        $this->loginAs(AdminRole::SupportAgent);

        $response = $this->get('/admin/payment-settings');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_save_gateway_credentials(): void
    {
        $this->loginAs(AdminRole::SuperAdmin);

        $response = $this->post('/admin/payment-settings', [
            'paystack' => ['public_key' => 'pk_test_123', 'secret_key' => 'sk_test_abcd1234'],
        ]);

        $response->assertRedirect();
        $this->assertSame('pk_test_123', PaymentGatewaySetting::get('paystack', 'public_key'));
        $this->assertSame('sk_test_abcd1234', PaymentGatewaySetting::get('paystack', 'secret_key'));
    }

    public function test_blank_fields_do_not_overwrite_existing_credentials(): void
    {
        $this->loginAs(AdminRole::SuperAdmin);
        PaymentGatewaySetting::set('paystack', 'secret_key', 'sk_existing_value');

        $this->post('/admin/payment-settings', [
            'paystack' => ['public_key' => 'pk_test_123', 'secret_key' => ''],
        ]);

        $this->assertSame('sk_existing_value', PaymentGatewaySetting::get('paystack', 'secret_key'));
    }

    public function test_secret_values_are_masked_and_never_shown_in_full(): void
    {
        $this->loginAs(AdminRole::SuperAdmin);
        PaymentGatewaySetting::set('paystack', 'secret_key', 'sk_live_supersecretvalue');

        $response = $this->get('/admin/payment-settings');

        $response->assertOk();
        $response->assertDontSee('sk_live_supersecretvalue');
        $response->assertSee('lue', false); // last 4 chars of the masked display
    }
}
