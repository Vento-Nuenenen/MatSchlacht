<?php

use App\Mail\TestMail;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {email? : Recipient, defaults to mail.from.address}', function (?string $email = null) {
    $recipient = $email ?? config('mail.from.address');

    if (! $recipient) {
        $this->error('No recipient email specified and mail.from.address is empty.');

        return 1;
    }

    try {
        Mail::to($recipient)->send(new TestMail);
    } catch (Throwable $e) {
        $this->error('Failed to send test email: '.$e->getMessage());

        return 1;
    }

    $this->info("Test email sent successfully to {$recipient} using the [".config('mail.default').'] mailer.');

    return 0;
})->purpose('Send a test mail to verify the mail configuration');
