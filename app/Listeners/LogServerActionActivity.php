<?php

namespace App\Listeners;

use App\Events\ServerActionPerformed;
use App\Services\ActivityLogger;

class LogServerActionActivity
{
    public function __construct(private ActivityLogger $logger)
    {
    }

    public function handle(ServerActionPerformed $event): void
    {
        $this->logger->log(
            action: $event->action,
            description: $event->description,
            subject: $event->order,
            properties: $event->properties,
            visibleToClient: $event->visibleToClient,
        );
    }
}
