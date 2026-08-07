<?php

namespace App\Listeners;

use App\Events\ServerProvisioningFailed;
use App\Services\ActivityLogger;

class LogServerProvisioningFailedActivity
{
    public function __construct(private ActivityLogger $logger)
    {
    }

    public function handle(ServerProvisioningFailed $event): void
    {
        $this->logger->log(
            action: 'server.provisioning_failed',
            description: 'Provisioning failed: ' . $event->reason,
            subject: $event->order,
            properties: ['reason' => $event->reason],
        );
    }
}
