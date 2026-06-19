<?php

namespace Tests\Feature\Console;

use App\Models\QsOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProvisionPaidQsTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(array $configOverrides = []): QsOrder
    {
        return QsOrder::create([
            'client_id' => 7,
            'whmcs_invoice_id' => 42,
            'status' => 'pending_payment',
            'price' => 10.00,
            'billing_cycle' => 1,
            'config' => array_merge([
                'server' => 'qs-plan-1',
                'os' => 'ubuntu-22.04',
                'comment' => 'test order',
                'password' => Crypt::encryptString('Sup3rSecret!'),
                'currency' => 'USD',
                'amount_usd' => 10.00,
            ], $configOverrides),
        ]);
    }

    private function fakeApis(string $invoiceStatus, array $placeOrderResponse): void
    {
        Http::fake(function (ClientRequest $request) use ($invoiceStatus, $placeOrderResponse) {
            if (str_contains($request->url(), 'includes/api.php')) {
                return Http::response(['result' => 'success', 'status' => $invoiceStatus]);
            }
            if (str_contains($request->url(), '/qs/order')) {
                return Http::response($placeOrderResponse);
            }
            return Http::response(['error' => true], 404);
        });
    }

    public function test_skips_orders_whose_invoice_is_not_paid(): void
    {
        $order = $this->makeOrder();
        $this->fakeApis('Unpaid', ['success' => true, 'serviceid' => 222]);

        $this->artisan('qs:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('pending_payment', $order->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/qs/order'));
    }

    public function test_provisions_paid_order_and_decrypts_password(): void
    {
        $order = $this->makeOrder();
        $this->fakeApis('Paid', ['success' => true, 'serviceid' => 222]);

        $this->artisan('qs:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('provisioned', $order->status);
        $this->assertSame(222, $order->interserver_qs_id);

        Http::assertSent(function (ClientRequest $r) {
            if (! str_contains($r->url(), '/qs/order')) {
                return false;
            }
            return ($r->data()['password'] ?? null) === 'Sup3rSecret!'
                && ($r->data()['server'] ?? null) === 'qs-plan-1'
                && ($r->data()['tos'] ?? null) === true;
        });
    }

    public function test_marks_order_failed_when_interserver_rejects_it(): void
    {
        $order = $this->makeOrder();
        $this->fakeApis('Paid', ['success' => false, 'message' => 'Out of capacity']);

        $this->artisan('qs:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('failed', $order->status);
        $this->assertSame('Out of capacity', $order->failure_reason);
    }

    public function test_does_not_retry_an_order_stuck_provisioning_from_a_crashed_run(): void
    {
        $order = $this->makeOrder();
        $order->update(['status' => 'provisioning']);
        $this->fakeApis('Paid', ['success' => true, 'serviceid' => 222]);

        $this->artisan('qs:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('provisioning', $order->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/qs/order'));
    }
}
