<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\WhmcsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * One-off repair for clients whose shadow WHMCS client creation failed at
 * registration because a WHMCS client with that email already existed (see
 * RegisterController::createShadowWhmcsClient — it logs and leaves
 * whmcs_client_id null rather than blocking signup). Looks up the
 * pre-existing WHMCS client by email and backfills whmcs_client_id on the
 * local row so invoicing (createInvoice, addInvoicePayment, etc.) can
 * resolve correctly. Must be run somewhere WHMCS's API allowlists the
 * outbound IP (e.g. the production server), not from an arbitrary dev box.
 */
#[Signature('whmcs:link-client {email} {--dry-run : Look up and report without writing anything}')]
#[Description('Backfill whmcs_client_id for a local client whose shadow WHMCS client creation failed because one already existed')]
class LinkWhmcsClient extends Command
{
    public function handle(WhmcsService $whmcs): int
    {
        $email = $this->argument('email');

        $client = Client::where('email', $email)->first();
        if (! $client) {
            $this->error("No local client found with email {$email}.");
            return self::FAILURE;
        }

        if ($client->whmcs_client_id) {
            $this->error("Client #{$client->id} already has whmcs_client_id={$client->whmcs_client_id} — nothing to do.");
            return self::FAILURE;
        }

        $result = $whmcs->getClientByEmail($email);
        if (($result['result'] ?? '') !== 'success') {
            $this->error('WHMCS lookup failed: ' . ($result['message'] ?? 'unknown error'));
            return self::FAILURE;
        }

        $whmcsClientId = (int) ($result['userid'] ?? $result['id'] ?? 0);
        if (! $whmcsClientId) {
            $this->error('WHMCS returned success but no client id was found in the response: ' . json_encode($result));
            return self::FAILURE;
        }

        $this->info("Found WHMCS client #{$whmcsClientId} for {$email} ({$result['firstname']} {$result['lastname']}).");

        if ($this->option('dry-run')) {
            $this->info('Dry run — not writing anything.');
            return self::SUCCESS;
        }

        $client->update(['whmcs_client_id' => $whmcsClientId]);
        $this->info("Linked local client #{$client->id} to whmcs_client_id={$whmcsClientId}.");

        return self::SUCCESS;
    }
}
