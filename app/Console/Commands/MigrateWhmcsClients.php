<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time historical import from the real WHMCS database (Phase 2 of the
 * WHMCS exit — see /Users/Apple/.claude/plans/hidden-baking-gem.md). Reads
 * from the read-only `whmcs` connection (config/database.php), never writes
 * to it. Safe to re-run — every insert is keyed by the original WHMCS id via
 * forceFill (see upsertById()), so this can be run as a dry-run-then-final-
 * delta pass right before cutover.
 *
 * Backfills whmcs_client_id = id for every imported row — for these migrated
 * clients the local id IS the WHMCS id (same record), unlike clients created
 * after this phase ships, where WHMCS assigns its shadow client a different,
 * unrelated id (see App\Models\Client and RegisterController).
 *
 * Column names below assume a standard WHMCS schema (tblclients) and have NOT
 * been verified against this specific WHMCS install — run `DESCRIBE
 * tblclients;` against the real DB first and adjust if anything differs
 * before the real cutover run. The password column is imported byte-for-byte;
 * Client::checkPassword() supports bcrypt or plain unsalted MD5 — any other
 * legacy hash scheme simply fails to verify post-migration, recoverable via
 * the (now fully local) forgot-password flow.
 */
#[Signature('whmcs:migrate-clients {--dry-run : Report counts without writing anything}')]
#[Description('One-time import of clients from the real WHMCS database')]
class MigrateWhmcsClients extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = DB::connection('whmcs')->table('tblclients')->get([
            'id', 'email', 'password', 'firstname', 'lastname',
            'phonenumber', 'address1', 'city', 'state', 'postcode', 'country', 'credit',
        ]);

        $this->info("Clients found in WHMCS: {$rows->count()}");

        if ($dryRun) {
            return self::SUCCESS;
        }

        foreach ($rows as $row) {
            $model = Client::find($row->id) ?? new Client();

            $model->forceFill([
                'id' => $row->id,
                'email' => $row->email,
                'password' => $row->password,
                'firstname' => $row->firstname,
                'lastname' => $row->lastname,
                'phonenumber' => $row->phonenumber,
                'address1' => $row->address1,
                'city' => $row->city,
                'state' => $row->state,
                'postcode' => $row->postcode,
                'country' => $row->country,
                'credit_balance' => $row->credit ?? 0,
                'whmcs_client_id' => $row->id,
            ])->save();
        }

        return self::SUCCESS;
    }
}
