<?php
namespace HexaGen\Core\Storage\Drivers;

use HexaGen\Core\Storage\StorageDriverInterface;

class LocalDriver implements StorageDriverInterface
{
    private string $root;
    private string $publicUrl;

    public function __construct(string $root, string $publicUrl = '')
    {
        $this->root      = rtrim($root, DIRECTORY_SEPARATOR);
        $this->publicUrl = rtrim($publicUrl, '/');
    }

    private function fullPath(string $path): string
    {
        return $this->root . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }

    public function put(string $path, string $contents, array $options = []): bool
    {
        $full = $this->fullPath($path);
        $dir  = dirname($full);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $visibility = $options['visibility'] ?? 'private';
        $result = file_put_contents($full, $contents, LOCK_EX) !== false;
        if ($result && $visibility === 'public') {
            chmod($full, 0644);
        }
        return $result;
    }

    public function get(string $path): string
    {
        $full = $this->fullPath($path);
        if (!file_exists($full)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        return file_get_contents($full);
    }

    public function exists(string $path): bool
    {
        return file_exists($this->fullPath($path));
    }

    public function delete(string|array $paths): bool
    {
        $success = true;
        foreach ((array) $paths as $path) {
            $full = $this->fullPath($path);
            if (file_exists($full) && !unlink($full)) {
                $success = false;
            }
        }
        return $success;
    }

    public function move(string $from, string $to): bool
    {
        return rename($this->fullPath($from), $this->fullPath($to));
    }

    public function copy(string $from, string $to): bool
    {
        $dest = $this->fullPath($to);
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }
        return copy($this->fullPath($from), $dest);
    }

    public function size(string $path): int
    {
        return (int) filesize($this->fullPath($path));
    }

    public function lastModified(string $path): int
    {
        return (int) filemtime($this->fullPath($path));
    }

    public function url(string $path): string
    {
        if ($this->publicUrl) {
            return $this->publicUrl . '/' . ltrim($path, '/');
        }
        return '/storage/' . ltrim($path, '/');
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        // For local disk, just return the regular URL (no real expiry enforcement)
        return $this->url($path);
    }

    public function files(string $directory = '', bool $recursive = false): array
    {
        $full = $this->fullPath($directory);
        if (!is_dir($full)) {
            return [];
        }
        $flags  = $recursive ? \FilesystemIterator::SKIP_DOTS : \FilesystemIterator::SKIP_DOTS;
        $iterator = $recursive
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($full, $flags))
            : new \FilesystemIterator($full, $flags);

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative = str_replace($this->root . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $files[]  = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            }
        }
        return $files;
    }

    public function directories(string $directory = ''): array
    {
        $full = $this->fullPath($directory);
        if (!is_dir($full)) {
            return [];
        }
        $dirs = [];
        foreach (new \FilesystemIterator($full, \FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry->isDir()) {
                $relative = str_replace($this->root . DIRECTORY_SEPARATOR, '', $entry->getPathname());
                $dirs[]   = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            }
        }
        return $dirs;
    }

    public function makeDirectory(string $path): bool
    {
        return mkdir($this->fullPath($path), 0755, true);
    }

    public function deleteDirectory(string $directory): bool
    {
        $full = $this->fullPath($directory);
        if (!is_dir($full)) {
            return false;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($full, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        return rmdir($full);
    }

    public function append(string $path, string $data): bool
    {
        return file_put_contents($this->fullPath($path), $data, FILE_APPEND | LOCK_EX) !== false;
    }

    public function prepend(string $path, string $data): bool
    {
        $existing = $this->exists($path) ? $this->get($path) : '';
        return $this->put($path, $data . $existing);
    }
}
