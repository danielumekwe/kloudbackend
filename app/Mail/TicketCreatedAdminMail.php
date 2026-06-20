<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketCreatedAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $ticketId,
        public string $ticketCode,
        public string $ticketSubject,
        public string $clientName,
        public string $clientEmail,
    ) {}

    public function build(): self
    {
        return $this->subject("New ticket {$this->ticketCode}: {$this->ticketSubject}")
            ->view('emails.ticket-created-admin');
    }
}
