<?php

namespace App\Services\FileStorage;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
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
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use phpseclib4\Crypt\Common\PrivateKey;
use phpseclib4\Crypt\PublicKeyLoader;
use phpseclib4\Net\SFTP;
use phpseclib4\Net\SFTP\FileType;
use RuntimeException;
use Throwable;

/**
 * A Flysystem adapter for SFTP servers built on phpseclib 4.
 *
 * league/flysystem-sftp-v3 still requires phpseclib 3, which conflicts with laravel/socialite.
 */
class SftpAdapter implements FilesystemAdapter
{
    protected ?SFTP $connection = null;

    protected PathPrefixer $prefixer;

    protected FinfoMimeTypeDetector $mimeTypeDetector;

    public function __construct(
        protected string $host,
        protected int $port,
        protected string $username,
        #[\SensitiveParameter] protected ?string $password = null,
        #[\SensitiveParameter] protected ?string $privateKey = null,
        string $root = '/',
        protected int $timeout = 15,
    ) {
        $this->prefixer = new PathPrefixer($root);
        $this->mimeTypeDetector = new FinfoMimeTypeDetector;
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->connection()->is_file($this->prefixer->prefixPath($path));
        } catch (Throwable $exception) {
            throw UnableToCheckExistence::forLocation($path, $exception);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            return $this->connection()->is_dir($this->prefixer->prefixDirectoryPath($path));
        } catch (Throwable $exception) {
            throw UnableToCheckExistence::forLocation($path, $exception);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $contents);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->upload($path, $contents);
    }

    public function read(string $path): string
    {
        try {
            return (string) $this->connection()->get($this->prefixer->prefixPath($path));
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

        try {
            $this->connection()->get($this->prefixer->prefixPath($path), $stream);
        } catch (Throwable $exception) {
            fclose($stream);

            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }

        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            if ($this->connection()->file_exists($location)) {
                $this->connection()->delete($location, false);
            }
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $location = rtrim($this->prefixer->prefixDirectoryPath($path), '/');

        try {
            if ($this->connection()->is_dir($location)) {
                $this->connection()->delete($location, true);
            }
        } catch (Throwable $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->ensureDirectory(rtrim($this->prefixer->prefixDirectoryPath($path), '/'));
        } catch (Throwable $exception) {
            throw UnableToCreateDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Visibility is not supported for SFTP storage.');
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'Visibility is not supported for SFTP storage.');
    }

    public function mimeType(string $path): FileAttributes
    {
        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromPath($path);

        if ($mimeType === null) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return new FileAttributes($path, null, null, null, $mimeType);
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            return new FileAttributes($path, null, null, (int) $this->connection()->filemtime($this->prefixer->prefixPath($path)));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::lastModified($path, $exception->getMessage(), $exception);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            return new FileAttributes($path, (int) $this->connection()->filesize($this->prefixer->prefixPath($path)));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::fileSize($path, $exception->getMessage(), $exception);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $location = rtrim($this->prefixer->prefixDirectoryPath($path), '/') ?: '/';

        foreach ($this->connection()->rawlist($location) as $name => $attributes) {
            $name = (string) $name;

            if ($name === '.' || $name === '..') {
                continue;
            }

            $itemPath = ltrim(rtrim($path, '/').'/'.$name, '/');

            if (($attributes['type'] ?? null) === FileType::DIRECTORY) {
                yield new DirectoryAttributes($itemPath, null, isset($attributes['mtime']) ? (int) $attributes['mtime'] : null);

                if ($deep) {
                    yield from $this->listContents($itemPath, true);
                }

                continue;
            }

            yield new FileAttributes(
                $itemPath,
                isset($attributes['size']) ? (int) $attributes['size'] : null,
                null,
                isset($attributes['mtime']) ? (int) $attributes['mtime'] : null,
            );
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $target = $this->prefixer->prefixPath($destination);

        try {
            $this->ensureDirectory(dirname($target));
            $this->connection()->rename($this->prefixer->prefixPath($source), $target);
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->upload($destination, $this->readStream($source));
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * @param  string|resource  $contents
     */
    protected function upload(string $path, mixed $contents): void
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $this->ensureDirectory(dirname($location));
            $this->connection()->put($location, $contents);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    protected function ensureDirectory(string $directory): void
    {
        if ($directory === '' || $directory === '.' || $directory === '/') {
            return;
        }

        if (! $this->connection()->is_dir($directory)) {
            $this->connection()->mkdir($directory, -1, true);
        }
    }

    protected function connection(): SFTP
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $connection = new SFTP($this->host, $this->port, $this->timeout);

        if (! $connection->login($this->username, $this->credential())) {
            throw new RuntimeException(__('SFTP-Anmeldung als «:username» bei :host fehlgeschlagen.', ['username' => $this->username, 'host' => $this->host]));
        }

        return $this->connection = $connection;
    }

    protected function credential(): PrivateKey|string
    {
        if (blank($this->privateKey)) {
            return (string) $this->password;
        }

        $key = PublicKeyLoader::load((string) $this->privateKey, blank($this->password) ? null : $this->password);

        if (! $key instanceof PrivateKey) {
            throw new RuntimeException(__('Der hinterlegte SFTP-Schlüssel ist kein privater Schlüssel.'));
        }

        return $key;
    }
}
