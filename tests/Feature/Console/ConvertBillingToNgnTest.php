<?php

namespace Tests\Feature\Console;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PricingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConvertBillingToNgnTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(array $overrides = []): Client
    {
        return Client::create(array_merge([
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'firstname' => 'Jane',
            'lastname' => 'Doe',
        ], $overrides));
    }

    private function makeInvoice(Client $client, array $overrides = []): Invoice
    {
        $invoice = Invoice::create(array_merge([
            'client_id' => $client->id, 'status' => 'unpaid', 'currency_code' => 'USD',
            'subtotal' => 20.00, 'tax_rate' => 0, 'tax_amount' => 0, 'total' => 20.00,
        ], $overrides));

        $invoice->items()->create(['description' => 'VPS order', 'amount' => 20.00]);

        return $invoice;
    }

    public function test_converts_unpaid_usd_invoice_to_ngn_at_admin_rate(): void
    {
        PricingSetting::set('currency.rates.NGN', 1500.0);
        $client = $this->makeClient();
        $invoice = $this->makeInvoice($client);

        $this->artisan('billing:convert-to-ngn')->assertExitCode(0);

        $invoice->refresh();
        $this->assertSame('NGN', $invoice->currency_code);
        $this->assertSame('30000.00', (string) $invoice->subtotal);
        $this->assertSame('30000.00', (string) $invoice->total);
        $this->assertSame('30000.00', (string) $invoice->items()->first()->amount);
    }

    public function test_paid_and_cancelled_invoices_are_untouched(): void
    {
        PricingSetting::set('currency.rates.NGN', 1500.0);
        $client = $this->makeClient();
        $paid = $this->makeInvoice($client, ['status' => 'paid']);
        $cancelled = $this->makeInvoice($client, ['status' => 'cancelled']);

        $this->artisan('billing:convert-to-ngn')->assertExitCode(0);

        $this->assertSame('USD', $paid->refresh()->currency_code);
        $this->assertSame('USD', $cancelled->refresh()->currency_code);
    }

    public function test_dry_run_makes_no_database_writes(): void
    {
        PricingSetting::set('currency.rates.NGN', 1500.0);
        $client = $this->makeClient(['credit_balance' => 10.00]);
        $invoice = $this->makeInvoice($client);

        $this->artisan('billing:convert-to-ngn --dry-run')->assertExitCode(0);

        $this->assertSame('USD', $invoice->refresh()->currency_code);
        $this->assertSame('10.00', (string) $client->refresh()->credit_balance);
    }

    public function test_rerunning_is_idempotent(): void
    {
        PricingSetting::set('currency.rates.NGN', 1500.0);
        $client = $this->makeClient();
        $invoice = $this->makeInvoice($client);

        $this->artisan('billing:convert-to-ngn')->assertExitCode(0);
        $totalAfterFirstRun = $invoice->refresh()->total;

        $this->artisan('billing:convert-to-ngn')->assertExitCode(0);

        $this->assertSame((string) $totalAfterFirstRun, (string) $invoice->refresh()->total);
    }

    public function test_converts_wallet_balances_and_skips_on_rerun(): void
    {
        PricingSetting::set('currency.rates.NGN', 1500.0);
        $client = $this->makeClient(['credit_balance' => 10.00]);

        $this->artisan('billing:convert-to-ngn')->assertExitCode(0);
        $this->assertSame('15000.00', (string) $client->refresh()->credit_balance);

        // Manually bump the balance again to prove a second run is a no-op (flag guarded).
        $client->update(['credit_balance' => 20.00]);
        $this->artisan('billing:convert-to-ngn')->assertExitCode(0);

        $this->assertSame('20.00', (string) $client->refresh()->credit_balance);
    }
}
