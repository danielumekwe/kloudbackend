<?php

namespace App\Listeners;

use App\Events\ServerProvisioned;
use App\Models\Client;
use App\Notifications\ServerStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyClientOfServerProvisioned implements ShouldQueue
{
    public function handle(ServerProvisioned $event): void
    {
        $client = Client::find($event->order->client_id);
        $hostname = $event->order->config['hostname'] ?? 'Your server';

        $client?->notify(new ServerStatusChangedNotification(
            title: 'Server ready',
            message: "{$hostname} has been provisioned and is now active.",
            severity: 'info',
            url: route('vps.index'),
        ));
    }
}
