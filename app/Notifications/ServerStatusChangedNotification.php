<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * In-app-only notification for the customer whose server changed state
 * (provisioned, provisioning failed, suspended, etc). Mirrors AdminAlertNotification
 * but scoped for the client-side bell dropdown.
 */
class ServerStatusChangedNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $severity = 'info',
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
