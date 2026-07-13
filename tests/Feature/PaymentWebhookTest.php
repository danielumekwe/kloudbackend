<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function makeInvoice(array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'client_id' => 7, 'status' => 'unpaid', 'currency_code' => 'NGN',
            'subtotal' => 50.00, 'total' => 50.00,
        ], $overrides));
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
        $invoice = $this->makeInvoice();

        $body = [
            'event' => 'charge.success',
            'data' => [
                'status' => 'success',
                'reference' => "kloud101-invoice-{$invoice->id}-abc",
                'amount' => 5000, // kobo
                'currency' => 'NGN',
                'metadata' => ['invoice_id' => $invoice->id, 'client_id' => 7],
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
            'invoice_id' => $invoice->id,
            'gateway' => 'paystack',
            'gateway_reference' => "kloud101-invoice-{$invoice->id}-abc",
        ]);
        $this->assertSame('paid', $invoice->refresh()->status);
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
        $invoice = $this->makeInvoice();

        $response = $this->postJson('/webhooks/flutterwave', [
            'data' => [
                'status' => 'successful',
                'tx_ref' => "kloud101-invoice-{$invoice->id}-xyz",
                'amount' => 50,
                'currency' => 'NGN',
                'meta' => ['invoice_id' => $invoice->id, 'client_id' => 7],
            ],
        ], ['verif-hash' => 'flutterwave-hash']);

        $response->assertOk();
        $this->assertDatabaseHas('payment_transactions', [
            'invoice_id' => $invoice->id,
            'gateway' => 'flutterwave',
            'gateway_reference' => "kloud101-invoice-{$invoice->id}-xyz",
        ]);
        $this->assertSame('paid', $invoice->refresh()->status);
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
        $invoice = $this->makeInvoice(['currency_code' => 'USD', 'subtotal' => 20.00, 'total' => 20.00]);

        $body = [
            'payment_status' => 'finished',
            'order_id' => (string) $invoice->id,
            'payment_id' => 'np-123',
            'price_amount' => 20,
            'price_currency' => 'usd',
        ];
        ksort($body);
        $signature = hash_hmac('sha512', json_encode($body, JSON_UNESCAPED_SLASHES), 'nowpayments-secret');

        $response = $this->postJson('/webhooks/nowpayments', $body, ['x-nowpayments-sig' => $signature]);

        $response->assertOk();
        $this->assertDatabaseHas('payment_transactions', [
            'invoice_id' => $invoice->id,
            'gateway' => 'nowpayments',
            'gateway_reference' => 'np-123',
        ]);
        $this->assertSame('paid', $invoice->refresh()->status);
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
