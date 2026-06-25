<?php
namespace HexaGen\Core\Support;

use Stringable as StringableInterface;

class Stringable implements StringableInterface
{
    public function __construct(private string $value) {}

    public function __call(string $method, array $args): static
    {
        $result = Str::$method($this->value, ...$args);
        return new static(is_string($result) ? $result : (string) $result);
    }

    public function __toString(): string { return $this->value; }

    public function value(): string { return $this->value; }

    public function toString(): string { return $this->value; }

    public function exactly(string $value): bool { return $this->value === $value; }

    public function is(string ...$patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($pattern === $this->value || fnmatch($pattern, $this->value)) {
                return true;
            }
        }
        return false;
    }
}
