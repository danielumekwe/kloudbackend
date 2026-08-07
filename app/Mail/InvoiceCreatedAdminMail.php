<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreatedAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $clientName,
        public int $invoiceId,
        public string $description,
        public float $amount,
        public string $currency,
        public string $status,
    ) {}

    public function build(): self
    {
        return $this->subject("New invoice #{$this->invoiceId} — {$this->clientName}")
            ->view('emails.invoice-created-admin');
    }
}
