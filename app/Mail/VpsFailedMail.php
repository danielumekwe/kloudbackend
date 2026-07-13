<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VpsFailedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $planName,
        public int    $orderId,
        public int    $invoiceId,
    ) {}

    public function build(): self
    {
        return $this->subject('VPS provisioning issue — action may be required')
            ->view('emails.vps-failed');
    }
}
