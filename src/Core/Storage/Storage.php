<?php
namespace HexaGen\Core\Storage;

use HexaGen\Core\Storage\Drivers\LocalDriver;
use HexaGen\Core\Storage\Drivers\S3Driver;

class Storage
{
    private static array $disks = [];

    public static function disk(?string $name = null): StorageDriverInterface
    {
        $name ??= (string) \HexaGen\Core\Config::get('filesystems.default', 'local');

        if (!isset(static::$disks[$name])) {
            static::$disks[$name] = static::createDisk($name);
        }

        return static::$disks[$name];
    }

    private static function createDisk(string $name): StorageDriverInterface
    {
        $config = \HexaGen\Core\Config::get("filesystems.disks.{$name}");

        if ($config === null) {
            throw new \InvalidArgumentException("Storage disk '{$name}' is not configured.");
        }

        return match ($config['driver']) {
            'local' => new LocalDriver(
                $config['root'] ?? (dirname(__DIR__, 4) . '/storage/app'),
                $config['url']  ?? ''
            ),
            's3' => new S3Driver($config),
            default => throw new \InvalidArgumentException("Unsupported storage driver: {$config['driver']}"),
        };
    }

    public static function extend(string $name, StorageDriverInterface $driver): void
    {
        static::$disks[$name] = $driver;
    }

    // Proxy methods to default disk
    public static function put(string $path, string $contents, array $options = []): bool
    {
        return static::disk()->put($path, $contents, $options);
    }

    public static function get(string $path): string
    {
        return static::disk()->get($path);
    }

    public static function exists(string $path): bool
    {
        return static::disk()->exists($path);
    }

    public static function missing(string $path): bool
    {
        return !static::exists($path);
    }

    public static function delete(string|array $paths): bool
    {
        return static::disk()->delete($paths);
    }

    public static function move(string $from, string $to): bool
    {
        return static::disk()->move($from, $to);
    }

    public static function copy(string $from, string $to): bool
    {
        return static::disk()->copy($from, $to);
    }

    public static function size(string $path): int
    {
        return static::disk()->size($path);
    }

    public static function lastModified(string $path): int
    {
        return static::disk()->lastModified($path);
    }

    public static function url(string $path): string
    {
        return static::disk()->url($path);
    }

    public static function files(string $directory = '', bool $recursive = false): array
    {
        return static::disk()->files($directory, $recursive);
    }

    public static function directories(string $directory = ''): array
    {
        return static::disk()->directories($directory);
    }

    public static function makeDirectory(string $path): bool
    {
        return static::disk()->makeDirectory($path);
    }

    public static function deleteDirectory(string $directory): bool
    {
        return static::disk()->deleteDirectory($directory);
    }

    public static function append(string $path, string $data): bool
    {
        return static::disk()->append($path, $data);
    }

    public static function prepend(string $path, string $data): bool
    {
        return static::disk()->prepend($path, $data);
    }
}
