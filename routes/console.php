<?php

use App\Mail\MaintenanceDueReminder;
use App\Mail\TestMail;
use App\Services\ComponentManager;
use App\Services\MaintenanceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

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

Artisan::command('maintenance:remind', function (ComponentManager $components, MaintenanceService $maintenance) {
    if (! $components->isEnabled('maintenance') || ! $components->setting('maintenance', 'reminders')) {
        $this->info('Maintenance reminders are disabled.');

        return 0;
    }

    $dueItems = $maintenance->dueItems();

    if ($dueItems->isEmpty()) {
        $this->info('No maintenance is due.');

        return 0;
    }

    $recipients = $maintenance->reminderRecipients();
    Mail::to($recipients)->send(new MaintenanceDueReminder($dueItems));

    $this->info("Reminder for {$dueItems->count()} items sent to ".implode(', ', $recipients).'.');

    return 0;
})->purpose('Send a reminder mail about due maintenance');

Schedule::command('maintenance:remind')->weeklyOn(1, '07:00')->timezone('Europe/Zurich');
