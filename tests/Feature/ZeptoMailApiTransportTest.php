<?php

namespace Tests\Feature;

use App\Mail\Transport\ZeptoMailApiTransport;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class ZeptoMailApiTransportTest extends TestCase
{
    private function makeEmail(): Email
    {
        return (new Email())
            ->from('noreply@kloud101.com')
            ->to('client@example.com')
            ->subject('Payment received')
            ->html('<p>Hello</p>')
            ->text('Hello');
    }

    public function test_sends_via_zeptomail_api_with_expected_payload(): void
    {
        Http::fake([
            'api.zeptomail.com/*' => Http::response(['data' => [['message' => 'OK']]], 201),
        ]);

        (new ZeptoMailApiTransport('test-token-123'))->send($this->makeEmail());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.zeptomail.com/v1.1/email'
                && $request->hasHeader('Authorization', 'Zoho-enczapikey test-token-123')
                && $request['from']['address'] === 'noreply@kloud101.com'
                && $request['to'][0]['email_address']['address'] === 'client@example.com'
                && $request['subject'] === 'Payment received'
                && $request['htmlbody'] === '<p>Hello</p>'
                && $request['textbody'] === 'Hello';
        });
    }

    public function test_throws_transport_exception_on_api_failure(): void
    {
        Http::fake([
            'api.zeptomail.com/*' => Http::response(['error' => ['message' => 'Invalid token']], 401),
        ]);

        $this->expectException(TransportException::class);

        (new ZeptoMailApiTransport('bad-token'))->send($this->makeEmail());
    }
}
