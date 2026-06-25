<?php
namespace HexaGen\Core;

class Config
{
    private static array $cache = [];
    private static ?array $bootstrapCache = null;
    private static bool $bootstrapChecked = false;

    public static function get(string $key, mixed $default = null): mixed
    {
        // Load the bootstrap cache once per process
        if (!static::$bootstrapChecked) {
            static::$bootstrapChecked = true;
            $cacheFile = dirname(__DIR__, 2) . '/bootstrap/cache/config.php';
            if (file_exists($cacheFile)) {
                static::$bootstrapCache = require $cacheFile;
            }
        }

        [$file, $dotPath] = array_pad(explode('.', $key, 2), 2, null);

        if (!array_key_exists($file, static::$cache)) {
            if (static::$bootstrapCache !== null) {
                static::$cache[$file] = static::$bootstrapCache[$file] ?? [];
            } else {
                $configPath = dirname(__DIR__, 2) . '/config/' . $file . '.php';
                static::$cache[$file] = file_exists($configPath) ? (require $configPath) : [];
            }
        }

        $config = static::$cache[$file];

        if ($dotPath === null) {
            return $config ?? $default;
        }

        foreach (explode('.', $dotPath) as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    public static function set(string $key, mixed $value): void
    {
        [$file, $dotPath] = array_pad(explode('.', $key, 2), 2, null);

        if (!array_key_exists($file, static::$cache)) {
            static::get($key); // warm the file cache
        }

        if ($dotPath === null) {
            static::$cache[$file] = $value;
            return;
        }

        $parts   = explode('.', $dotPath);
        $current = &static::$cache[$file];
        while (count($parts) > 1) {
            $part = array_shift($parts);
            if (!isset($current[$part]) || !is_array($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }
        $current[array_shift($parts)] = $value;
    }

    public static function has(string $key): bool
    {
        return static::get($key) !== null;
    }

    public static function all(): array
    {
        return static::$cache;
    }

    public static function flush(): void
    {
        static::$cache          = [];
        static::$bootstrapCache = null;
        static::$bootstrapChecked = false;
    }
}
