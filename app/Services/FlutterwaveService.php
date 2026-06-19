<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveService
{
    private string $secretKey;
    private string $webhookHash;

    public function __construct()
    {
        $this->secretKey  = config('services.flutterwave.secret_key');
        $this->webhookHash = config('services.flutterwave.webhook_hash');
    }

    public function verifyTransaction(string $transactionId): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(30)
                ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

            if (! $response->successful()) {
                Log::error('Flutterwave API HTTP error [verifyTransaction]', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['status' => 'error', 'message' => 'Flutterwave API request failed (HTTP ' . $response->status() . ')'];
            }

            $data = $response->json();

            return is_array($data) ? $data : ['status' => 'error', 'message' => 'Invalid Flutterwave API response'];
        } catch (\Exception $e) {
            Log::error('Flutterwave API exception [verifyTransaction]', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Flutterwave uses a static pre-shared hash configured in their dashboard (sent
     * back verbatim in the "verif-hash" header) rather than an HMAC of the body.
     */
    public function verifyWebhookSignature(?string $signature): bool
    {
        if (! $signature || ! $this->webhookHash) {
            return false;
        }

        return hash_equals($this->webhookHash, $signature);
    }
}
