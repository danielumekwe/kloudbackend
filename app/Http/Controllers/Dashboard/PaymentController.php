<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\FlutterwaveService;
use App\Services\NowPaymentsService;
use App\Services\PaymentService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private PaystackService $paystack,
        private FlutterwaveService $flutterwave,
        private NowPaymentsService $nowPayments,
    ) {}

    /**
     * Client-triggered verification after Paystack's inline popup reports success.
     * This is a UX convenience for instant feedback — the webhook (WebhookController)
     * is still the authoritative path in case the client closes the tab too soon.
     */
    public function verifyPaystack(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['reference' => ['required', 'string']]);

        $invoice = Invoice::find($id);
        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        $verification = $this->paystack->verifyTransaction($validated['reference']);

        if (! ($verification['status'] ?? false) || ($verification['data']['status'] ?? null) !== 'success') {
            return response()->json(['success' => false, 'message' => 'Payment could not be verified.'], 422);
        }

        $data = $verification['data'];
        $amount = ((float) ($data['amount'] ?? 0)) / 100; // Paystack amounts are in the smallest currency unit (kobo)
        $currency = $data['currency'] ?? 'NGN';

        $result = $this->payments->recordPayment(
            invoiceId: $id,
            clientId: (int) session('clientId'),
            gateway: 'paystack',
            reference: $validated['reference'],
            amount: $amount,
            currency: $currency,
            rawPayload: $data,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Client-triggered verification after Flutterwave's inline checkout reports
     * success. Same UX-convenience role as verifyPaystack() — the webhook is
     * still authoritative.
     */
    public function verifyFlutterwave(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['transaction_id' => ['required', 'string']]);

        $invoice = Invoice::find($id);
        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        $verification = $this->flutterwave->verifyTransaction($validated['transaction_id']);

        if (($verification['status'] ?? null) !== 'success' || ($verification['data']['status'] ?? null) !== 'successful') {
            return response()->json(['success' => false, 'message' => 'Payment could not be verified.'], 422);
        }

        $data = $verification['data'];
        $amount = (float) ($data['amount'] ?? 0);
        $currency = $data['currency'] ?? 'NGN';
        $reference = (string) ($data['tx_ref'] ?? $validated['transaction_id']);

        $result = $this->payments->recordPayment(
            invoiceId: $id,
            clientId: (int) session('clientId'),
            gateway: 'flutterwave',
            reference: $reference,
            amount: $amount,
            currency: $currency,
            rawPayload: $data,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Creates a NOWPayments-hosted invoice and hands back its URL for the frontend
     * to open in a new tab. Unlike Paystack/Flutterwave, crypto confirmation only
     * ever arrives later via the webhook (WebhookController::nowpayments) — there is
     * no client-triggered verify step here, since the client's browser has no way
     * to know a blockchain transaction has confirmed.
     */
    public function initNowPayments(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::find($id);
        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        $result = $this->nowPayments->createInvoice([
            'price_amount'      => (float) $invoice->total,
            'price_currency'    => strtolower($invoice->currency_code),
            'order_id'          => (string) $id,
            'order_description' => "Invoice #{$id}",
            'ipn_callback_url'  => route('webhooks.nowpayments'),
            'success_url'       => route('billing.show', $id),
            'cancel_url'        => route('billing.show', $id),
        ]);

        if ($result['error'] ?? false || empty($result['invoice_url'])) {
            return response()->json(['success' => false, 'message' => 'Could not start a crypto payment. Please try again later.'], 422);
        }

        return response()->json(['success' => true, 'invoice_url' => $result['invoice_url']]);
    }
}
