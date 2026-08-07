<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public int $invoiceId,
        public float $amount,
        public string $currency,
        public string $dueDate,
    ) {}

    public function build(): self
    {
        return $this->subject("Reminder: Invoice #{$this->invoiceId} is due soon")
            ->view('emails.payment-reminder');
    }
}
