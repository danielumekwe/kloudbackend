<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class IpLocationService
{
    public function locate(string $ip): string
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return 'an unknown location';
        }

        try {
            $response = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");

            if (! $response->successful()) {
                return 'an unknown location';
            }

            $data = $response->json();
            $parts = array_filter([$data['city'] ?? null, $data['country_name'] ?? null]);

            return $parts ? implode(', ', $parts) : 'an unknown location';
        } catch (Throwable) {
            return 'an unknown location';
        }
    }
}
