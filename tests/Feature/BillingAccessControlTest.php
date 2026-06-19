<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingAccessControlTest extends TestCase
{
    private function fakeWhmcs(int $invoiceOwnerId): void
    {
        Http::fake(function (ClientRequest $request) use ($invoiceOwnerId) {
            $action = $request->data()['action'] ?? null;

            return match ($action) {
                'GetInvoice' => Http::response([
                    'result' => 'success',
                    'invoiceid' => 42,
                    'userid' => $invoiceOwnerId,
                    'currencycode' => 'USD',
                    'date' => '2026-06-01',
                    'duedate' => '2026-06-15',
                    'status' => 'Unpaid',
                    'subtotal' => '10.00',
                    'tax' => '0.00',
                    'total' => '10.00',
                    'credit' => '0.00',
                    'paymentmethod' => 'banktransfer',
                    'items' => ['item' => []],
                    'transactions' => ['transaction' => []],
                ]),
                'GetCurrencies' => Http::response(['result' => 'error']),
                default => Http::response(['result' => 'error'], 404),
            };
        });
    }

    public function test_client_cannot_view_another_clients_invoice(): void
    {
        $this->fakeWhmcs(invoiceOwnerId: 999);

        $response = $this->withSession(['clientId' => 7])->get('/billing/42');

        $response->assertStatus(404);
    }

    public function test_client_can_view_their_own_invoice(): void
    {
        $this->fakeWhmcs(invoiceOwnerId: 7);

        $response = $this->withSession(['clientId' => 7])->get('/billing/42');

        $response->assertOk();
    }
}
