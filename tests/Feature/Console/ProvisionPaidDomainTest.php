<?php

namespace Tests\Feature\Console;

use App\Models\DomainOrder;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProvisionPaidDomainTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(string $status = 'paid'): Invoice
    {
        return Invoice::create([
            'client_id' => 7, 'status' => $status, 'currency_code' => 'USD',
            'subtotal' => 15.00, 'total' => 15.00,
        ]);
    }

    private function makeOrder(Invoice $invoice, array $overrides = []): DomainOrder
    {
        return DomainOrder::create(array_merge([
            'client_id' => 7,
            'invoice_id' => $invoice->id,
            'domain_name' => 'example',
            'tld' => 'com',
            'order_type' => 'register',
            'registration_years' => 1,
            'status' => 'pending_payment',
            'price' => 15.00,
            'whois_privacy' => false,
            'registrant_contact' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com'],
            'config' => ['currency' => 'USD', 'amount_usd' => 15.00],
        ], $overrides));
    }

    private function fakeApi(array $placeOrderResponse): void
    {
        Http::fake(function (ClientRequest $request) use ($placeOrderResponse) {
            if (str_contains($request->url(), '/domains/order')) {
                return Http::response($placeOrderResponse);
            }
            return Http::response(['error' => true], 404);
        });
    }

    public function test_skips_orders_whose_invoice_is_not_paid(): void
    {
        $invoice = $this->makeInvoice('unpaid');
        $order = $this->makeOrder($invoice);
        $this->fakeApi(['success' => true, 'serviceid' => 999]);

        $this->artisan('domains:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('pending_payment', $order->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/domains/order'));
    }

    public function test_provisions_paid_registration_order(): void
    {
        $invoice = $this->makeInvoice('paid');
        $order = $this->makeOrder($invoice);
        $this->fakeApi(['success' => true, 'serviceid' => 999]);

        $this->artisan('domains:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('provisioned', $order->status);
        $this->assertSame(999, $order->interserver_domain_id);

        Http::assertSent(function (ClientRequest $r) {
            if (! str_contains($r->url(), '/domains/order')) {
                return false;
            }
            $data = $r->data();
            return $data['hostname'] === 'example.com'
                && $data['type'] === 'register'
                && $data['whois_privacy'] === 'disable';
        });
    }

    public function test_provisions_paid_transfer_order_with_auth_code(): void
    {
        $invoice = $this->makeInvoice('paid');
        $order = $this->makeOrder($invoice, [
            'order_type' => 'transfer',
            'config' => ['auth_code' => 'secret-auth-code', 'currency' => 'USD', 'amount_usd' => 15.00],
        ]);
        $this->fakeApi(['success' => true, 'serviceid' => 1000]);

        $this->artisan('domains:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('provisioned', $order->status);

        Http::assertSent(function (ClientRequest $r) {
            if (! str_contains($r->url(), '/domains/order')) {
                return false;
            }
            return ($r->data()['auth_code'] ?? null) === 'secret-auth-code';
        });
    }

    public function test_marks_order_failed_when_interserver_rejects_it(): void
    {
        $invoice = $this->makeInvoice('paid');
        $order = $this->makeOrder($invoice);
        $this->fakeApi(['success' => false, 'message' => 'Domain already registered']);

        $this->artisan('domains:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('failed', $order->status);
        $this->assertSame('Domain already registered', $order->failure_reason);
    }

    public function test_does_not_retry_an_order_stuck_provisioning_from_a_crashed_run(): void
    {
        $invoice = $this->makeInvoice('paid');
        $order = $this->makeOrder($invoice, ['status' => 'provisioning']);
        $this->fakeApi(['success' => true, 'serviceid' => 999]);

        $this->artisan('domains:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('provisioning', $order->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/domains/order'));
    }
}
