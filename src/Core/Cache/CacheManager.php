<?php
namespace HexaGen\Core\Cache;

use HexaGen\Core\Cache\Drivers\ArrayCache;
use HexaGen\Core\Cache\Drivers\FileCache;
use HexaGen\Core\Cache\Drivers\RedisCache;
use HexaGen\Core\Config;

/**
 * PSR-16 inspired cache manager.
 * Access via the cache() global helper or CacheManager::driver().
 */
class CacheManager
{
    private static array $resolved = [];

    public static function driver(?string $name = null): FileCache|ArrayCache|RedisCache
    {
        $name ??= Config::get('cache.default', 'file');

        if (isset(self::$resolved[$name])) {
            return self::$resolved[$name];
        }

        $config = Config::get("cache.drivers.$name", []);
        $driver = $config['driver'] ?? $name;

        self::$resolved[$name] = match ($driver) {
            'array' => new ArrayCache(),
            'redis' => new RedisCache($config),
            default => new FileCache(
                $config['path'] ?? (dirname(__DIR__, 3) . '/var/cache/data')
            ),
        };

        return self::$resolved[$name];
    }

    // Convenience proxy methods on the default driver
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::driver()->get($key, $default);
    }

    public static function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl ??= Config::get('cache.ttl', 300);
        return self::driver()->set($key, $value, $ttl);
    }

    public static function delete(string $key): bool
    {
        return self::driver()->delete($key);
    }

    public static function has(string $key): bool
    {
        return self::driver()->has($key);
    }

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        return self::driver()->remember($key, $ttl, $callback);
    }

    public static function increment(string $key, int $by = 1): int
    {
        return self::driver()->increment($key, $by);
    }

    public static function clear(): bool
    {
        return self::driver()->clear();
    }
}
