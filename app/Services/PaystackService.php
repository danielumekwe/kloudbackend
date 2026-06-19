<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
    }

    public function verifyTransaction(string $reference): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(30)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            if (! $response->successful()) {
                Log::error('Paystack API HTTP error [verifyTransaction]', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['status' => false, 'message' => 'Paystack API request failed (HTTP ' . $response->status() . ')'];
            }

            $data = $response->json();

            return is_array($data) ? $data : ['status' => false, 'message' => 'Invalid Paystack API response'];
        } catch (\Exception $e) {
            Log::error('Paystack API exception [verifyTransaction]', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Paystack signs webhook bodies with HMAC-SHA512 using the secret key — never
     * trust a webhook payload without this check first.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha512', $rawBody, $this->secretKey);

        return hash_equals($expected, $signature);
    }
}
