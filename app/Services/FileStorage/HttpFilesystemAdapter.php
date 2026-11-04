<?php

namespace App\Services\FileStorage;

use Closure;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use RuntimeException;

/**
 * What the adapters for HTTP based storage services have in common: they transfer whole files
 * and know no visibility.
 */
abstract class HttpFilesystemAdapter implements FilesystemAdapter
{
    /**
     * The name of the service for messages.
     */
    abstract protected function serviceName(): string;

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, (string) stream_get_contents($contents), $config);
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

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, "Visibility is not supported for {$this->serviceName()}.");
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, "Visibility is not supported for {$this->serviceName()}.");
    }

    /**
     * An access token, cached until shortly before it expires.
     *
     * @param  Closure(): Response  $requestToken
     */
    protected function rememberAccessToken(string $cacheKey, Closure $requestToken): string
    {
        $token = Cache::get($cacheKey);

        if (is_string($token)) {
            return $token;
        }

        $response = $requestToken();
        $token = $response->json('access_token');

        if (! $response->successful() || ! is_string($token)) {
            throw new RuntimeException("Anmeldung bei {$this->serviceName()} fehlgeschlagen: ".($response->json('error_description') ?? $response->status()));
        }

        Cache::put($cacheKey, $token, max(60, (int) $response->json('expires_in', 3600) - 120));

        return $token;
    }
}
