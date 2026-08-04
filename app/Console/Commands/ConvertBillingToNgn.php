<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PricingSetting;
use App\Support\PricingConfig;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * One-time conversion of billing to Naira-only (see /Users/Apple/.claude/plans/calm-twirling-harp.md).
 * Converts every non-NGN unpaid invoice, and every client's non-zero credit_balance,
 * at the exchange rate currently set in Admin > Billing Settings. Paid/cancelled
 * invoices are never touched. Safe to re-run: invoices are skipped once their
 * currency_code is NGN, and the credit_balance pass runs at most once (tracked via
 * a PricingSetting flag, since Client has no per-row currency marker to check).
 */
#[Signature('billing:convert-to-ngn {--dry-run : Report counts/amounts without writing anything}')]
#[Description('One-time conversion of unpaid invoices and wallet balances from USD to NGN')]
class ConvertBillingToNgn extends Command
{
    private const CREDIT_BALANCE_FLAG = 'credit_balance.converted_to_ngn_at';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $rate = PricingConfig::currencyRate('NGN');
        $taxRatePercent = PricingConfig::taxRatePercent();

        $this->info(($dryRun ? '[dry-run] ' : '') . "Using NGN rate: {$rate}");

        $this->convertInvoices($dryRun, $rate, $taxRatePercent);
        $this->convertWalletBalances($dryRun, $rate);

        return self::SUCCESS;
    }

    private function convertInvoices(bool $dryRun, float $rate, float $taxRatePercent): void
    {
        $invoices = Invoice::where('status', 'unpaid')->where('currency_code', '!=', 'NGN')->with('items')->get();

        foreach ($invoices as $invoice) {
            $newSubtotal = 0.0;

            foreach ($invoice->items as $item) {
                $newAmount = round((float) $item->amount * $rate, 2);
                $newSubtotal += $newAmount;

                if (! $dryRun) {
                    $item->update(['amount' => $newAmount]);
                }
            }

            $newTax = round($newSubtotal * $taxRatePercent / 100, 2);
            $newTotal = $newSubtotal + $newTax;

            if (! $dryRun) {
                $invoice->update([
                    'currency_code' => 'NGN',
                    'subtotal'      => $newSubtotal,
                    'tax_rate'      => $taxRatePercent,
                    'tax_amount'    => $newTax,
                    'total'         => $newTotal,
                ]);
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Converted {$invoices->count()} unpaid invoice(s) to NGN.");
    }

    private function convertWalletBalances(bool $dryRun, float $rate): void
    {
        if (PricingSetting::get(self::CREDIT_BALANCE_FLAG)) {
            $this->info('Wallet balances were already converted to NGN — skipping.');
            return;
        }

        $clients = Client::where('credit_balance', '>', 0)->get();

        foreach ($clients as $client) {
            $newBalance = round((float) $client->credit_balance * $rate, 2);

            if (! $dryRun) {
                $client->update(['credit_balance' => $newBalance]);
            }
        }

        if (! $dryRun) {
            PricingSetting::set(self::CREDIT_BALANCE_FLAG, now()->toIso8601String());
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Converted {$clients->count()} client wallet balance(s) to NGN.");
    }
}
