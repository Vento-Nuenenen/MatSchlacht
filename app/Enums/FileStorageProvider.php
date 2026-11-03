<?php

namespace App\Enums;

/**
 * Where uploaded files (item images, receipts, maintenance photos) are stored.
 */
enum FileStorageProvider: string
{
    case S3 = 's3';
    case Local = 'local';
    case Sftp = 'sftp';
    case Ftp = 'ftp';
    case SharePoint = 'sharepoint';
    case Nextcloud = 'nextcloud';
    case Dropbox = 'dropbox';
    case GoogleDrive = 'google_drive';

    public function label(): string
    {
        return match ($this) {
            self::S3 => 'Amazon S3 / S3-kompatibel',
            self::Local => 'Lokaler Server-Speicher',
            self::Sftp => 'SFTP',
            self::Ftp => 'FTP',
            self::SharePoint => 'SharePoint / OneDrive',
            self::Nextcloud => 'Nextcloud',
            self::Dropbox => 'Dropbox',
            self::GoogleDrive => 'Google Drive',
        };
    }

    /**
     * Providers that are connected through an OAuth login instead of entering credentials.
     */
    public function usesOAuth(): bool
    {
        return in_array($this, [self::Dropbox, self::GoogleDrive], true);
    }

    /**
     * The name of the Laravel disk that stores the files of this provider.
     */
    public function diskName(): string
    {
        return 'attachments_'.$this->value;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $provider) => [$provider->value => $provider->label()])->all();
    }
}
