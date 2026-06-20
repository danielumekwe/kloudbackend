<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $verifyUrl,
        public string $firstName,
    ) {}

    public function build(): self
    {
        return $this->subject('Verify your Kloud101 email address')
            ->view('emails.verify-email');
    }
}
