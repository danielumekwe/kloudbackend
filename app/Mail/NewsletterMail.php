<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        string $subject,
        public string $body,
    ) {
        // Assigned to the inherited (untyped) Mailable::$subject rather than
        // promoted, since promoting it here would redeclare that property
        // with a type and fatal: "Type of ...::$subject must not be defined".
        $this->subject = $subject;
    }

    public function build(): self
    {
        return $this->subject($this->subject)
            ->view('emails.newsletter');
    }
}
