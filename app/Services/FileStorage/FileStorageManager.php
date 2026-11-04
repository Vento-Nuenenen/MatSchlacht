<?php

namespace App\Services\FileStorage;

use App\Enums\FileStorageProvider;
use App\Services\ComponentManager;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Builds the disk for uploaded files from the "Dateispeicher" settings.
 *
 * The disk "attachments" always points to the active provider, while "attachments_{provider}"
 * (e.g. "attachments_sftp") address a specific provider, e.g. to move files between providers.
 */
class FileStorageManager
{
    public const DISK = 'attachments';

    public function __construct(protected ComponentManager $components) {}

    public function activeProvider(): FileStorageProvider
    {
        return FileStorageProvider::tryFrom((string) $this->components->setting('file_storage', 'provider')) ?? FileStorageProvider::S3;
    }

    /**
     * The disk of the given provider, or of the active provider.
     */
    public function disk(?FileStorageProvider $provider = null): Filesystem
    {
        return Storage::disk($provider?->diskName() ?? self::DISK);
    }

    /**
     * The Laravel disk configuration of a provider, built from the saved settings.
     *
     * @return array<string, mixed>
     */
    public function diskConfig(FileStorageProvider $provider): array
    {
        return match ($provider) {
            FileStorageProvider::S3 => [
                'driver' => 's3',
                'key' => $this->setting('s3_key'),
                'secret' => $this->setting('s3_secret'),
                'region' => $this->setting('s3_region'),
                'bucket' => $this->setting('s3_bucket'),
                'endpoint' => $this->setting('s3_endpoint') ?: null,
                'use_path_style_endpoint' => (bool) $this->setting('s3_path_style'),
                'throw' => true,
            ],
            FileStorageProvider::Local => [
                'driver' => 'local',
                'root' => storage_path('app/private/attachments'),
                'throw' => true,
            ],
            FileStorageProvider::Sftp => [
                'driver' => 'matschlacht-sftp',
                'host' => $this->setting('sftp_host'),
                'port' => (int) ($this->setting('sftp_port') ?: 22),
                'username' => $this->setting('sftp_username'),
                'password' => $this->setting('sftp_password'),
                'private_key' => $this->setting('sftp_private_key'),
                'root' => $this->setting('sftp_root') ?: '/',
                'throw' => true,
            ],
            FileStorageProvider::Ftp => [
                'driver' => 'ftp',
                'host' => $this->setting('ftp_host'),
                'port' => (int) ($this->setting('ftp_port') ?: 21),
                'username' => $this->setting('ftp_username'),
                'password' => $this->setting('ftp_password'),
                'root' => $this->setting('ftp_root') ?: '/',
                'ssl' => (bool) $this->setting('ftp_ssl'),
                'passive' => (bool) $this->setting('ftp_passive'),
                'timeout' => 30,
                'throw' => true,
            ],
            FileStorageProvider::SharePoint => [
                'driver' => 'sharepoint',
                'tenant_id' => config('services.microsoft.tenant'),
                'client_id' => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'drive_id' => $this->setting('sharepoint_drive_id'),
                'folder' => $this->setting('sharepoint_folder') ?: '',
                'throw' => true,
            ],
            FileStorageProvider::Nextcloud => [
                'driver' => 'webdav',
                'base_url' => rtrim((string) $this->setting('nextcloud_url'), '/').'/remote.php/dav/files/'.rawurlencode((string) $this->setting('nextcloud_username')),
                'username' => $this->setting('nextcloud_username'),
                'password' => $this->setting('nextcloud_password'),
                'folder' => $this->setting('nextcloud_folder') ?: '',
                'throw' => true,
            ],
            FileStorageProvider::Dropbox => [
                'driver' => 'dropbox',
                'app_key' => $this->setting('dropbox_app_key'),
                'app_secret' => $this->setting('dropbox_app_secret'),
                'refresh_token' => $this->setting('dropbox_refresh_token'),
                'folder' => $this->setting('dropbox_folder') ?: '',
                'throw' => true,
            ],
            FileStorageProvider::GoogleDrive => [
                'driver' => 'google_drive',
                'client_id' => $this->setting('google_client_id'),
                'client_secret' => $this->setting('google_client_secret'),
                'refresh_token' => $this->setting('google_refresh_token'),
                'folder' => $this->setting('google_folder') ?: 'MatSchlacht',
                'throw' => true,
            ],
        };
    }

    /**
     * Drop the resolved disks, so that changed settings are used for the next file access.
     */
    public function forgetDisks(): void
    {
        Storage::forgetDisk([self::DISK, ...array_map(fn (FileStorageProvider $provider) => $provider->diskName(), FileStorageProvider::cases())]);
    }

    /**
     * Write, read and delete a small test file. Returns null on success, otherwise the error message.
     */
    public function testConnection(): ?string
    {
        $path = '.matschlacht-connection-test-'.Str::lower(Str::random(8)).'.txt';

        try {
            $disk = $this->disk();
            $disk->put($path, 'MatSchlacht');

            if ($disk->get($path) !== 'MatSchlacht') {
                return __('Die Testdatei konnte nicht gelesen werden.');
            }

            $disk->delete($path);

            return null;
        } catch (Throwable $exception) {
            return $exception->getMessage();
        }
    }

    protected function setting(string $key): mixed
    {
        return $this->components->setting('file_storage', $key);
    }
}
