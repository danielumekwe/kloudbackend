<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ServiceCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $serviceType,
        public string $serviceDescription,
        public int    $orderId,
    ) {}

    public function build(): self
    {
        return $this->subject('Service cancelled — ' . $this->serviceDescription)
            ->view('emails.service-cancelled');
    }
}
