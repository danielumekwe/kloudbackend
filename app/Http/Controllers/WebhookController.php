<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\FlutterwaveService;
use App\Services\NowPaymentsService;
use App\Services\PaymentService;
use App\Services\PaystackService;
use App\Services\WhmcsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public, unauthenticated endpoints for payment gateway callbacks — CSRF-exempted
 * in bootstrap/app.php. Each method verifies the gateway's own signature scheme
 * before trusting anything in the payload; this is the authoritative path for
 * marking invoices paid (PaymentController's verify* methods are just instant
 * client-side feedback and may never fire if the client closes the tab).
 */
class WebhookController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private PaystackService $paystack,
        private FlutterwaveService $flutterwave,
        private NowPaymentsService $nowPayments,
        private WhmcsService $whmcs,
    ) {}

    public function paystack(Request $request): JsonResponse
    {
        $signature = $request->header('x-paystack-signature');

        if (! $this->paystack->verifyWebhookSignature($request->getContent(), $signature)) {
            Log::error('Paystack webhook: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = $request->input('data', []);

        if ($request->input('event') !== 'charge.success' || ($payload['status'] ?? null) !== 'success') {
            // Not a payment-success event (e.g. a different Paystack event type) — acknowledge, do nothing.
            return response()->json(['message' => 'Ignored']);
        }

        $invoiceId = $this->extractInvoiceId($payload['metadata'] ?? [], $payload['reference'] ?? '');
        if (! $invoiceId) {
            Log::error('Paystack webhook: could not determine invoice id', ['payload' => $payload]);
            return response()->json(['message' => 'Could not determine invoice'], 422);
        }

        $amount = ((float) ($payload['amount'] ?? 0)) / 100;
        $currency = $payload['currency'] ?? 'NGN';

        $result = $this->payments->recordPayment(
            invoiceId: $invoiceId,
            clientId: (int) ($payload['metadata']['client_id'] ?? 0),
            gateway: 'paystack',
            reference: $payload['reference'] ?? '',
            amount: $amount,
            currency: $currency,
            rawPayload: $payload,
        );

        return response()->json(['message' => $result['message'] ?? 'Processed'], $result['success'] ? 200 : 422);
    }

    public function flutterwave(Request $request): JsonResponse
    {
        $signature = $request->header('verif-hash');

        if (! $this->flutterwave->verifyWebhookSignature($signature)) {
            Log::error('Flutterwave webhook: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = $request->input('data', []);

        if (($payload['status'] ?? null) !== 'successful') {
            // Not a successful-charge event — acknowledge, do nothing.
            return response()->json(['message' => 'Ignored']);
        }

        $reference = (string) ($payload['tx_ref'] ?? '');
        $invoiceId = $this->extractInvoiceId($payload['meta'] ?? [], $reference);
        if (! $invoiceId) {
            Log::error('Flutterwave webhook: could not determine invoice id', ['payload' => $payload]);
            return response()->json(['message' => 'Could not determine invoice'], 422);
        }

        $amount = (float) ($payload['amount'] ?? 0);
        $currency = $payload['currency'] ?? 'NGN';

        $result = $this->payments->recordPayment(
            invoiceId: $invoiceId,
            clientId: (int) ($payload['meta']['client_id'] ?? 0),
            gateway: 'flutterwave',
            reference: $reference,
            amount: $amount,
            currency: $currency,
            rawPayload: $payload,
        );

        return response()->json(['message' => $result['message'] ?? 'Processed'], $result['success'] ? 200 : 422);
    }

    public function nowpayments(Request $request): JsonResponse
    {
        $signature = $request->header('x-nowpayments-sig');
        $payload = $request->all();

        if (! $this->nowPayments->verifyWebhookSignature($payload, $signature)) {
            Log::error('NOWPayments webhook: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // "finished" is NOWPayments' fully-settled state — confirmed-but-not-yet-finished
        // states (e.g. "confirmed", "partially_paid") are acknowledged but not recorded.
        if (($payload['payment_status'] ?? null) !== 'finished') {
            return response()->json(['message' => 'Ignored']);
        }

        $invoiceId = (int) ($payload['order_id'] ?? 0);
        if (! $invoiceId) {
            Log::error('NOWPayments webhook: could not determine invoice id', ['payload' => $payload]);
            return response()->json(['message' => 'Could not determine invoice'], 422);
        }

        $invoice = $this->whmcs->getInvoice($invoiceId);
        if (empty($invoice) || ($invoice['result'] ?? null) === 'error') {
            Log::error('NOWPayments webhook: invoice not found', ['invoice_id' => $invoiceId]);
            return response()->json(['message' => 'Invoice not found'], 422);
        }

        // invoice['userid'] is WHMCS's own client id, not necessarily our local one
        // (they only coincide for clients migrated before the WHMCS exit) — resolve
        // back to the local client via whmcs_client_id rather than storing WHMCS's
        // id directly as payment_transactions.client_id.
        $client = Client::where('whmcs_client_id', (int) ($invoice['userid'] ?? 0))->first();

        $result = $this->payments->recordPayment(
            invoiceId: $invoiceId,
            clientId: $client?->id ?? 0,
            gateway: 'nowpayments',
            reference: (string) ($payload['payment_id'] ?? ''),
            amount: (float) ($payload['price_amount'] ?? 0),
            currency: strtoupper($payload['price_currency'] ?? 'USD'),
            rawPayload: $payload,
        );

        return response()->json(['message' => $result['message'] ?? 'Processed'], $result['success'] ? 200 : 422);
    }

    /**
     * The invoice id is passed through as gateway metadata ("metadata" for Paystack,
     * "meta" for Flutterwave) when initializing the transaction client-side (see
     * resources/views/dashboard/billing/show.blade.php). The reference/tx_ref is
     * checked as a fallback in case metadata is ever stripped.
     */
    private function extractInvoiceId(array $metadata, string $reference): ?int
    {
        if (isset($metadata['invoice_id'])) {
            return (int) $metadata['invoice_id'];
        }

        if (preg_match('/^kloud101-invoice-(\d+)-/', $reference, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
