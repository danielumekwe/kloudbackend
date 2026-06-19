<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $resetUrl, public string $firstName) {}

    public function build(): self
    {
        return $this->subject('Reset your Kloud101 password')
            ->view('emails.password-reset');
    }
}
