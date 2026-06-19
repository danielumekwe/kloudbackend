<?php

namespace Tests\Feature\Console;

use App\Models\SslOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProvisionPaidSslTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(array $configOverrides = []): SslOrder
    {
        return SslOrder::create([
            'client_id' => 7,
            'whmcs_invoice_id' => 42,
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

    private function fakeApis(string $invoiceStatus, array $placeOrderResponse): void
    {
        Http::fake(function (ClientRequest $request) use ($invoiceStatus, $placeOrderResponse) {
            if (str_contains($request->url(), 'includes/api.php')) {
                return Http::response(['result' => 'success', 'status' => $invoiceStatus]);
            }
            if (str_contains($request->url(), '/ssl/order')) {
                return Http::response($placeOrderResponse);
            }
            return Http::response(['error' => true], 404);
        });
    }

    public function test_skips_orders_whose_invoice_is_not_paid(): void
    {
        $order = $this->makeOrder();
        $this->fakeApis('Unpaid', ['success' => true, 'serviceid' => 333]);

        $this->artisan('ssl:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('pending_payment', $order->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/ssl/order'));
    }

    public function test_provisions_paid_order(): void
    {
        $order = $this->makeOrder();
        $this->fakeApis('Paid', ['success' => true, 'serviceid' => 333]);

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
        $order = $this->makeOrder();
        $this->fakeApis('Paid', ['success' => false, 'message' => 'Invalid CSR']);

        $this->artisan('ssl:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('failed', $order->status);
        $this->assertSame('Invalid CSR', $order->failure_reason);
    }

    public function test_does_not_retry_an_order_stuck_provisioning_from_a_crashed_run(): void
    {
        $order = $this->makeOrder();
        $order->update(['status' => 'provisioning']);
        $this->fakeApis('Paid', ['success' => true, 'serviceid' => 333]);

        $this->artisan('ssl:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('provisioning', $order->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/ssl/order'));
    }
}
