<?php

namespace App\Filesystem;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Throwable;

/**
 * Flysystem adapter for Vercel Blob (public stores), using its REST API.
 *
 * Files keep their exact path (no random suffix), so the database stores the same
 * relative paths as with the local disk, and public URLs are built without API calls:
 * https://{store-id}.public.blob.vercel-storage.com/{path}
 */
class VercelBlobAdapter implements FilesystemAdapter
{
    private const API = 'https://vercel.com/api/blob';

    private string $baseUrl;

    public function __construct(private string $token, ?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?: self::publicBaseUrl($token), '/');
    }

    /** vercel_blob_rw_{storeId}_{secret} → https://{storeid}.public.blob.vercel-storage.com */
    public static function publicBaseUrl(string $token): string
    {
        $storeId = explode('_', $token)[3] ?? '';

        return 'https://'.strtolower($storeId).'.public.blob.vercel-storage.com';
    }

    public function getUrl(string $path): string
    {
        return $this->baseUrl.'/'.$this->encodePath($path);
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->token)->withHeaders(['x-api-version' => '11'])->timeout(30)->retry(2, 300, throw: false);
    }

    private function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
    }

    private function head(string $path): ?array
    {
        $response = $this->http()->get(self::API, ['url' => $this->getUrl($path)]);
        if ($response->status() === 404) {
            return null;
        }
        if (! $response->successful()) {
            throw new \RuntimeException('Vercel Blob HEAD failed: '.$response->status());
        }

        return $response->json();
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->head($path) !== null;
        } catch (Throwable $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            return $this->listPage(trim($path, '/').'/', null, 1)['blobs'] !== [];
        } catch (Throwable $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $mime = $config->get('mimetype')
            ?: (new \League\MimeTypeDetection\FinfoMimeTypeDetector)->detectMimeType($path, $contents)
            ?: 'application/octet-stream';

        try {
            $response = $this->http()
                ->withHeaders([
                    'x-content-type' => $mime,
                    'x-add-random-suffix' => '0',
                    'x-allow-overwrite' => '1',
                    'x-vercel-blob-access' => 'public',
                    'x-cache-control-max-age' => '31536000',
                ])
                ->withBody($contents, $mime)
                ->put(self::API.'/?pathname='.rawurlencode(ltrim($path, '/')));
        } catch (Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }

        if (! $response->successful()) {
            throw UnableToWriteFile::atLocation($path, 'Vercel Blob returned '.$response->status().': '.$response->body());
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, (string) stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        $response = Http::timeout(30)->get($this->getUrl($path).'?t='.time());
        if (! $response->successful()) {
            throw UnableToReadFile::fromLocation($path, 'HTTP '.$response->status());
        }

        return $response->body();
    }

    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $this->read($path));
        rewind($stream);

        return $stream;
    }

    private function deleteUrls(array $urls): void
    {
        foreach (array_chunk($urls, 500) as $chunk) {
            $response = $this->http()->post(self::API.'/delete', ['urls' => $chunk]);
            if (! $response->successful()) {
                throw new \RuntimeException('Vercel Blob delete failed: '.$response->status());
            }
        }
    }

    public function delete(string $path): void
    {
        try {
            $this->deleteUrls([$this->getUrl($path)]);
        } catch (Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $prefix = trim($path, '/').'/';
        $cursor = null;
        do {
            $page = $this->listPage($prefix, $cursor, 1000);
            if ($page['blobs'] !== []) {
                $this->deleteUrls(array_column($page['blobs'], 'url'));
            }
            $cursor = $page['cursor'] ?? null;
        } while (! empty($page['hasMore']) && $cursor);
    }

    public function createDirectory(string $path, Config $config): void
    {
        // Blob storage has no real directories.
    }

    public function setVisibility(string $path, string $visibility): void
    {
        if ($visibility !== Visibility::PUBLIC) {
            throw UnableToSetVisibility::atLocation($path, 'Vercel Blob public stores only hold public files.');
        }
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, Visibility::PUBLIC);
    }

    private function metadata(string $path): array
    {
        $meta = null;
        try {
            $meta = $this->head($path);
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::create($path, 'metadata', $e->getMessage(), $e);
        }
        if ($meta === null) {
            throw UnableToRetrieveMetadata::create($path, 'metadata', 'File not found');
        }

        return $meta;
    }

    public function mimeType(string $path): FileAttributes
    {
        return new FileAttributes($path, null, null, null, $this->metadata($path)['contentType'] ?? null);
    }

    public function lastModified(string $path): FileAttributes
    {
        $at = $this->metadata($path)['uploadedAt'] ?? null;

        return new FileAttributes($path, null, null, $at ? strtotime($at) : null);
    }

    public function fileSize(string $path): FileAttributes
    {
        return new FileAttributes($path, $this->metadata($path)['size'] ?? null);
    }

    private function listPage(string $prefix, ?string $cursor, int $limit, ?string $mode = null): array
    {
        $query = array_filter(['prefix' => ltrim($prefix, '/'), 'cursor' => $cursor, 'limit' => $limit, 'mode' => $mode]);
        $response = $this->http()->get(self::API, $query);
        if (! $response->successful()) {
            throw new \RuntimeException('Vercel Blob list failed: '.$response->status());
        }

        return $response->json() + ['blobs' => []];
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = trim($path, '/') === '' ? '' : trim($path, '/').'/';
        $cursor = null;
        do {
            $page = $this->listPage($prefix, $cursor, 1000, $deep ? null : 'folded');
            foreach ($page['blobs'] as $blob) {
                yield new FileAttributes($blob['pathname'], $blob['size'] ?? null, Visibility::PUBLIC,
                    isset($blob['uploadedAt']) ? strtotime($blob['uploadedAt']) : null);
            }
            foreach ($page['folders'] ?? [] as $folder) {
                yield new DirectoryAttributes(rtrim($folder, '/'));
            }
            $cursor = $page['cursor'] ?? null;
        } while (! empty($page['hasMore']) && $cursor);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->write($destination, $this->read($source), $config);
        } catch (Throwable $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }
}
