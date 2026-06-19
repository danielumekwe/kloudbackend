<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function fakeWhmcs(array $invoice, array $addInvoicePayment = ['result' => 'success']): void
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

    public function test_records_payment_and_marks_invoice_paid_in_whmcs(): void
    {
        $this->fakeWhmcs(['result' => 'success', 'status' => 'Unpaid', 'currencycode' => 'NGN', 'balance' => '5000.00', 'total' => '5000.00']);

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: 42,
            clientId: 7,
            gateway: 'paystack',
            reference: 'ref-1',
            amount: 5000.00,
            currency: 'NGN',
        );

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('payment_transactions', [
            'whmcs_invoice_id' => 42,
            'gateway_reference' => 'ref-1',
            'status' => 'completed',
        ]);
        Http::assertSentCount(2);
    }

    public function test_invoice_already_paid_is_treated_as_duplicate_without_recording(): void
    {
        $this->fakeWhmcs(['result' => 'success', 'status' => 'Paid', 'currencycode' => 'NGN', 'balance' => '0.00']);

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: 42,
            clientId: 7,
            gateway: 'paystack',
            reference: 'ref-1',
            amount: 5000.00,
            currency: 'NGN',
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['duplicate'] ?? false);
        $this->assertDatabaseCount('payment_transactions', 0);
        Http::assertSentCount(1); // only GetInvoice — never reaches AddInvoicePayment
    }

    public function test_currency_mismatch_refuses_to_record_payment(): void
    {
        $this->fakeWhmcs(['result' => 'success', 'status' => 'Unpaid', 'currencycode' => 'NGN', 'balance' => '5000.00']);

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: 42,
            clientId: 7,
            gateway: 'paystack',
            reference: 'ref-1',
            amount: 5000.00,
            currency: 'USD',
        );

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_short_payment_is_refused(): void
    {
        $this->fakeWhmcs(['result' => 'success', 'status' => 'Unpaid', 'currencycode' => 'NGN', 'balance' => '5000.00']);

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: 42,
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
        PaymentTransaction::create([
            'client_id' => 7,
            'whmcs_invoice_id' => 42,
            'gateway' => 'paystack',
            'gateway_reference' => 'ref-1',
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'completed',
        ]);

        $this->fakeWhmcs(['result' => 'success', 'status' => 'Unpaid', 'currencycode' => 'NGN', 'balance' => '5000.00']);

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: 42,
            clientId: 7,
            gateway: 'paystack',
            reference: 'ref-1',
            amount: 5000.00,
            currency: 'NGN',
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['duplicate'] ?? false);
        $this->assertDatabaseCount('payment_transactions', 1);
        Http::assertSentCount(1); // never reaches AddInvoicePayment for the duplicate
    }

    public function test_whmcs_add_invoice_payment_failure_marks_transaction_failed(): void
    {
        $this->fakeWhmcs(
            ['result' => 'success', 'status' => 'Unpaid', 'currencycode' => 'NGN', 'balance' => '5000.00'],
            ['result' => 'error', 'message' => 'Invoice not found']
        );

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: 42,
            clientId: 7,
            gateway: 'paystack',
            reference: 'ref-1',
            amount: 5000.00,
            currency: 'NGN',
        );

        $this->assertFalse($result['success']);
        $this->assertDatabaseHas('payment_transactions', [
            'gateway_reference' => 'ref-1',
            'status' => 'failed',
        ]);
    }

    public function test_invoice_not_found_refuses_to_record_payment(): void
    {
        $this->fakeWhmcs(['result' => 'error', 'message' => 'Invoice does not exist']);

        $result = app(PaymentService::class)->recordPayment(
            invoiceId: 999,
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
