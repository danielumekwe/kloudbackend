<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $ticketId,
        public string $ticketCode,
        public string $ticketSubject,
        public string $firstName,
    ) {}

    public function build(): self
    {
        return $this->subject("Ticket {$this->ticketCode} received")
            ->view('emails.ticket-created');
    }
}
