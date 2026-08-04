<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminInvoicesCreateTest extends TestCase
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

    public function test_create_form_finds_clients_by_search_query(): void
    {
        $this->loginAsFinanceManager();
        $client = $this->makeClient();

        $response = $this->get('/admin/invoices/create?q=jane');

        $response->assertOk();
        $response->assertSee($client->email);
    }

    public function test_admin_can_create_a_standalone_ngn_invoice_for_a_client(): void
    {
        $this->loginAsFinanceManager();
        $client = $this->makeClient();

        $response = $this->post('/admin/invoices', [
            'client_id'   => $client->id,
            'description' => 'Custom setup fee',
            'amount'      => 15000,
        ]);

        $invoice = Invoice::where('client_id', $client->id)->first();
        $response->assertRedirect(route('admin.invoices.show', $invoice->id));
        $this->assertSame('NGN', $invoice->currency_code);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame('15000.00', (string) $invoice->subtotal);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Custom setup fee',
        ]);
    }

    public function test_amount_must_be_positive(): void
    {
        $this->loginAsFinanceManager();
        $client = $this->makeClient();

        $response = $this->post('/admin/invoices', [
            'client_id'   => $client->id,
            'description' => 'Custom setup fee',
            'amount'      => 0,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_client_id_must_exist(): void
    {
        $this->loginAsFinanceManager();

        $response = $this->post('/admin/invoices', [
            'client_id'   => 999999,
            'description' => 'Custom setup fee',
            'amount'      => 5000,
        ]);

        $response->assertSessionHasErrors('client_id');
        $this->assertDatabaseCount('invoices', 0);
    }
}
