<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use App\Models\VpsOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_admin_auth(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_dashboard_shows_local_and_billing_stats(): void
    {
        VpsOrder::create([
            'client_id' => 1, 'category' => 'vps', 'status' => 'provisioned',
            'price' => 10, 'billing_cycle' => 'monthly', 'config' => [],
        ]);
        DomainOrder::create([
            'client_id' => 1, 'domain_name' => 'example', 'tld' => 'com',
            'status' => 'provisioned', 'price' => 10, 'registrant_contact' => [],
        ]);
        PaymentTransaction::create([
            'client_id' => 1, 'invoice_id' => 1, 'gateway' => 'paystack',
            'gateway_reference' => 'ref-1', 'amount' => 100, 'currency' => 'USD', 'status' => 'completed',
        ]);

        Invoice::create(['client_id' => 1, 'status' => 'unpaid', 'currency_code' => 'USD', 'subtotal' => 10.00, 'total' => 10.00]);
        Invoice::create(['client_id' => 1, 'status' => 'unpaid', 'currency_code' => 'USD', 'subtotal' => 5.00, 'total' => 5.00]);
        Invoice::create(['client_id' => 1, 'status' => 'unpaid', 'currency_code' => 'USD', 'subtotal' => 0.00, 'total' => 0.00]);
        for ($i = 0; $i < 12; $i++) {
            Invoice::create(['client_id' => 1, 'status' => 'paid', 'currency_code' => 'USD', 'subtotal' => 1.00, 'total' => 1.00]);
        }

        $department = SupportDepartment::create(['name' => 'Billing']);
        SupportTicket::create(['client_id' => 1, 'department_id' => $department->id, 'subject' => 'a', 'message' => 'b', 'status' => 'Open']);
        SupportTicket::create(['client_id' => 1, 'department_id' => $department->id, 'subject' => 'c', 'message' => 'd', 'status' => 'Open']);
        SupportTicket::create(['client_id' => 1, 'department_id' => $department->id, 'subject' => 'e', 'message' => 'f', 'status' => 'Closed']);

        $response = $this->withSession(['isAdmin' => true])->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('100.00');
        $response->assertSee('Active VPS');
        $response->assertViewHas('billingStats', function (array $stats) {
            return $stats['pending_invoices'] === 3
                && $stats['paid_invoices'] === 12
                && $stats['open_tickets'] === 2
                && $stats['revenue_waiting'] === 15.0;
        });
    }
}
