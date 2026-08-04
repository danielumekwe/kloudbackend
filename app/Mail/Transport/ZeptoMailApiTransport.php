<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends via ZeptoMail's HTTP API (https://api.zeptomail.com/v1.1/email) instead of
 * their SMTP relay, using Laravel's Http facade like every other external API
 * integration in this app (Paystack/Flutterwave/NOWPayments/InterServer), rather
 * than pulling in symfony/http-client just for Symfony's AbstractApiTransport.
 */
class ZeptoMailApiTransport extends AbstractTransport
{
    private const ENDPOINT = 'https://api.zeptomail.com/v1.1/email';

    public function __construct(private readonly ?string $apiToken)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $payload = [
            'from'    => $this->formatAddress($email->getFrom()[0] ?? null),
            'to'      => $this->formatRecipients($email->getTo()),
            'subject' => (string) $email->getSubject(),
        ];

        if ($cc = $this->formatRecipients($email->getCc())) {
            $payload['cc'] = $cc;
        }

        if ($bcc = $this->formatRecipients($email->getBcc())) {
            $payload['bcc'] = $bcc;
        }

        if ($html = $email->getHtmlBody()) {
            $payload['htmlbody'] = $html;
        }

        if ($text = $email->getTextBody()) {
            $payload['textbody'] = $text;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-enczapikey ' . $this->apiToken,
            'Accept'        => 'application/json',
        ])->post(self::ENDPOINT, $payload);

        if ($response->failed()) {
            Log::error('ZeptoMail API send failed', ['status' => $response->status(), 'body' => $response->body()]);

            throw new TransportException('ZeptoMail API request failed (HTTP ' . $response->status() . '): ' . $response->body());
        }
    }

    private function formatAddress(?Address $address): ?array
    {
        if (! $address) {
            return null;
        }

        $formatted = ['address' => $address->getAddress()];

        if ($address->getName() !== '') {
            $formatted['name'] = $address->getName();
        }

        return $formatted;
    }

    /**
     * @param  Address[]  $addresses
     */
    private function formatRecipients(array $addresses): array
    {
        return array_values(array_map(
            fn (Address $address) => ['email_address' => $this->formatAddress($address)],
            $addresses,
        ));
    }

    public function __toString(): string
    {
        return 'zeptomail+api://api.zeptomail.com';
    }
}
