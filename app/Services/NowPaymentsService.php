<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NowPaymentsService
{
    private string $apiUrl;
    private string $apiKey;
    private string $ipnSecret;

    public function __construct()
    {
        $this->apiUrl    = rtrim(config('services.nowpayments.url'), '/');
        $this->apiKey    = config('services.nowpayments.api_key');
        $this->ipnSecret = config('services.nowpayments.ipn_secret');
    }

    /**
     * Creates a NOWPayments-hosted invoice page (handles address/QR/countdown for
     * whichever crypto the client picks there) — we never build our own raw
     * address/QR display, this is the standard, pragmatic way to integrate.
     */
    public function createInvoice(array $params): array
    {
        try {
            $response = Http::withHeaders(['x-api-key' => $this->apiKey])
                ->timeout(30)
                ->post("{$this->apiUrl}/invoice", $params);

            if (! $response->successful()) {
                Log::error('NOWPayments API HTTP error [createInvoice]', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['error' => true, 'message' => 'NOWPayments API request failed (HTTP ' . $response->status() . ')'];
            }

            $data = $response->json();

            return is_array($data) ? $data : ['error' => true, 'message' => 'Invalid NOWPayments API response'];
        } catch (\Exception $e) {
            Log::error('NOWPayments API exception [createInvoice]', ['error' => $e->getMessage()]);
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    /**
     * NOWPayments signs IPN callbacks with HMAC-SHA512 over the JSON-encoded body
     * with keys sorted alphabetically (recursively) — not the raw received body.
     */
    public function verifyWebhookSignature(array $payload, ?string $signature): bool
    {
        if (! $signature || ! $this->ipnSecret) {
            return false;
        }

        $sorted = $this->recursiveKeySort($payload);
        $expected = hash_hmac('sha512', json_encode($sorted, JSON_UNESCAPED_SLASHES), $this->ipnSecret);

        return hash_equals($expected, $signature);
    }

    private function recursiveKeySort(array $data): array
    {
        ksort($data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveKeySort($value);
            }
        }

        return $data;
    }
}
