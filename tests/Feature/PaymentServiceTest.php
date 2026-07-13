<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'client_id' => 7, 'status' => 'unpaid', 'currency_code' => 'NGN',
            'subtotal' => 5000.00, 'total' => 5000.00,
        ], $overrides));
    }

    public function test_records_payment_and_marks_invoice_paid(): void
    {
        $invoice = $this->makeInvoice();

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: $invoice->id,
            clientId: 7,
            gateway: 'paystack',
            reference: 'ref-1',
            amount: 5000.00,
            currency: 'NGN',
        );

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('payment_transactions', [
            'invoice_id' => $invoice->id,
            'gateway_reference' => 'ref-1',
            'status' => 'completed',
        ]);
        $this->assertSame('paid', $invoice->refresh()->status);
        $this->assertSame('paystack', $invoice->payment_method);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_invoice_already_paid_is_treated_as_duplicate_without_recording(): void
    {
        $invoice = $this->makeInvoice(['status' => 'paid']);

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: $invoice->id,
            clientId: 7,
            gateway: 'paystack',
            reference: 'ref-1',
            amount: 5000.00,
            currency: 'NGN',
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['duplicate'] ?? false);
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_currency_mismatch_refuses_to_record_payment(): void
    {
        $invoice = $this->makeInvoice();

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: $invoice->id,
            clientId: 7,
            gateway: 'paystack',
            reference: 'ref-1',
            amount: 5000.00,
            currency: 'USD',
        );

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertSame('unpaid', $invoice->refresh()->status);
    }

    public function test_short_payment_is_refused(): void
    {
        $invoice = $this->makeInvoice();

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: $invoice->id,
            clientId: 7,
            gateway: 'paystack',
            reference: 'ref-1',
            amount: 4000.00,
            currency: 'NGN',
        );

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_already_recorded_reference_is_treated_as_duplicate(): void
    {
        $invoice = $this->makeInvoice();

        PaymentTransaction::create([
            'client_id' => 7,
            'invoice_id' => $invoice->id,
            'gateway' => 'paystack',
            'gateway_reference' => 'ref-1',
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'completed',
        ]);

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: $invoice->id,
            clientId: 7,
            gateway: 'paystack',
            reference: 'ref-1',
            amount: 5000.00,
            currency: 'NGN',
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['duplicate'] ?? false);
        $this->assertDatabaseCount('payment_transactions', 1);
    }

    public function test_invoice_not_found_refuses_to_record_payment(): void
    {
        $result = app(PaymentService::class)->recordPayment(
            invoiceId: 999999,
            clientId: 7,
            gateway: 'paystack',
            reference: 'ref-1',
            amount: 5000.00,
            currency: 'NGN',
        );

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('payment_transactions', 0);
    }
}
