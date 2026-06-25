<?php
namespace HexaGen\Core\Storage\Drivers;

use HexaGen\Core\Storage\StorageDriverInterface;

/**
 * S3 driver using the AWS SDK for PHP.
 * Requires: composer require aws/aws-sdk-php
 */
class S3Driver implements StorageDriverInterface
{
    private \Aws\S3\S3Client $client;
    private string $bucket;
    private string $prefix;
    private string $region;

    public function __construct(array $config)
    {
        if (!class_exists(\Aws\S3\S3Client::class)) {
            throw new \RuntimeException('AWS SDK not installed. Run: composer require aws/aws-sdk-php');
        }

        $this->bucket = $config['bucket'];
        $this->prefix = rtrim($config['prefix'] ?? '', '/');
        $this->region = $config['region'] ?? 'us-east-1';

        $this->client = new \Aws\S3\S3Client([
            'region'      => $this->region,
            'version'     => 'latest',
            'credentials' => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
            ],
            'endpoint'    => $config['endpoint'] ?? null,
            'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? false,
        ]);
    }

    private function key(string $path): string
    {
        return $this->prefix ? $this->prefix . '/' . ltrim($path, '/') : ltrim($path, '/');
    }

    public function put(string $path, string $contents, array $options = []): bool
    {
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key'    => $this->key($path),
            'Body'   => $contents,
            'ACL'    => $options['visibility'] === 'public' ? 'public-read' : 'private',
            'ContentType' => $options['mime'] ?? 'application/octet-stream',
        ]);
        return true;
    }

    public function get(string $path): string
    {
        $result = $this->client->getObject(['Bucket' => $this->bucket, 'Key' => $this->key($path)]);
        return (string) $result['Body'];
    }

    public function exists(string $path): bool
    {
        return $this->client->doesObjectExist($this->bucket, $this->key($path));
    }

    public function delete(string|array $paths): bool
    {
        foreach ((array) $paths as $path) {
            $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $this->key($path)]);
        }
        return true;
    }

    public function move(string $from, string $to): bool
    {
        $this->client->copyObject([
            'Bucket'     => $this->bucket,
            'CopySource' => $this->bucket . '/' . $this->key($from),
            'Key'        => $this->key($to),
        ]);
        $this->delete($from);
        return true;
    }

    public function copy(string $from, string $to): bool
    {
        $this->client->copyObject([
            'Bucket'     => $this->bucket,
            'CopySource' => $this->bucket . '/' . $this->key($from),
            'Key'        => $this->key($to),
        ]);
        return true;
    }

    public function size(string $path): int
    {
        $meta = $this->client->headObject(['Bucket' => $this->bucket, 'Key' => $this->key($path)]);
        return (int) $meta['ContentLength'];
    }

    public function lastModified(string $path): int
    {
        $meta = $this->client->headObject(['Bucket' => $this->bucket, 'Key' => $this->key($path)]);
        return $meta['LastModified']->getTimestamp();
    }

    public function url(string $path): string
    {
        return "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/" . $this->key($path);
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        $cmd = $this->client->getCommand('GetObject', ['Bucket' => $this->bucket, 'Key' => $this->key($path)]);
        $request = $this->client->createPresignedRequest($cmd, $expiration);
        return (string) $request->getUri();
    }

    public function files(string $directory = '', bool $recursive = false): array
    {
        $prefix  = $this->key($directory);
        $args    = ['Bucket' => $this->bucket, 'Prefix' => $prefix ? $prefix . '/' : ''];
        if (!$recursive) {
            $args['Delimiter'] = '/';
        }
        $results = $this->client->listObjectsV2($args);
        return array_map(
            fn($obj) => ltrim(str_replace($this->prefix . '/', '', $obj['Key']), '/'),
            $results['Contents'] ?? []
        );
    }

    public function directories(string $directory = ''): array
    {
        $prefix  = $this->key($directory);
        $results = $this->client->listObjectsV2([
            'Bucket'    => $this->bucket,
            'Prefix'    => $prefix ? $prefix . '/' : '',
            'Delimiter' => '/',
        ]);
        return array_map(
            fn($p) => rtrim(ltrim(str_replace($this->prefix . '/', '', $p['Prefix']), '/'), '/'),
            $results['CommonPrefixes'] ?? []
        );
    }

    public function makeDirectory(string $path): bool
    {
        return true; // S3 is flat; directories are virtual
    }

    public function deleteDirectory(string $directory): bool
    {
        $files = $this->files($directory, true);
        return $this->delete($files);
    }

    public function append(string $path, string $data): bool
    {
        $existing = $this->exists($path) ? $this->get($path) : '';
        return $this->put($path, $existing . $data);
    }

    public function prepend(string $path, string $data): bool
    {
        $existing = $this->exists($path) ? $this->get($path) : '';
        return $this->put($path, $data . $existing);
    }
}
