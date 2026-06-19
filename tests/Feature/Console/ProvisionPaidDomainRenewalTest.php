<?php

namespace Tests\Feature\Console;

use App\Models\DomainOrder;
use App\Models\DomainRenewal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProvisionPaidDomainRenewalTest extends TestCase
{
    use RefreshDatabase;

    private function makeRenewal(array $overrides = []): DomainRenewal
    {
        $order = DomainOrder::create([
            'client_id' => 7,
            'whmcs_invoice_id' => 41,
            'interserver_domain_id' => 555,
            'domain_name' => 'example',
            'tld' => 'com',
            'order_type' => 'register',
            'registration_years' => 1,
            'status' => 'provisioned',
            'price' => 15.00,
            'whois_privacy' => false,
            'registrant_contact' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com'],
        ]);

        return DomainRenewal::create(array_merge([
            'domain_order_id' => $order->id,
            'whmcs_invoice_id' => 99,
            'years' => 2,
            'price' => 30.00,
            'status' => 'pending_payment',
            'config' => ['currency' => 'USD', 'amount_usd' => 30.00],
        ], $overrides));
    }

    private function fakeApis(string $invoiceStatus, array $renewResponse): void
    {
        Http::fake(function (ClientRequest $request) use ($invoiceStatus, $renewResponse) {
            if (str_contains($request->url(), 'includes/api.php')) {
                return Http::response(['result' => 'success', 'status' => $invoiceStatus]);
            }
            if (str_contains($request->url(), '/renew')) {
                return Http::response($renewResponse);
            }
            return Http::response(['error' => true], 404);
        });
    }

    public function test_skips_renewal_whose_invoice_is_not_paid(): void
    {
        $renewal = $this->makeRenewal();
        $this->fakeApis('Unpaid', ['success' => true]);

        $this->artisan('domains:provision-paid-renewal')->assertExitCode(0);

        $renewal->refresh();
        $this->assertSame('pending_payment', $renewal->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/renew'));
    }

    public function test_completes_renewal_for_paid_invoice(): void
    {
        $renewal = $this->makeRenewal();
        $this->fakeApis('Paid', ['success' => true]);

        $this->artisan('domains:provision-paid-renewal')->assertExitCode(0);

        $renewal->refresh();
        $this->assertSame('completed', $renewal->status);

        Http::assertSent(function (ClientRequest $r) {
            if (! str_contains($r->url(), '/renew')) {
                return false;
            }
            return ($r->data()['years'] ?? null) === 2;
        });
    }

    public function test_marks_renewal_failed_when_interserver_rejects_it(): void
    {
        $renewal = $this->makeRenewal();
        $this->fakeApis('Paid', ['success' => false, 'message' => 'Domain not eligible for renewal']);

        $this->artisan('domains:provision-paid-renewal')->assertExitCode(0);

        $renewal->refresh();
        $this->assertSame('failed', $renewal->status);
        $this->assertSame('Domain not eligible for renewal', $renewal->failure_reason);
    }

    public function test_does_not_retry_a_renewal_stuck_provisioning_from_a_crashed_run(): void
    {
        $renewal = $this->makeRenewal(['status' => 'provisioning']);
        $this->fakeApis('Paid', ['success' => true]);

        $this->artisan('domains:provision-paid-renewal')->assertExitCode(0);

        $renewal->refresh();
        $this->assertSame('provisioning', $renewal->status);
        Http::assertNotSent(fn (ClientRequest $r) => str_contains($r->url(), '/renew'));
    }
}
