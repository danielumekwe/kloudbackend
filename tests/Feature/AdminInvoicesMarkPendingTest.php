<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminInvoicesMarkPendingTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsFinanceManager(): Admin
    {
        $admin = Admin::create([
            'email' => 'finance@admin.com',
            'password' => Hash::make('password123'),
            'role' => AdminRole::FinanceManager->value,
        ]);

        session([
            'isAdmin' => true,
            'adminId' => $admin->id,
            'adminEmail' => $admin->email,
            'adminRole' => $admin->role->value,
        ]);

        return $admin;
    }

    private function makeClient(): Client
    {
        return Client::create([
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'firstname' => 'Jane',
            'lastname' => 'Doe',
        ]);
    }

    private function makePaidInvoice(Client $client): Invoice
    {
        return Invoice::create([
            'client_id' => $client->id, 'status' => 'paid', 'currency_code' => 'NGN',
            'subtotal' => 5000.00, 'total' => 5000.00,
            'paid_at' => now(), 'payment_method' => 'manual',
        ]);
    }

    public function test_admin_can_revert_a_paid_invoice_to_pending(): void
    {
        $this->loginAsFinanceManager();
        $invoice = $this->makePaidInvoice($this->makeClient());

        $response = $this->post("/admin/invoices/{$invoice->id}/mark-pending");

        $response->assertRedirect();
        $invoice->refresh();
        $this->assertSame('unpaid', $invoice->status);
        $this->assertNull($invoice->paid_at);
        $this->assertNull($invoice->payment_method);
    }

    public function test_reverting_an_already_unpaid_invoice_is_refused(): void
    {
        $this->loginAsFinanceManager();
        $client = $this->makeClient();
        $invoice = Invoice::create([
            'client_id' => $client->id, 'status' => 'unpaid', 'currency_code' => 'NGN',
            'subtotal' => 5000.00, 'total' => 5000.00,
        ]);

        $response = $this->post("/admin/invoices/{$invoice->id}/mark-pending");

        $response->assertSessionHas('error');
        $this->assertSame('unpaid', $invoice->refresh()->status);
    }

    public function test_reverting_shows_a_warning_when_a_real_payment_transaction_exists(): void
    {
        $this->loginAsFinanceManager();
        $invoice = $this->makePaidInvoice($this->makeClient());
        PaymentTransaction::create([
            'client_id' => $invoice->client_id,
            'invoice_id' => $invoice->id,
            'gateway' => 'paystack',
            'gateway_reference' => 'ref-1',
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'completed',
        ]);

        $response = $this->get("/admin/invoices/{$invoice->id}");

        $response->assertOk();
        $response->assertSee('reverting to pending will not refund it', false);
    }
}
