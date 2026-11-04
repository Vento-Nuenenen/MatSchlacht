<?php

namespace App\Services\FileStorage;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\PathPrefixer;
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
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use RuntimeException;
use Throwable;

/**
 * A Flysystem adapter for Dropbox, built on the Dropbox HTTP API v2.
 *
 * Authenticates with the refresh token obtained through «Mit Dropbox verbinden». Single uploads
 * are limited to 150 MB by Dropbox, which is far above the upload limit of the app.
 */
class DropboxAdapter extends HttpFilesystemAdapter
{
    public const API_URL = 'https://api.dropboxapi.com/2';

    public const CONTENT_URL = 'https://content.dropboxapi.com/2';

    public const TOKEN_URL = 'https://api.dropboxapi.com/oauth2/token';

    protected PathPrefixer $prefixer;

    public function __construct(
        protected string $appKey,
        #[\SensitiveParameter] protected string $appSecret,
        #[\SensitiveParameter] protected string $refreshToken,
        string $folder = '',
    ) {
        $folder = trim($folder, '/');
        $this->prefixer = new PathPrefixer($folder === '' ? '/' : '/'.$folder);
    }

    protected function serviceName(): string
    {
        return 'Dropbox';
    }

    public function fileExists(string $path): bool
    {
        return ($this->metadataOrNull($path)['.tag'] ?? null) === 'file';
    }

    public function directoryExists(string $path): bool
    {
        return ($this->metadataOrNull($path)['.tag'] ?? null) === 'folder';
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->content('files/upload', ['path' => $this->location($path), 'mode' => 'overwrite', 'mute' => true], $contents);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function read(string $path): string
    {
        try {
            return $this->content('files/download', ['path' => $this->location($path)])->body();
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function delete(string $path): void
    {
        try {
            $this->deleteLocation($this->location($path));
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $this->deleteLocation($this->location($path));
        } catch (Throwable $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $response = $this->api('files/create_folder_v2', ['path' => $this->location($path), 'autorename' => false], allowConflict: true);

            if ($response->status() === 409 && ! str_contains((string) $response->json('error_summary'), 'path/conflict/folder')) {
                throw new RuntimeException((string) $response->json('error_summary'));
            }
        } catch (Throwable $exception) {
            throw UnableToCreateDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        $mimeType = (new FinfoMimeTypeDetector)->detectMimeTypeFromPath($path);

        if ($mimeType === null) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return new FileAttributes($path, null, null, null, $mimeType);
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
        $location = $this->location($path);
        $response = $this->api('files/list_folder', ['path' => $location === '/' ? '' : $location, 'recursive' => $deep]);

        while (true) {
            /** @var array<int, array<string, mixed>> $entries */
            $entries = $response->json('entries', []);

            foreach ($entries as $entry) {
                $itemPath = $this->prefixer->stripPrefix((string) ($entry['path_display'] ?? ''));
                $itemPath = trim($itemPath, '/');

                yield ($entry['.tag'] ?? null) === 'folder'
                    ? new DirectoryAttributes($itemPath)
                    : $this->fileAttributes($itemPath, $entry);
            }

            if (! $response->json('has_more')) {
                break;
            }

            $response = $this->api('files/list_folder/continue', ['cursor' => $response->json('cursor')]);
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->deleteLocation($this->location($destination));
            $this->api('files/move_v2', ['from_path' => $this->location($source), 'to_path' => $this->location($destination), 'autorename' => false]);
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->deleteLocation($this->location($destination));
            $this->api('files/copy_v2', ['from_path' => $this->location($source), 'to_path' => $this->location($destination), 'autorename' => false]);
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function fileAttributes(string $path, array $metadata): FileAttributes
    {
        $lastModified = is_string($metadata['server_modified'] ?? null) ? strtotime($metadata['server_modified']) : false;

        return new FileAttributes($path, isset($metadata['size']) ? (int) $metadata['size'] : null, null, $lastModified === false ? null : $lastModified);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fileMetadata(string $path, string $type): array
    {
        try {
            $metadata = $this->metadataOrNull($path);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, $exception->getMessage(), $exception);
        }

        if (($metadata['.tag'] ?? null) !== 'file') {
            throw UnableToRetrieveMetadata::create($path, $type, 'File not found.');
        }

        return $metadata;
    }

    /**
     * The metadata of a file or folder, null if nothing exists at the path.
     *
     * @return array<string, mixed>|null
     */
    protected function metadataOrNull(string $path): ?array
    {
        try {
            $response = $this->api('files/get_metadata', ['path' => $this->location($path)], allowConflict: true);
        } catch (Throwable $exception) {
            throw UnableToCheckExistence::forLocation($path, $exception);
        }

        if ($response->status() === 409) {
            return null;
        }

        /** @var array<string, mixed> $metadata */
        $metadata = $response->json();

        return $metadata;
    }

    protected function deleteLocation(string $location): void
    {
        $response = $this->api('files/delete_v2', ['path' => $location], allowConflict: true);

        if ($response->status() === 409 && ! str_contains((string) $response->json('error_summary'), 'not_found')) {
            throw new RuntimeException((string) $response->json('error_summary'));
        }
    }

    /**
     * Call an RPC endpoint. Dropbox reports missing paths and conflicts with status 409.
     *
     * @param  array<string, mixed>  $arguments
     */
    protected function api(string $endpoint, array $arguments, bool $allowConflict = false): Response
    {
        $response = Http::withToken($this->accessToken())->timeout(60)->post(self::API_URL.'/'.$endpoint, $arguments);

        if (! $response->successful() && ! ($allowConflict && $response->status() === 409)) {
            throw new RuntimeException(__('Dropbox (:endpoint): ', ['endpoint' => $endpoint]).($response->json('error_summary') ?? __('Status :status', ['status' => $response->status()])));
        }

        return $response;
    }

    /**
     * Call a content endpoint (upload or download), which takes its arguments as JSON header.
     *
     * @param  array<string, mixed>  $arguments
     */
    protected function content(string $endpoint, array $arguments, ?string $body = null): Response
    {
        // json_encode escapes non-ASCII characters, as required for HTTP headers.
        $request = Http::withToken($this->accessToken())
            ->timeout(120)
            ->withHeaders(['Dropbox-API-Arg' => (string) json_encode($arguments)])
            // Downloads must not be sent as octet-stream, Dropbox rejects that content type for them.
            ->withBody($body ?? '', $body === null ? 'text/plain; charset=utf-8' : 'application/octet-stream');

        $response = $request->post(self::CONTENT_URL.'/'.$endpoint);

        if (! $response->successful()) {
            throw new RuntimeException(__('Dropbox (:endpoint): ', ['endpoint' => $endpoint]).($response->json('error_summary') ?? __('Status :status', ['status' => $response->status()])));
        }

        return $response;
    }

    protected function location(string $path): string
    {
        $location = '/'.trim($this->prefixer->prefixPath($path), '/');

        return $location === '/' ? '/' : rtrim($location, '/');
    }

    /**
     * A short-lived access token from the refresh token, cached until shortly before it expires.
     */
    protected function accessToken(): string
    {
        if ($this->refreshToken === '') {
            throw new RuntimeException(__('Dropbox ist noch nicht verbunden. Bitte unter Einstellungen «Mit Dropbox verbinden».'));
        }

        return $this->rememberAccessToken('file-storage.dropbox.token.'.md5($this->appKey.$this->refreshToken), fn (): Response => Http::asForm()
            ->withBasicAuth($this->appKey, $this->appSecret)
            ->post(self::TOKEN_URL, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
            ]));
    }
}
