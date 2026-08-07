<?php

namespace App\Listeners;

use App\Events\ServerProvisioningFailed;
use App\Models\Admin;
use App\Notifications\AdminAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class NotifyAdminOfProvisioningFailure implements ShouldQueue
{
    public function handle(ServerProvisioningFailed $event): void
    {
        $order = $event->order;
        $hostname = $order->config['hostname'] ?? "order #{$order->id}";

        Notification::send(Admin::all(), new AdminAlertNotification(
            title: 'Provisioning failed',
            message: "Provisioning failed for \"{$hostname}\": {$event->reason}",
            severity: 'critical',
            url: route('admin.services.index', ['type' => $order instanceof \App\Models\DedicatedServerOrder ? 'dedicated' : 'vps']),
        ));
    }
}
