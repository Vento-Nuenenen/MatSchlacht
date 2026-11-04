<?php

namespace App\Services\FileStorage;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use RuntimeException;
use Throwable;

/**
 * A Flysystem adapter for Google Drive, built on the Drive API v3.
 *
 * Drive addresses files by ID, so paths are resolved folder by folder. The app uses the scope
 * "drive.file" and therefore only sees files it created itself, starting with its root folder
 * (default «MatSchlacht») in «Meine Ablage».
 */
class GoogleDriveAdapter extends HttpFilesystemAdapter
{
    public const API_URL = 'https://www.googleapis.com/drive/v3';

    public const UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3';

    public const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public const SCOPE = 'https://www.googleapis.com/auth/drive.file';

    public const FOLDER_MIME_TYPE = 'application/vnd.google-apps.folder';

    protected const FILE_FIELDS = 'id,name,mimeType,size,modifiedTime';

    /**
     * Resolved folder IDs by path, "" is the root folder of the app.
     *
     * @var array<string, string>
     */
    protected array $folderIds = [];

    public function __construct(
        protected string $clientId,
        #[\SensitiveParameter] protected string $clientSecret,
        #[\SensitiveParameter] protected string $refreshToken,
        protected string $rootFolder = 'MatSchlacht',
    ) {}

    public function fileExists(string $path): bool
    {
        return $this->findOrNull($path, files: true) !== null;
    }

    protected function serviceName(): string
    {
        return 'Google Drive';
    }

