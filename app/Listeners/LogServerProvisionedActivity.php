<?php

namespace App\Listeners;

use App\Events\ServerProvisioned;
use App\Services\ActivityLogger;

class LogServerProvisionedActivity
{
    public function __construct(private ActivityLogger $logger)
    {
    }

    public function handle(ServerProvisioned $event): void
    {
        $hostname = $event->order->config['hostname'] ?? 'server';

        $this->logger->log(
            action: 'server.provisioned',
            description: "Server \"{$hostname}\" provisioned successfully",
            subject: $event->order,
        );
    }
}
