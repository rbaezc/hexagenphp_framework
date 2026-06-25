<?php
namespace HexaGen\Core\Cache\Drivers;

/** In-memory cache — data lives only for the duration of the current request. */
class ArrayCache
{
    private array $store   = [];
    private array $expires = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->store)) {
            return $default;
        }
        if (isset($this->expires[$key]) && $this->expires[$key] < time()) {
            unset($this->store[$key], $this->expires[$key]);
            return $default;
        }
        return $this->store[$key];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->store[$key] = $value;
        if ($ttl !== null) {
            $this->expires[$key] = time() + $ttl;
        }
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key], $this->expires[$key]);
        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key, '__MISS__') !== '__MISS__';
    }

    public function increment(string $key, int $by = 1): int
    {
        $new = (int)($this->get($key, 0)) + $by;
        $this->set($key, $new);
        return $new;
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
        $this->store = $this->expires = [];
        return true;
    }
}
