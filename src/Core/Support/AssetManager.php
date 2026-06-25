<?php
namespace HexaGen\Core\Support;

class AssetManager
{
    private static ?array $manifest = null;
    private static bool $manifestLoaded = false;

    public static function asset(string $path): string
    {
        $baseUrl = rtrim((string) \HexaGen\Core\Config::get('app.url', ''), '/');
        $manifest = static::loadManifest();

        // Check Vite manifest (build/manifest.json)
        if ($manifest !== null && isset($manifest[$path])) {
            $entry = $manifest[$path];
            $file  = is_array($entry) ? ($entry['file'] ?? $path) : $entry;
            return $baseUrl . '/build/' . ltrim($file, '/');
        }

        // Fallback: append file modification time as cache buster
        $publicPath = dirname(__DIR__, 3) . '/public/' . ltrim($path, '/');
        if (file_exists($publicPath)) {
            $mtime = filemtime($publicPath);
            return $baseUrl . '/' . ltrim($path, '/') . '?v=' . $mtime;
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    public static function vite(string|array $entrypoints): string
    {
        $baseUrl = rtrim((string) \HexaGen\Core\Config::get('app.url', ''), '/');
        $hotFile = dirname(__DIR__, 3) . '/public/hot';

        // HMR mode: use Vite dev server
        if (file_exists($hotFile)) {
            $devServer = rtrim(file_get_contents($hotFile), "\n");
            $tags      = [];
            foreach ((array) $entrypoints as $entry) {
                if (str_ends_with($entry, '.css')) {
                    $tags[] = "<link rel=\"stylesheet\" href=\"{$devServer}/{$entry}\">";
                } else {
                    $tags[] = "<script type=\"module\" src=\"{$devServer}/{$entry}\"></script>";
                }
            }
            return implode("\n", $tags);
        }

        // Production mode: use manifest
        $manifest = static::loadManifest();
        $tags     = [];

        foreach ((array) $entrypoints as $entry) {
            if (!isset($manifest[$entry])) {
                continue;
            }
            $chunk = $manifest[$entry];
            $file  = is_array($chunk) ? ($chunk['file'] ?? $entry) : $chunk;
            $url   = $baseUrl . '/build/' . ltrim($file, '/');

            if (str_ends_with($file, '.css')) {
                $tags[] = "<link rel=\"stylesheet\" href=\"{$url}\">";
            } else {
                $tags[] = "<script type=\"module\" src=\"{$url}\"></script>";
            }

            // CSS imports from JS entry
            if (is_array($chunk) && isset($chunk['css'])) {
                foreach ($chunk['css'] as $css) {
                    $cssUrl = $baseUrl . '/build/' . ltrim($css, '/');
                    $tags[] = "<link rel=\"stylesheet\" href=\"{$cssUrl}\">";
                }
            }
        }

        return implode("\n", $tags);
    }

    private static function loadManifest(): ?array
    {
        if (static::$manifestLoaded) {
            return static::$manifest;
        }

        static::$manifestLoaded = true;

        $paths = [
            dirname(__DIR__, 3) . '/public/build/manifest.json',
            dirname(__DIR__, 3) . '/public/mix-manifest.json',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                static::$manifest = json_decode(file_get_contents($path), true) ?? [];
                return static::$manifest;
            }
        }

        return null;
    }

    public static function flush(): void
    {
        static::$manifest       = null;
        static::$manifestLoaded = false;
    }
}
