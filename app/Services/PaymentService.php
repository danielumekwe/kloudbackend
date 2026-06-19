<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * The single choke point every gateway (Paystack/Flutterwave/NOWPayments) funnels
 * through after its own verification — both the client-triggered "verify" call and
 * the gateway's webhook call this, so idempotency and amount/currency checks live
 * in exactly one place rather than being duplicated per gateway.
 */
class PaymentService
{
    public function __construct(private WhmcsService $whmcs) {}

    public function recordPayment(
        int $invoiceId,
        int $clientId,
        string $gateway,
        string $reference,
        float $amount,
        string $currency,
        array $rawPayload = [],
    ): array {
        $invoice = $this->whmcs->getInvoice($invoiceId);

        if (empty($invoice) || ($invoice['result'] ?? null) === 'error') {
            Log::error('PaymentService: could not fetch invoice before recording payment', [
                'invoice_id' => $invoiceId, 'gateway' => $gateway, 'reference' => $reference,
            ]);
            return ['success' => false, 'message' => 'Could not verify the invoice.'];
        }

        // Already paid — most likely a duplicate webhook/client-verify race, not a real error.
        if (strtolower($invoice['status'] ?? '') === 'paid') {
            return ['success' => true, 'message' => 'Invoice already paid.', 'duplicate' => true];
        }

        $invoiceCurrency = $invoice['currencycode'] ?? 'USD';
        $amountDue = (float) ($invoice['balance'] ?? $invoice['total'] ?? 0);

        if (strcasecmp($currency, $invoiceCurrency) !== 0) {
            Log::error('PaymentService: currency mismatch, refusing to record payment', [
                'invoice_id' => $invoiceId, 'gateway' => $gateway, 'reference' => $reference,
                'paid_currency' => $currency, 'invoice_currency' => $invoiceCurrency,
            ]);
            return ['success' => false, 'message' => 'Currency mismatch — payment not recorded.'];
        }

        // Small tolerance for rounding, but never silently accept a materially short
        // payment as if it covered the full amount due.
        if ($amount < $amountDue - 0.01) {
            Log::error('PaymentService: amount paid is less than amount due, refusing to record payment', [
                'invoice_id' => $invoiceId, 'gateway' => $gateway, 'reference' => $reference,
                'amount_paid' => $amount, 'amount_due' => $amountDue,
            ]);
            return ['success' => false, 'message' => 'Paid amount does not match the amount due.'];
        }

        if (PaymentTransaction::where('gateway', $gateway)->where('gateway_reference', $reference)->exists()) {
            return ['success' => true, 'message' => 'Payment already recorded.', 'duplicate' => true];
        }

        try {
            $transaction = PaymentTransaction::create([
                'client_id'         => $clientId,
                'whmcs_invoice_id'  => $invoiceId,
                'gateway'           => $gateway,
                'gateway_reference' => $reference,
                'amount'            => $amount,
                'currency'          => $currency,
                'status'            => 'completed',
                'raw_payload'       => $rawPayload,
            ]);
        } catch (QueryException $e) {
            // Unique constraint on gateway_reference — the webhook and the client-verify
            // call raced each other; whichever got here first already recorded it.
            return ['success' => true, 'message' => 'Payment already recorded.', 'duplicate' => true];
        }

        $result = $this->whmcs->addInvoicePayment($invoiceId, $reference, $amount, $gateway);

        if (($result['result'] ?? '') !== 'success') {
            Log::error('PaymentService: WHMCS AddInvoicePayment failed after recording transaction', [
                'invoice_id' => $invoiceId, 'gateway' => $gateway, 'reference' => $reference, 'result' => $result,
            ]);
            $transaction->update(['status' => 'failed']);
            return ['success' => false, 'message' => 'Payment received but could not be recorded on the invoice. Please contact support.'];
        }

        return ['success' => true, 'message' => 'Payment recorded.'];
    }
}
