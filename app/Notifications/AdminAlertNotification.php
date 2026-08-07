<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * In-app-only (database channel) alert for admins — new orders, provisioning
 * failures, failed payments, suspensions. No mail channel here: the matching
 * email (if any) is sent separately by the dedicated Mailable for that event,
 * so this never double-sends an email.
 */
class AdminAlertNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $severity = 'info', // info, warning, critical
        public ?string $url = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'    => $this->title,
            'message'  => $this->message,
            'severity' => $this->severity,
            'url'      => $this->url,
        ];
    }
}
