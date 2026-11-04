<?php

namespace App\Services\FileStorage;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\PathPrefixer;
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
 * A Flysystem adapter that stores files in a SharePoint document library or a OneDrive via Microsoft Graph.
 *
 * Uses the client credentials of the Azure app that is already used for the login and the mails.
 * The app needs the application permission Sites.ReadWrite.All (or Files.ReadWrite.All).
 */
class SharePointAdapter extends HttpFilesystemAdapter
{
    public const GRAPH_URL = 'https://graph.microsoft.com/v1.0';

    /**
     * Files up to this size are uploaded in one request, larger ones through an upload session.
     */
    public const SIMPLE_UPLOAD_LIMIT = 4 * 1024 * 1024;

    /**
     * Upload session chunks must be a multiple of 320 KiB.
     */
    public const CHUNK_SIZE = 320 * 1024 * 16;

    protected PathPrefixer $prefixer;

    public function __construct(
        protected string $tenantId,
        protected string $clientId,
        #[\SensitiveParameter] protected string $clientSecret,
        protected string $driveId,
        string $folder = '',
    ) {
        $this->prefixer = new PathPrefixer(trim($folder, '/'));
    }

    protected function serviceName(): string
    {
        return 'SharePoint';
    }

    public function fileExists(string $path): bool
    {
        return $this->itemExists($path, 'file');
    }

