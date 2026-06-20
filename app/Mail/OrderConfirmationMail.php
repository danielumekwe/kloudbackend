<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $description,
        public float $price,
        public string $currency,
        public int $invoiceId,
    ) {}

    public function build(): self
    {
        return $this->subject('Order received — ' . $this->description)
            ->view('emails.order-confirmation');
    }
}
