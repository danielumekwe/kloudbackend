<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoicePaidMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public int $invoiceId,
        public float $amount,
        public string $currency,
        public string $paidAt,
    ) {}

    public function build(): self
    {
        return $this->subject('Payment received — Invoice #' . $this->invoiceId)
            ->view('emails.invoice-paid');
    }
}
