<?php

use App\Mail\Transport\MicrosoftGraphTransport;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mime\Email;

test('it sends email via graph api', function () {
    Http::fake([
        'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response(['access_token' => 'THIS_IS_A_TEST_TOKEN_123456789']),
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
});
