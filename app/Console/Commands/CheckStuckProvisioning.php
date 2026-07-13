<?php

namespace App\Console\Commands;

use App\Models\DomainOrder;
use App\Models\DomainRenewal;
use App\Models\QsOrder;
use App\Models\SslOrder;
use App\Models\VpsOrder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * The Provision* commands mark an order "provisioning" before calling InterServer
 * specifically so a crash mid-call leaves it stuck rather than retried (see
 * ProvisionPaidDomain). A stuck record is never auto-recovered — it needs a human
 * to check InterServer directly before touching its status, since the original
 * call may or may not have gone through. This command is how anyone finds out.
 */
#[Signature('provisioning:check-stuck {--minutes=15 : How long a record can sit in "provisioning" before it is considered stuck}')]
#[Description('Alert on orders/renewals stuck in "provisioning" — almost certainly a crashed provisioning run')]
class CheckStuckProvisioning extends Command
{
    /** @var array<int, class-string> */
    private const ORDER_MODELS = [
        DomainOrder::class,
        DomainRenewal::class,
        VpsOrder::class,
        QsOrder::class,
        SslOrder::class,
    ];

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = now()->subMinutes($minutes);
        $foundAny = false;

        foreach (self::ORDER_MODELS as $modelClass) {
            $stuck = $modelClass::where('status', 'provisioning')
                ->where('updated_at', '<=', $cutoff)
                ->get();

            foreach ($stuck as $record) {
                $foundAny = true;

                Log::critical("{$modelClass} #{$record->id} has been stuck in \"provisioning\" for over {$minutes} minutes — likely a crashed provisioning run. Check InterServer directly before changing its status, to avoid double-provisioning.", [
                    'model' => $modelClass,
                    'id' => $record->id,
                    'invoice_id' => $record->invoice_id,
                    'updated_at' => $record->updated_at,
                ]);

                $this->error("{$modelClass} #{$record->id} stuck in \"provisioning\" since {$record->updated_at}");
            }
        }

        if (! $foundAny) {
            $this->info('No stuck provisioning records found.');
        }

        return $foundAny ? self::FAILURE : self::SUCCESS;
    }
}
