<?php

namespace App\Services\FileStorage;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
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
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use RuntimeException;
use Throwable;

/**
 * A Flysystem adapter for WebDAV servers such as Nextcloud, built on the Laravel HTTP client.
 */
class WebDavAdapter implements FilesystemAdapter
{
    protected const PROPFIND_BODY = '<?xml version="1.0" encoding="utf-8"?><d:propfind xmlns:d="DAV:"><d:prop>'
        .'<d:resourcetype/><d:getcontentlength/><d:getlastmodified/><d:getcontenttype/></d:prop></d:propfind>';

    protected PathPrefixer $prefixer;

    public function __construct(
        protected string $baseUrl,
        protected string $username,
        #[\SensitiveParameter] protected string $password,
        string $folder = '',
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->prefixer = new PathPrefixer(trim($folder, '/'));
    }

    public function fileExists(string $path): bool
    {
        return $this->stat($path) instanceof FileAttributes;
    }

    public function directoryExists(string $path): bool
    {
        return $this->stat($path) instanceof DirectoryAttributes;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->ensureDirectory(dirname($this->location($path)));
            $this->expectSuccess($this->dav()->withBody($contents, 'application/octet-stream')->put($this->url($this->location($path))));
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, (string) stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        try {
            return $this->expectSuccess($this->dav()->get($this->url($this->location($path))))->body();
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw UnableToReadFile::fromLocation($path, 'Could not open a temporary stream.');
        }

        fwrite($stream, $this->read($path));
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $this->expectSuccess($this->dav()->delete($this->url($this->location($path))), [404]);
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $this->expectSuccess($this->dav()->delete($this->url($this->location($path)).'/'), [404]);
        } catch (Throwable $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->ensureDirectory($this->location($path));
        } catch (Throwable $exception) {
            throw UnableToCreateDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Visibility is not supported for WebDAV storage.');
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'Visibility is not supported for WebDAV storage.');
    }

    public function mimeType(string $path): FileAttributes
    {
        $attributes = $this->fileStat($path, StorageAttributes::ATTRIBUTE_MIME_TYPE);

        if ($attributes->mimeType() === null) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return $attributes;
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->fileStat($path, StorageAttributes::ATTRIBUTE_LAST_MODIFIED);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->fileStat($path, StorageAttributes::ATTRIBUTE_FILE_SIZE);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $location = $this->location($path);
        $response = $this->expectSuccess($this->propfind($location, 1));

        foreach ($this->parseMultiStatus($response->body()) as $attributes) {
            $itemLocation = $this->locationFromHref($attributes['href']);

            if (trim($itemLocation, '/') === trim($location, '/')) {
                continue;
            }

            $itemPath = $this->prefixer->stripPrefix(trim($itemLocation, '/'));

            if ($attributes['isDirectory']) {
                yield new DirectoryAttributes($itemPath, null, $attributes['lastModified']);

                if ($deep) {
                    yield from $this->listContents($itemPath, true);
                }

                continue;
            }

            yield new FileAttributes($itemPath, $attributes['size'], null, $attributes['lastModified'], $attributes['mimeType']);
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->ensureDirectory(dirname($this->location($destination)));
            $this->expectSuccess($this->transfer('MOVE', $source, $destination));
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->ensureDirectory(dirname($this->location($destination)));
            $this->expectSuccess($this->transfer('COPY', $source, $destination));
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * The attributes of a file or directory, null if nothing exists at the path.
     */
    protected function stat(string $path): FileAttributes|DirectoryAttributes|null
    {
        try {
            $response = $this->propfind($this->location($path), 0);
        } catch (Throwable $exception) {
            throw UnableToCheckExistence::forLocation($path, $exception);
        }

        if ($response->status() === 404) {
            return null;
        }

        if ($response->status() !== 207) {
            throw UnableToCheckExistence::forLocation($path, new RuntimeException("WebDAV antwortete mit Status {$response->status()}."));
        }

        $attributes = $this->parseMultiStatus($response->body())[0] ?? null;

        if ($attributes === null) {
            return null;
        }

        return $attributes['isDirectory']
            ? new DirectoryAttributes($path, null, $attributes['lastModified'])
            : new FileAttributes($path, $attributes['size'], null, $attributes['lastModified'], $attributes['mimeType']);
    }

    protected function fileStat(string $path, string $type): FileAttributes
    {
        try {
            $attributes = $this->stat($path);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, $exception->getMessage(), $exception);
        }

        if (! $attributes instanceof FileAttributes) {
            throw UnableToRetrieveMetadata::create($path, $type, 'File not found.');
        }

        return $attributes;
    }

    /**
     * Create the directory and all missing parents. Existing directories answer MKCOL with 405.
     */
    protected function ensureDirectory(string $location): void
    {
        $location = trim($location, '/');

        if ($location === '' || $location === '.') {
            return;
        }

        $current = '';

        foreach (explode('/', $location) as $segment) {
            $current = ltrim($current.'/'.$segment, '/');
            $this->expectSuccess($this->dav()->send('MKCOL', $this->url($current).'/'), [405]);
        }
    }

    protected function propfind(string $location, int $depth): Response
    {
        return $this->dav()
            ->withHeaders(['Depth' => (string) $depth])
            ->withBody(self::PROPFIND_BODY, 'application/xml; charset=utf-8')
            ->send('PROPFIND', $this->url($location));
    }

    protected function transfer(string $method, string $source, string $destination): Response
    {
        return $this->dav()
            ->withHeaders(['Destination' => $this->url($this->location($destination)), 'Overwrite' => 'T'])
            ->send($method, $this->url($this->location($source)));
    }

    /**
     * Parse a PROPFIND multistatus response into the attributes of each resource.
     *
     * @return array<int, array{href: string, isDirectory: bool, size: ?int, lastModified: ?int, mimeType: ?string}>
     */
    protected function parseMultiStatus(string $xml): array
    {
        $document = new DOMDocument;

        if ($xml === '' || ! @$document->loadXML($xml)) {
            return [];
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('d', 'DAV:');
        $resources = [];

        foreach ($xpath->query('//d:response') ?: [] as $response) {
            if (! $response instanceof DOMElement) {
                continue;
            }

            $size = $this->propertyValue($xpath, $response, 'getcontentlength');
            $lastModified = $this->propertyValue($xpath, $response, 'getlastmodified');
            $collections = $xpath->query('.//d:propstat/d:prop/d:resourcetype/d:collection', $response);

            $resources[] = [
                'href' => (string) $xpath->evaluate('string(d:href)', $response),
                'isDirectory' => $collections !== false && $collections->length > 0,
                'size' => $size !== null ? (int) $size : null,
                'lastModified' => $lastModified !== null && strtotime($lastModified) !== false ? (int) strtotime($lastModified) : null,
                'mimeType' => $this->propertyValue($xpath, $response, 'getcontenttype'),
            ];
        }

        return $resources;
    }

    protected function propertyValue(DOMXPath $xpath, DOMElement $response, string $property): ?string
    {
        $nodes = $xpath->query(".//d:propstat/d:prop/d:{$property}", $response);
        $node = $nodes !== false ? $nodes->item(0) : null;

        return $node instanceof DOMElement && $node->textContent !== '' ? $node->textContent : null;
    }

    /**
     * The location (path below the base URL) of a resource from its href.
     */
    protected function locationFromHref(string $href): string
    {
        $basePath = rtrim((string) parse_url($this->baseUrl, PHP_URL_PATH), '/');
        $hrefPath = rawurldecode((string) parse_url($href, PHP_URL_PATH));

        return str_starts_with($hrefPath, rawurldecode($basePath)) ? substr($hrefPath, strlen(rawurldecode($basePath))) : $hrefPath;
    }

    protected function location(string $path): string
    {
        return trim($this->prefixer->prefixPath($path), '/');
    }

    protected function url(string $location): string
    {
        $location = trim($location, '/');

        return $this->baseUrl.($location === '' ? '' : '/'.implode('/', array_map('rawurlencode', explode('/', $location))));
    }

    protected function dav(): PendingRequest
    {
        return Http::withBasicAuth($this->username, $this->password)->timeout(60);
    }

    /**
     * @param  array<int, int>  $acceptedStatuses  Non-success statuses that are fine, e.g. 404 when deleting.
     */
    protected function expectSuccess(Response $response, array $acceptedStatuses = []): Response
    {
        if (! $response->successful() && ! in_array($response->status(), $acceptedStatuses, true)) {
            throw new RuntimeException("WebDAV antwortete mit Status {$response->status()}.");
        }

        return $response;
    }
}
