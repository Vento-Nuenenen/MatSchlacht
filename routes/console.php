<?php

use App\Enums\FileStorageProvider;
use App\Mail\MaintenanceDueReminder;
use App\Mail\TestMail;
use App\Models\Attachment;
use App\Services\ComponentManager;
use App\Services\FileStorage\FileStorageManager;
use App\Services\MaintenanceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Finder\SplFileInfo;

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

Artisan::command('lang:check {locale : Zu prüfende Sprache, z. B. fr} {--write : Fehlende Texte leer in die JSON-Datei der Sprache eintragen}', function (string $locale) {
    $keys = collect(File::allFiles(app_path()))
        ->merge(File::allFiles(resource_path('views')))
        ->flatMap(function (SplFileInfo $file) {
            preg_match_all("/__\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $file->getContents(), $matches);

            return array_map(stripslashes(...), $matches[1]);
        })
        ->merge(collect(config()->array('components'))->flatMap(fn (array $component) => [
            $component['label'],
            $component['description'],
            ...collect((array) ($component['settings'] ?? []))->flatMap(fn (array $setting) => [
                $setting['label'],
                ...array_values($setting['options'] ?? []),
            ]),
        ]))
        ->unique()
        ->sort()
        ->values();

    $path = lang_path("{$locale}.json");
    $translations = File::exists($path) ? (array) json_decode(File::get($path), true) : [];
    $missing = $keys->reject(fn (string $key) => filled($translations[$key] ?? null));

    if ($this->option('write')) {
        $texts = $translations + $missing->mapWithKeys(fn (string $key) => [$key => ''])->all();
        ksort($texts);
        File::put($path, json_encode($texts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
    }

    $missing->each(fn (string $key) => $this->line($key));
    $this->info("{$missing->count()} von {$keys->count()} Texten fehlen in {$locale}.");

    return $missing->isEmpty() ? 0 : 1;
})->purpose('List the texts that are not translated into a language yet');

Artisan::command('attachments:transfer
    {from : Provider, von dem kopiert wird (s3, local, sftp, ftp, sharepoint)}
    {to? : Ziel-Provider, Standard ist der unter Einstellungen gewählte}
    {--delete-source : Dateien nach erfolgreichem Kopieren an der Quelle löschen}', function (FileStorageManager $fileStorage, string $from, ?string $to = null) {
    $source = FileStorageProvider::tryFrom($from);
    $target = $to !== null ? FileStorageProvider::tryFrom($to) : $fileStorage->activeProvider();
    $validProviders = implode(', ', array_map(fn (FileStorageProvider $provider) => $provider->value, FileStorageProvider::cases()));

    if ($source === null || $target === null) {
        $this->error("Unbekannter Provider. Möglich sind: {$validProviders}.");

        return 1;
    }

    if ($source === $target) {
        $this->error('Quelle und Ziel sind derselbe Provider.');

        return 1;
    }

    $sourceDisk = $fileStorage->disk($source);
    $targetDisk = $fileStorage->disk($target);
    $counts = ['kopiert' => 0, 'bereits vorhanden' => 0, 'an der Quelle nicht gefunden' => 0, 'fehlgeschlagen' => 0];

    $this->info("Dateien werden von «{$source->label()}» nach «{$target->label()}» kopiert …");

    foreach (Attachment::query()->lazyById() as $attachment) {
        try {
            if (! $sourceDisk->exists($attachment->path)) {
                $counts['an der Quelle nicht gefunden']++;
                $this->warn("Nicht gefunden: {$attachment->path}");

                continue;
            }

            if ($targetDisk->exists($attachment->path)) {
                $counts['bereits vorhanden']++;
            } else {
                $stream = $sourceDisk->readStream($attachment->path);
                $targetDisk->writeStream($attachment->path, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                $counts['kopiert']++;
            }

            if ($this->option('delete-source')) {
                $sourceDisk->delete($attachment->path);
            }
        } catch (Throwable $exception) {
            $counts['fehlgeschlagen']++;
            $this->error("Fehler bei {$attachment->path}: {$exception->getMessage()}");
        }
    }

    $this->table(['Ergebnis', 'Dateien'], collect($counts)->map(fn (int $count, string $label) => [$label, $count])->values()->all());

    if ($target !== $fileStorage->activeProvider()) {
        $this->comment("Hinweis: Aktiv ist weiterhin «{$fileStorage->activeProvider()->label()}». Den Provider unter Einstellungen › Dateispeicher umstellen.");
    }

    return $counts['fehlgeschlagen'] > 0 ? 1 : 0;
})->purpose('Move all uploaded files to another file storage provider');
