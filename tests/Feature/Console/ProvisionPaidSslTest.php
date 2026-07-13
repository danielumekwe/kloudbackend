<?php

namespace Tests\Feature\Console;

use App\Models\Invoice;
use App\Models\SslOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProvisionPaidSslTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(string $status = 'paid'): Invoice
    {
        return Invoice::create([
            'client_id' => 7, 'status' => $status, 'currency_code' => 'USD',
            'subtotal' => 12.00, 'total' => 12.00,
        ]);
    }

    private function makeOrder(Invoice $invoice, array $configOverrides = []): SslOrder
    {
        return SslOrder::create([
            'client_id' => 7,
            'invoice_id' => $invoice->id,
            'status' => 'pending_payment',
            'price' => 12.00,
            'billing_cycle' => 'annually',
            'config' => array_merge([
                'package_id' => 'pos-dv',
                'hostname' => 'example.com',
                'approver_email' => 'admin@example.com',
                'csr_type' => 'generate',
                'currency' => 'USD',
                'amount_usd' => 12.00,
            ], $configOverrides),
        ]);
    }

    private function fakeApi(array $placeOrderResponse): void
    {
        Http::fake(function (ClientRequest $request) use ($placeOrderResponse) {
            if (str_contains($request->url(), '/ssl/order')) {
                return Http::response($placeOrderResponse);
            }
            return Http::response(['error' => true], 404);
        });
    }

    public function test_skips_orders_whose_invoice_is_not_paid(): void
    {
        $invoice = $this->makeInvoice('unpaid');
        $order = $this->makeOrder($invoice);
        $this->fakeApi(['success' => true, 'serviceid' => 333]);

        $this->artisan('ssl:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('pending_payment', $order->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/ssl/order'));
    }

    public function test_provisions_paid_order(): void
    {
        $invoice = $this->makeInvoice('paid');
        $order = $this->makeOrder($invoice);
        $this->fakeApi(['success' => true, 'serviceid' => 333]);

        $this->artisan('ssl:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('provisioned', $order->status);
        $this->assertSame(333, $order->interserver_ssl_id);

        Http::assertSent(function (ClientRequest $r) {
            if (! str_contains($r->url(), '/ssl/order')) {
                return false;
            }
            return ($r->data()['ssl'] ?? null) === 'pos-dv'
                && ($r->data()['hostname'] ?? null) === 'example.com';
        });
    }

    public function test_marks_order_failed_when_interserver_rejects_it(): void
    {
        $invoice = $this->makeInvoice('paid');
        $order = $this->makeOrder($invoice);
        $this->fakeApi(['success' => false, 'message' => 'Invalid CSR']);

        $this->artisan('ssl:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('failed', $order->status);
        $this->assertSame('Invalid CSR', $order->failure_reason);
    }

    public function test_does_not_retry_an_order_stuck_provisioning_from_a_crashed_run(): void
    {
        $invoice = $this->makeInvoice('paid');
        $order = $this->makeOrder($invoice);
        $order->update(['status' => 'provisioning']);
        $this->fakeApi(['success' => true, 'serviceid' => 333]);

        $this->artisan('ssl:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('provisioning', $order->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/ssl/order'));
    }
}
