<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Support\CurrencyConverter;
use App\Support\PricingConfig;

/**
 * Creates local invoices for VPS/Quick Server/SSL/Domain orders — replaces
 * WhmcsService::createInvoice() now that invoicing is fully local (Phase 3
 * of the WHMCS exit). Unlike the WHMCS wrapper it replaces, an invoice here
 * needs no WHMCS client id at all — every client can be billed.
 */
class InvoiceService
{
    public function create(Client $client, string $description, float $amountUsd, string $currencyCode): Invoice
    {
        return $this->createAt($client, $description, CurrencyConverter::convertFromUsd($amountUsd, $currencyCode), $currencyCode);
    }

    /**
     * Same as create(), but $amount is already expressed in $currencyCode — used when
     * App\Support\ProductCatalog resolved an explicit per-currency admin override rather
     * than converting a USD figure via the flat exchange rate.
     */
    public function createAt(Client $client, string $description, float $amount, string $currencyCode): Invoice
    {
        $taxRate = PricingConfig::taxRatePercent();
        $taxAmount = round($amount * $taxRate / 100, 2);

        $invoice = Invoice::create([
            'client_id'      => $client->id,
            'status'         => 'unpaid',
            'currency_code'  => $currencyCode,
            'subtotal'       => $amount,
            'tax_rate'       => $taxRate,
            'tax_amount'     => $taxAmount,
            'total'          => $amount + $taxAmount,
        ]);

        $invoice->items()->create([
            'description' => $description,
            'amount'      => $amount,
        ]);

        return $invoice;
    }
}
