<?php
namespace HexaGen\Core\Cache\Drivers;

class FileCache
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/\\');
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->filePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));
        if ($data['expires'] !== null && $data['expires'] < time()) {
            unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $data = serialize([
            'value'   => $value,
            'expires' => $ttl !== null ? time() + $ttl : null,
        ]);
        return file_put_contents($this->filePath($key), $data, LOCK_EX) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->filePath($key);
        return !file_exists($file) || unlink($file);
    }

    public function has(string $key): bool
    {
        return $this->get($key, '__MISS__') !== '__MISS__';
    }

    public function increment(string $key, int $by = 1): int
    {
        $current = (int)($this->get($key, 0));
        $new     = $current + $by;
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
        foreach (glob($this->path . '/*.cache') ?: [] as $file) {
            unlink($file);
        }
        return true;
    }

    private function filePath(string $key): string
    {
        return $this->path . '/' . sha1($key) . '.cache';
    }
}