    public function directoryExists(string $path): bool
    {
        return $this->itemExists($path, 'folder');
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            if (strlen($contents) <= self::SIMPLE_UPLOAD_LIMIT) {
                $this->graph()
                    ->withBody($contents, 'application/octet-stream')
                    ->put($this->itemUrl($path).':/content')
                    ->throw();

                return;
            }

            $this->uploadInChunks($path, $contents);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function read(string $path): string
    {
        try {
            $response = $this->graph()->get($this->itemUrl($path).':/content');
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }

        if (! $response->successful()) {
            throw UnableToReadFile::fromLocation($path, "Microsoft Graph antwortete mit Status {$response->status()}.");
        }

        return $response->body();
    }

    public function delete(string $path): void
    {
        try {
            $response = $this->graph()->delete($this->itemUrl($path));
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }

        if (! $response->successful() && $response->status() !== 404) {
            throw UnableToDeleteFile::atLocation($path, "Microsoft Graph antwortete mit Status {$response->status()}.");
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $response = $this->graph()->delete($this->itemUrl($path));
        } catch (Throwable $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getMessage(), $exception);
        }

        if (! $response->successful() && $response->status() !== 404) {
            throw UnableToDeleteDirectory::atLocation($path, "Microsoft Graph antwortete mit Status {$response->status()}.");
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $location = trim($this->prefixer->prefixPath($path), '/');
        $parent = dirname($location);

        try {
            $this->graph()
                ->post($this->locationUrl($parent === '.' ? '' : $parent).($parent === '.' ? '/children' : ':/children'), [
                    'name' => basename($location),
                    'folder' => new \stdClass,
                    '@microsoft.graph.conflictBehavior' => 'replace',
                ])
                ->throw();
        } catch (Throwable $exception) {
            throw UnableToCreateDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        $mimeType = $this->metadata($path, 'mimeType')['file']['mimeType'] ?? null;

        if (! is_string($mimeType)) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return new FileAttributes($path, null, null, null, $mimeType);
    }

    public function lastModified(string $path): FileAttributes
    {
        $lastModified = $this->metadata($path, 'lastModified')['lastModifiedDateTime'] ?? null;

        if (! is_string($lastModified) || strtotime($lastModified) === false) {
            throw UnableToRetrieveMetadata::lastModified($path);
        }

        return new FileAttributes($path, null, null, (int) strtotime($lastModified));
    }

    public function fileSize(string $path): FileAttributes
    {
        return new FileAttributes($path, (int) ($this->metadata($path, 'fileSize')['size'] ?? 0));
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $location = trim($this->prefixer->prefixPath($path), '/');
        $url = $this->locationUrl($location).($location === '' ? '/children' : ':/children');

        while ($url !== null) {
            $response = $this->graph()->get($url)->throw();

            /** @var array<int, array<string, mixed>> $children */
            $children = $response->json('value', []);

            foreach ($children as $child) {
                $itemPath = ltrim(trim($path, '/').'/'.$child['name'], '/');
                $lastModified = is_string($child['lastModifiedDateTime'] ?? null) ? (int) strtotime($child['lastModifiedDateTime']) : null;

                if (isset($child['folder'])) {
                    yield new DirectoryAttributes($itemPath, null, $lastModified);

                    if ($deep) {
                        yield from $this->listContents($itemPath, true);
                    }

                    continue;
                }

                yield new FileAttributes($itemPath, (int) ($child['size'] ?? 0), null, $lastModified, $child['file']['mimeType'] ?? null);
            }

            $nextLink = $response->json('@odata.nextLink');
            $url = is_string($nextLink) ? $nextLink : null;
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->write($destination, $this->read($source), $config);
            $this->delete($source);
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->write($destination, $this->read($source), $config);
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * Upload a large file through an upload session in chunks.
     */
    protected function uploadInChunks(string $path, string $contents): void
    {
        $uploadUrl = $this->graph()
            ->post($this->itemUrl($path).':/createUploadSession', [
                'item' => ['@microsoft.graph.conflictBehavior' => 'replace'],
            ])
            ->throw()
            ->json('uploadUrl');

        if (! is_string($uploadUrl)) {
            throw new RuntimeException('Microsoft Graph hat keine Upload-URL geliefert.');
        }

        $size = strlen($contents);

        for ($offset = 0; $offset < $size; $offset += self::CHUNK_SIZE) {
            $chunk = substr($contents, $offset, self::CHUNK_SIZE);
            $end = $offset + strlen($chunk) - 1;

            // The upload URL is pre-authenticated and must not receive the bearer token.
            Http::withHeaders(['Content-Range' => "bytes {$offset}-{$end}/{$size}"])
                ->withBody($chunk, 'application/octet-stream')
                ->put($uploadUrl)
                ->throw();
        }
    }

    protected function itemExists(string $path, string $facet): bool
    {
        try {
            $response = $this->graph()->get($this->itemUrl($path));
        } catch (Throwable $exception) {
            throw UnableToCheckExistence::forLocation($path, $exception);
        }

        if ($response->status() === 404) {
            return false;
        }

        if (! $response->successful()) {
            throw UnableToCheckExistence::forLocation($path, new RuntimeException("Microsoft Graph antwortete mit Status {$response->status()}."));
        }

        return $response->json($facet) !== null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function metadata(string $path, string $type): array
    {
        try {
            $response = $this->graph()->get($this->itemUrl($path))->throw();
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, $exception->getMessage(), $exception);
        }

        /** @var array<string, mixed> $metadata */
        $metadata = $response->json();

        return $metadata;
    }

    /**
     * The Graph URL of the item at the given path inside the configured folder.
     */
    protected function itemUrl(string $path): string
    {
        return $this->locationUrl(trim($this->prefixer->prefixPath($path), '/'));
    }

    /**
     * The Graph URL of a location relative to the drive root, e.g. ".../root:/MatSchlacht/items/a.jpg".
     */
    protected function locationUrl(string $location): string
    {
        $root = self::GRAPH_URL.'/drives/'.rawurlencode($this->driveId).'/root';

        if ($location === '') {
            return $root;
        }

        return $root.':/'.implode('/', array_map('rawurlencode', explode('/', $location)));
    }

    protected function graph(): PendingRequest
    {
        return Http::withToken($this->accessToken())->acceptJson()->timeout(60);
    }

    /**
     * An app-only access token for Microsoft Graph, cached until shortly before it expires.
     */
    protected function accessToken(): string
    {
        return $this->rememberAccessToken('file-storage.sharepoint.token.'.md5($this->tenantId.$this->clientId), fn (): Response => Http::asForm()
            ->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
            ]));
    }
}
