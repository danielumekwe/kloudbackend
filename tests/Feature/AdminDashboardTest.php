<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\PaymentTransaction;
use App\Models\VpsOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function fakeWhmcs(): void
    {
        Http::fake(function (ClientRequest $request) {
            $data = $request->data();
            $action = $data['action'] ?? null;
            $status = $data['status'] ?? null;

            if ($action === 'GetInvoices') {
                return match ($status) {
                    'Unpaid' => Http::response(['result' => 'success', 'totalresults' => 3, 'invoices' => ['invoice' => [
                        ['balance' => '10.00', 'currencycode' => 'USD'],
                    ]]]),
                    'Overdue' => Http::response(['result' => 'success', 'totalresults' => 1, 'invoices' => ['invoice' => [
                        ['balance' => '5.00', 'currencycode' => 'USD'],
                    ]]]),
                    'Paid' => Http::response(['result' => 'success', 'totalresults' => 12, 'invoices' => ['invoice' => []]]),
                    default => Http::response(['result' => 'success', 'totalresults' => 0, 'invoices' => ['invoice' => []]]),
                };
            }

            if ($action === 'GetTickets') {
                return Http::response(['result' => 'success', 'totalresults' => 4, 'tickets' => ['ticket' => []]]);
            }

            if ($action === 'GetCurrencies') {
                return Http::response(['result' => 'error']);
            }

            return Http::response(['result' => 'error'], 404);
        });
    }

    public function test_dashboard_requires_admin_auth(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_dashboard_shows_local_and_whmcs_stats(): void
    {
        $this->fakeWhmcs();

        VpsOrder::create([
            'client_id' => 1, 'category' => 'vps', 'status' => 'provisioned',
            'price' => 10, 'billing_cycle' => 'monthly', 'config' => [],
        ]);
        DomainOrder::create([
            'client_id' => 1, 'domain_name' => 'example', 'tld' => 'com',
            'status' => 'provisioned', 'price' => 10, 'registrant_contact' => [],
        ]);
        PaymentTransaction::create([
            'client_id' => 1, 'whmcs_invoice_id' => 1, 'gateway' => 'paystack',
            'gateway_reference' => 'ref-1', 'amount' => 100, 'currency' => 'USD', 'status' => 'completed',
        ]);

        $response = $this->withSession(['isAdmin' => true])->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('100.00');
        $response->assertSee('Active VPS');
        $response->assertViewHas('whmcsStats', function (array $stats) {
            return $stats['pending_invoices'] === 3
                && $stats['overdue_invoices'] === 1
                && $stats['paid_invoices'] === 12
                && $stats['open_tickets'] === 4
                && $stats['revenue_waiting'] === 15.0;
        });
    }
}
