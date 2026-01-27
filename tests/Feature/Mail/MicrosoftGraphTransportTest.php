<?php

namespace Tests\Feature\Mail;

use App\Mail\Transport\MicrosoftGraphTransport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class MicrosoftGraphTransportTest extends TestCase
{
    public function test_it_sends_email_via_graph_api(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response(['access_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.e30.Et9HF-uS387_m97S6S7G38_C8W_132_C9W_132']),
            'https://graph.microsoft.com/v1.0/users/*/sendMail' => Http::response([], 202),
        ]);

        $transport = new MicrosoftGraphTransport(
            'tenant-id',
            'client-id',
            'client-secret',
            'user@example.com'
        );

        $email = (new Email())
            ->from('user@example.com')
            ->to('recipient@example.com')
            ->subject('Test Subject')
            ->text('Test Body');

        $transport->send($email);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMail') &&
                $request['message']['subject'] === 'Test Subject';
        });
    }
}