    public function directoryExists(string $path): bool
    {
        return trim($path, '/') === '' || $this->findOrNull($path, files: false) !== null;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $existing = $this->findOrNull($path, files: true);

            $sessionUrl = $existing !== null
                ? $this->drive()->withBody('{}', 'application/json')->patch(self::UPLOAD_URL.'/files/'.$existing['id'].'?uploadType=resumable')
                : $this->drive()->post(self::UPLOAD_URL.'/files?uploadType=resumable', [
                    'name' => basename($path),
                    'parents' => [$this->folderId(dirname($path), create: true)],
                ]);

            $uploadUrl = $this->expectSuccess($sessionUrl)->header('Location');

            if ($uploadUrl === '') {
                throw new RuntimeException('Google Drive hat keine Upload-Adresse geliefert.');
            }

            $this->expectSuccess(Http::withBody($contents, 'application/octet-stream')->timeout(120)->put($uploadUrl));
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function read(string $path): string
    {
        try {
            $file = $this->findOrNull($path, files: true) ?? throw new RuntimeException('Datei nicht gefunden.');

            return $this->expectSuccess($this->drive()->get(self::API_URL.'/files/'.$file['id'], ['alt' => 'media']))->body();
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function delete(string $path): void
    {
        try {
            $file = $this->findOrNull($path, files: true);

            if ($file !== null) {
                $this->expectSuccess($this->drive()->delete(self::API_URL.'/files/'.$file['id']), [404]);
            }
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $folder = $this->findOrNull($path, files: false);

            if ($folder !== null) {
                $this->expectSuccess($this->drive()->delete(self::API_URL.'/files/'.$folder['id']), [404]);
                $this->folderIds = [];
            }
        } catch (Throwable $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->folderId($path, create: true);
        } catch (Throwable $exception) {
            throw UnableToCreateDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->fileAttributes($path, $this->fileMetadata($path, StorageAttributes::ATTRIBUTE_MIME_TYPE));
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->fileAttributes($path, $this->fileMetadata($path, StorageAttributes::ATTRIBUTE_LAST_MODIFIED));
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->fileAttributes($path, $this->fileMetadata($path, StorageAttributes::ATTRIBUTE_FILE_SIZE));
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $folderId = $this->folderId($path, create: false);

        if ($folderId === null) {
            return;
        }

        foreach ($this->children($folderId) as $child) {
            $itemPath = ltrim(trim($path, '/').'/'.$child['name'], '/');

            if ($child['mimeType'] === self::FOLDER_MIME_TYPE) {
                yield new DirectoryAttributes($itemPath, null, $this->timestamp($child));

                if ($deep) {
                    yield from $this->listContents($itemPath, true);
                }

                continue;
            }

            yield $this->fileAttributes($itemPath, $child);
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $file = $this->findOrNull($source, files: true) ?? throw new RuntimeException('Datei nicht gefunden.');
            $this->delete($destination);
            $sourceFolderId = $this->folderId(dirname($source), create: false);
            $targetFolderId = $this->folderId(dirname($destination), create: true);

            $this->expectSuccess($this->drive()->patch(
                self::API_URL.'/files/'.$file['id'].'?'.http_build_query(['addParents' => $targetFolderId, 'removeParents' => $sourceFolderId]),
                ['name' => basename($destination)],
            ));
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $file = $this->findOrNull($source, files: true) ?? throw new RuntimeException('Datei nicht gefunden.');
            $this->delete($destination);

            $this->expectSuccess($this->drive()->post(self::API_URL.'/files/'.$file['id'].'/copy', [
                'name' => basename($destination),
                'parents' => [$this->folderId(dirname($destination), create: true)],
            ]));
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * The file or folder at the path, null if it does not exist.
     *
     * @return array{id: string, name: string, mimeType: string, size?: string, modifiedTime?: string}|null
     */
    protected function findOrNull(string $path, bool $files): ?array
    {
        try {
            $parentId = $this->folderId(dirname($path), create: false);

            return $parentId === null ? null : $this->child($parentId, basename($path), $files);
        } catch (Throwable $exception) {
            throw UnableToCheckExistence::forLocation($path, $exception);
        }
    }

    /**
     * The ID of the folder at the path below the root folder, created on demand.
     */
    protected function folderId(string $path, bool $create): ?string
    {
        $path = trim($path === '.' ? '' : $path, '/');

        if (isset($this->folderIds[$path])) {
            return $this->folderIds[$path];
        }

        if ($path === '') {
            $rootFolder = $this->child('root', $this->rootFolder, files: false);

            if ($rootFolder === null && ! $create) {
                return null;
            }

            return $this->folderIds[''] = $rootFolder['id'] ?? $this->createFolder('root', $this->rootFolder);
        }

        $parentId = $this->folderId(dirname($path), $create);

        if ($parentId === null) {
            return null;
        }

        $folder = $this->child($parentId, basename($path), files: false);

        if ($folder === null && ! $create) {
            return null;
        }

        return $this->folderIds[$path] = $folder['id'] ?? $this->createFolder($parentId, basename($path));
    }

    /**
     * @return array{id: string, name: string, mimeType: string, size?: string, modifiedTime?: string}|null
     */
    protected function child(string $parentId, string $name, bool $files): ?array
    {
        $query = sprintf(
            "name = '%s' and '%s' in parents and trashed = false and mimeType %s '%s'",
            $this->escape($name),
            $this->escape($parentId),
            $files ? '!=' : '=',
            self::FOLDER_MIME_TYPE,
        );

        /** @var array<int, array{id: string, name: string, mimeType: string, size?: string, modifiedTime?: string}> $found */
        $found = $this->expectSuccess($this->drive()->get(self::API_URL.'/files', [
            'q' => $query,
            'fields' => 'files('.self::FILE_FIELDS.')',
            'pageSize' => 1,
            'spaces' => 'drive',
        ]))->json('files', []);

        return $found[0] ?? null;
    }

    /**
     * @return iterable<int, array{id: string, name: string, mimeType: string, size?: string, modifiedTime?: string}>
     */
    protected function children(string $folderId): iterable
    {
        $pageToken = null;

        do {
            $response = $this->expectSuccess($this->drive()->get(self::API_URL.'/files', array_filter([
                'q' => sprintf("'%s' in parents and trashed = false", $this->escape($folderId)),
                'fields' => 'nextPageToken,files('.self::FILE_FIELDS.')',
                'pageSize' => 1000,
                'pageToken' => $pageToken,
            ])));

            /** @var array<int, array{id: string, name: string, mimeType: string, size?: string, modifiedTime?: string}> $files */
            $files = $response->json('files', []);

            yield from $files;

            $pageToken = $response->json('nextPageToken');
        } while (is_string($pageToken));
    }

    protected function createFolder(string $parentId, string $name): string
    {
        $id = $this->expectSuccess($this->drive()->post(self::API_URL.'/files', [
            'name' => $name,
            'mimeType' => self::FOLDER_MIME_TYPE,
            'parents' => [$parentId],
        ]))->json('id');

        if (! is_string($id)) {
            throw new RuntimeException("Ordner «{$name}» konnte nicht angelegt werden.");
        }

        return $id;
    }

    /**
     * @return array{id: string, name: string, mimeType: string, size?: string, modifiedTime?: string}
     */
    protected function fileMetadata(string $path, string $type): array
    {
        try {
            $file = $this->findOrNull($path, files: true);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, $exception->getMessage(), $exception);
        }

        return $file ?? throw UnableToRetrieveMetadata::create($path, $type, 'File not found.');
    }

    /**
     * @param  array{id: string, name: string, mimeType: string, size?: string, modifiedTime?: string}  $file
     */
    protected function fileAttributes(string $path, array $file): FileAttributes
    {
        return new FileAttributes($path, isset($file['size']) ? (int) $file['size'] : null, null, $this->timestamp($file), $file['mimeType']);
    }

    /**
     * @param  array{modifiedTime?: string}  $file
     */
    protected function timestamp(array $file): ?int
    {
        $timestamp = isset($file['modifiedTime']) ? strtotime($file['modifiedTime']) : false;

        return $timestamp === false ? null : $timestamp;
    }

    /**
     * Escape a value for a Drive search query.
     */
    protected function escape(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }

    protected function drive(): PendingRequest
    {
        return Http::withToken($this->accessToken())->acceptJson()->timeout(60);
    }

    /**
     * @param  array<int, int>  $acceptedStatuses
     */
    protected function expectSuccess(Response $response, array $acceptedStatuses = []): Response
    {
        if (! $response->successful() && ! in_array($response->status(), $acceptedStatuses, true)) {
            throw new RuntimeException('Google Drive: '.($response->json('error.message') ?? "Status {$response->status()}"));
        }

        return $response;
    }

    /**
     * A short-lived access token from the refresh token, cached until shortly before it expires.
     */
    protected function accessToken(): string
    {
        if ($this->refreshToken === '') {
            throw new RuntimeException('Google Drive ist noch nicht verbunden. Bitte unter Einstellungen «Mit Google verbinden».');
        }

        return $this->rememberAccessToken('file-storage.google-drive.token.'.md5($this->clientId.$this->refreshToken), fn (): Response => Http::asForm()
            ->post(self::TOKEN_URL, [
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
            ]));
    }
}
