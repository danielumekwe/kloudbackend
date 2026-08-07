<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Models\Invoice;
use Illuminate\Mail\Events\MessageSent;

/**
 * Captures every outgoing email app-wide with zero changes to any existing
 * Mailable or call site — Laravel tags '__laravel_mailable' onto the view
 * data for every Mailable::send() call (see Mailable::buildViewData()),
 * which is how the mailable class is recovered here.
 */
class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        $to = collect($event->message->getTo())->first()?->getAddress();

        if (! $to) {
            return;
        }

        $mailableClass = $event->data['__laravel_mailable'] ?? null;

        [$relatedType, $relatedId] = $this->resolveRelated($event->data);

        EmailLog::create([
            'to_email'       => $to,
            'subject'        => $event->message->getSubject() ?? '(no subject)',
            'mailable_class' => $mailableClass,
            'related_type'   => $relatedType,
            'related_id'     => $relatedId,
            'body_html'      => $event->message->getHtmlBody(),
            'status'         => 'sent',
        ]);
    }

    /** @return array{0: string|null, 1: int|null} */
    private function resolveRelated(array $data): array
    {
        if (isset($data['invoiceId']) && is_numeric($data['invoiceId'])) {
            return [Invoice::class, (int) $data['invoiceId']];
        }

        return [null, null];
    }
}
