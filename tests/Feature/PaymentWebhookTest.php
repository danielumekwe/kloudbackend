<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paystack.secret_key' => 'paystack-secret',
            'services.flutterwave.webhook_hash' => 'flutterwave-hash',
            'services.nowpayments.ipn_secret' => 'nowpayments-secret',
        ]);
    }

    private function fakeWhmcsInvoice(array $invoice, array $addInvoicePayment = ['result' => 'success']): void
    {
        Http::fake(function (ClientRequest $request) use ($invoice, $addInvoicePayment) {
            $action = $request->data()['action'] ?? null;

            return match ($action) {
                'GetInvoice' => Http::response($invoice),
                'AddInvoicePayment' => Http::response($addInvoicePayment),
                default => Http::response(['result' => 'error'], 404),
            };
        });
    }

    // -------------------------------------------------------------------------
    // Paystack
    // -------------------------------------------------------------------------

    public function test_paystack_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/webhooks/paystack', ['event' => 'charge.success'], [
            'x-paystack-signature' => 'not-a-real-signature',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_paystack_webhook_records_payment_on_valid_signature(): void
    {
        $this->fakeWhmcsInvoice(['result' => 'success', 'status' => 'Unpaid', 'currencycode' => 'NGN', 'balance' => '50.00']);

        $body = [
            'event' => 'charge.success',
            'data' => [
                'status' => 'success',
                'reference' => 'kloud101-invoice-42-abc',
                'amount' => 5000, // kobo
                'currency' => 'NGN',
                'metadata' => ['invoice_id' => 42, 'client_id' => 7],
            ],
        ];
        $payload = json_encode($body);
        $signature = hash_hmac('sha512', $payload, 'paystack-secret');

        $response = $this->call('POST', '/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_x-paystack-signature' => $signature,
        ], $payload);

        $response->assertOk();
        $this->assertDatabaseHas('payment_transactions', [
            'whmcs_invoice_id' => 42,
            'gateway' => 'paystack',
            'gateway_reference' => 'kloud101-invoice-42-abc',
        ]);
    }

    public function test_paystack_webhook_ignores_non_success_events(): void
    {
        $body = ['event' => 'charge.failed', 'data' => ['status' => 'failed']];
        $payload = json_encode($body);
        $signature = hash_hmac('sha512', $payload, 'paystack-secret');

        $response = $this->call('POST', '/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_x-paystack-signature' => $signature,
        ], $payload);

        $response->assertOk();
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    // -------------------------------------------------------------------------
    // Flutterwave
    // -------------------------------------------------------------------------

    public function test_flutterwave_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/webhooks/flutterwave', ['data' => ['status' => 'successful']], [
            'verif-hash' => 'wrong-hash',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_flutterwave_webhook_records_payment_on_valid_signature(): void
    {
        $this->fakeWhmcsInvoice(['result' => 'success', 'status' => 'Unpaid', 'currencycode' => 'NGN', 'balance' => '50.00']);

        $response = $this->postJson('/webhooks/flutterwave', [
            'data' => [
                'status' => 'successful',
                'tx_ref' => 'kloud101-invoice-42-xyz',
                'amount' => 50,
                'currency' => 'NGN',
                'meta' => ['invoice_id' => 42, 'client_id' => 7],
            ],
        ], ['verif-hash' => 'flutterwave-hash']);

        $response->assertOk();
        $this->assertDatabaseHas('payment_transactions', [
            'whmcs_invoice_id' => 42,
            'gateway' => 'flutterwave',
            'gateway_reference' => 'kloud101-invoice-42-xyz',
        ]);
    }

    // -------------------------------------------------------------------------
    // NOWPayments
    // -------------------------------------------------------------------------

    public function test_nowpayments_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/webhooks/nowpayments', ['payment_status' => 'finished'], [
            'x-nowpayments-sig' => 'wrong-signature',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_nowpayments_webhook_records_payment_when_finished(): void
    {
        $this->fakeWhmcsInvoice(['result' => 'success', 'status' => 'Unpaid', 'currencycode' => 'USD', 'balance' => '20.00', 'userid' => 7]);

        $body = [
            'payment_status' => 'finished',
            'order_id' => '42',
            'payment_id' => 'np-123',
            'price_amount' => 20,
            'price_currency' => 'usd',
        ];
        ksort($body);
        $signature = hash_hmac('sha512', json_encode($body, JSON_UNESCAPED_SLASHES), 'nowpayments-secret');

        $response = $this->postJson('/webhooks/nowpayments', $body, ['x-nowpayments-sig' => $signature]);

        $response->assertOk();
        $this->assertDatabaseHas('payment_transactions', [
            'whmcs_invoice_id' => 42,
            'gateway' => 'nowpayments',
            'gateway_reference' => 'np-123',
        ]);
    }

    public function test_nowpayments_webhook_ignores_unfinished_status(): void
    {
        $body = ['payment_status' => 'confirmed', 'order_id' => '42'];
        ksort($body);
        $signature = hash_hmac('sha512', json_encode($body, JSON_UNESCAPED_SLASHES), 'nowpayments-secret');

        $response = $this->postJson('/webhooks/nowpayments', $body, ['x-nowpayments-sig' => $signature]);

        $response->assertOk();
        $this->assertDatabaseCount('payment_transactions', 0);
    }
}
