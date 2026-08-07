<?php

namespace App\Console\Commands;

use App\Models\ServerInstance;
use App\Models\ServerMetric;
use App\Models\VpsOrder;
use App\Services\InterServerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('servers:collect-metrics')]
#[Description('Snapshot bandwidth/resource usage for every active server instance, for the usage chart on VPS detail pages')]
class CollectServerMetrics extends Command
{
    public function handle(InterServerService $interserver): int
    {
        $instances = ServerInstance::where('status', 'active')->get();
        $collected = 0;

        foreach ($instances as $instance) {
            if (! $instance->interserver_id || $instance->orderable_type !== VpsOrder::class) {
                // Traffic usage is currently only exposed for VPS by InterServerService;
                // Dedicated Server usage metrics are skipped until that endpoint exists.
                continue;
            }

            $usage = $interserver->getTrafficUsage($instance->interserver_id);

            if ($usage['error'] ?? false) {
                continue;
            }

            // Field names below are best-effort against InterServer's documented shape;
            // adjust once a real traffic_usage response has been inspected in production.
            // Every field is nullable, so an unmatched key just records no data point
            // rather than failing the sweep.
            ServerMetric::create([
                'orderable_type'      => $instance->orderable_type,
                'orderable_id'        => $instance->orderable_id,
                'bandwidth_usage_gb'  => $usage['used_gb'] ?? $usage['bandwidth_gb'] ?? null,
                'disk_usage_gb'       => $usage['disk_gb'] ?? null,
                'cpu_usage_percent'   => $usage['cpu_percent'] ?? null,
                'ram_usage_percent'   => $usage['ram_percent'] ?? null,
            ]);

            $collected++;
        }

        $this->info("Collected metrics for {$collected} server instance(s).");

        return self::SUCCESS;
    }
}
