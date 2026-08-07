<?php

namespace App\Listeners;

use App\Events\ServerActionPerformed;
use App\Models\Admin;
use App\Notifications\AdminAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Only a subset of server actions warrant paging an admin — routine
 * start/restart/reinstall noise would drown out the ones that matter.
 */
class NotifyAdminOfHighPriorityServerAction implements ShouldQueue
{
    private const HIGH_PRIORITY_ACTIONS = ['suspended', 'unsuspended', 'cancelled', 'terminated'];

    public function handle(ServerActionPerformed $event): void
    {
        if (! in_array($event->action, self::HIGH_PRIORITY_ACTIONS, true)) {
            return;
        }

        Notification::send(Admin::all(), new AdminAlertNotification(
            title: 'Server ' . $event->action,
            message: $event->description,
            severity: 'warning',
        ));
    }
}
