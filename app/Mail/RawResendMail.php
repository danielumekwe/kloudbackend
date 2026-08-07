<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Re-sends the exact stored HTML from an EmailLog row — used by the admin
 * "Resend" action so it never needs to reconstruct the original Mailable's
 * constructor arguments.
 */
class RawResendMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $emailSubject, public string $html)
    {
    }

    public function build(): self
    {
        return $this->subject('[Resent] ' . $this->emailSubject)->html($this->html);
    }
}
