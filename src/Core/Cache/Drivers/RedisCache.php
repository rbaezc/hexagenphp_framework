<?php
namespace HexaGen\Core\Cache\Drivers;

use Predis\Client;

/**
 * Cache driver backed by Redis via predis/predis.
 * Configura la conexión en config/cache.php → drivers.redis
 */
class RedisCache
{
    private Client $redis;

    public function __construct(array $config = [])
    {
        $this->redis = new Client([
            'scheme'   => $config['scheme']   ?? 'tcp',
            'host'     => $config['host']     ?? (getenv('REDIS_HOST') ?: '127.0.0.1'),
            'port'     => (int)($config['port']     ?? (getenv('REDIS_PORT') ?: 6379)),
            'password' => $config['password'] ?? (getenv('REDIS_PASSWORD') ?: null),
            'database' => (int)($config['database'] ?? (getenv('REDIS_DB')       ?: 0)),
        ]);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);
        if ($value === null) {
            return $default;
        }
        return unserialize($value);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $serialized = serialize($value);
        if ($ttl !== null) {
            return $this->redis->setex($key, $ttl, $serialized) == 'OK';
        }
        return $this->redis->set($key, $serialized) == 'OK';
    }

    public function delete(string $key): bool
    {
        return $this->redis->del($key) >= 0;
    }

    public function has(string $key): bool
    {
        return (bool)$this->redis->exists($key);
    }

    public function increment(string $key, int $by = 1): int
    {
        return (int)$this->redis->incrby($key, $by);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key, '__MISS__');
        if ($cached !== '__MISS__') {
            return $cached;
        }
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function clear(): bool
    {
        $this->redis->flushdb();
        return true;
    }

    public function getClient(): Client
    {
        return $this->redis;
    }
}
