<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $ip,
        public string $location,
        public string $loggedInAt,
    ) {}

    public function build(): self
    {
        return $this->subject('New login to your Kloud101 account')
            ->view('emails.login-alert');
    }
}
