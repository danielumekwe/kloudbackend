<?php

namespace Tests\Feature\Console;

use App\Models\VpsOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProvisionPaidVpsTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(array $configOverrides = []): VpsOrder
    {
        return VpsOrder::create([
            'client_id' => 7,
            'category' => 'vps',
            'whmcs_invoice_id' => 42,
            'status' => 'pending_payment',
            'price' => 20.00,
            'billing_cycle' => 'monthly',
            'config' => array_merge([
                'platform' => 'kvm',
                'controlpanel' => 'none',
                'osDistro' => 'ubuntu',
                'osVersion' => '22.04',
                'slices' => 2,
                'location' => 'us',
                'period' => 'monthly',
                'hostname' => 'vps1.example.com',
                'rootpass' => Crypt::encryptString('Sup3rSecret!'),
                'currency' => 'USD',
                'amount_usd' => 20.00,
            ], $configOverrides),
        ]);
    }

    private function fakeApis(string $invoiceStatus, array $placeOrderResponse): void
    {
        Http::fake(function (ClientRequest $request) use ($invoiceStatus, $placeOrderResponse) {
            if (str_contains($request->url(), 'includes/api.php')) {
                return Http::response(['result' => 'success', 'status' => $invoiceStatus]);
            }
            if (str_contains($request->url(), '/vps/order')) {
                return Http::response($placeOrderResponse);
            }
            return Http::response(['error' => true], 404);
        });
    }

    public function test_skips_orders_whose_invoice_is_not_paid(): void
    {
        $order = $this->makeOrder();
        $this->fakeApis('Unpaid', ['success' => true, 'serviceid' => 111]);

        $this->artisan('vps:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('pending_payment', $order->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/vps/order'));
    }

    public function test_provisions_paid_order_and_decrypts_root_password(): void
    {
        $order = $this->makeOrder();
        $this->fakeApis('Paid', ['success' => true, 'serviceid' => 111]);

        $this->artisan('vps:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('provisioned', $order->status);
        $this->assertSame(111, $order->interserver_vps_id);

        Http::assertSent(function (ClientRequest $r) {
            if (! str_contains($r->url(), '/vps/order')) {
                return false;
            }
            return ($r->data()['rootpass'] ?? null) === 'Sup3rSecret!'
                && ($r->data()['hostname'] ?? null) === 'vps1.example.com';
        });
    }

    public function test_marks_order_failed_when_interserver_rejects_it(): void
    {
        $order = $this->makeOrder();
        $this->fakeApis('Paid', ['success' => false, 'message' => 'Out of capacity']);

        $this->artisan('vps:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('failed', $order->status);
        $this->assertSame('Out of capacity', $order->failure_reason);
    }

    public function test_does_not_retry_an_order_stuck_provisioning_from_a_crashed_run(): void
    {
        $order = $this->makeOrder();
        $order->update(['status' => 'provisioning']);
        $this->fakeApis('Paid', ['success' => true, 'serviceid' => 111]);

        $this->artisan('vps:provision-paid')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('provisioning', $order->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/vps/order'));
    }
}
