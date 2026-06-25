<?php
namespace HexaGen\Core\Storage;

interface StorageDriverInterface
{
    public function put(string $path, string $contents, array $options = []): bool;
    public function get(string $path): string;
    public function exists(string $path): bool;
    public function delete(string|array $paths): bool;
    public function move(string $from, string $to): bool;
    public function copy(string $from, string $to): bool;
    public function size(string $path): int;
    public function lastModified(string $path): int;
    public function url(string $path): string;
    public function files(string $directory = '', bool $recursive = false): array;
    public function directories(string $directory = ''): array;
    public function makeDirectory(string $path): bool;
    public function deleteDirectory(string $directory): bool;
    public function append(string $path, string $data): bool;
    public function prepend(string $path, string $data): bool;
}
