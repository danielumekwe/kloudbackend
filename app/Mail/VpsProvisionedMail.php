<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VpsProvisionedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $hostname,
        public string $planName,
        public int    $orderId,
        public int    $invoiceId,
    ) {}

    public function build(): self
    {
        return $this->subject('Your VPS is ready — ' . $this->hostname)
            ->view('emails.vps-provisioned');
    }
}
