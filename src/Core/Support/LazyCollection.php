<?php
namespace HexaGen\Core\Support;

use Generator;
use IteratorAggregate;
use Traversable;

class LazyCollection implements IteratorAggregate
{
    private \Closure|array $source;

    public function __construct(\Closure|array $source)
    {
        $this->source = $source;
    }

    public static function make(\Closure|array|self $source = []): static
    {
        if ($source instanceof static) {
            return $source;
        }
        if (is_array($source)) {
            return new static(fn() => yield from $source);
        }
        return new static($source);
    }

    public function getIterator(): Traversable
    {
        $source = $this->source;
        if (is_array($source)) {
            yield from $source;
        } else {
            yield from $source();
        }
    }

    public function map(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    public function filter(?callable $callback = null): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                if ($callback === null ? (bool) $value : $callback($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    public function take(int $limit): static
    {
        return new static(function () use ($limit) {
            $count = 0;
            foreach ($this as $key => $value) {
                if ($count >= $limit) break;
                yield $key => $value;
                $count++;
            }
        });
    }

    public function skip(int $count): static
    {
        return new static(function () use ($count) {
            $skipped = 0;
            foreach ($this as $key => $value) {
                if ($skipped < $count) { $skipped++; continue; }
                yield $key => $value;
            }
        });
    }

    public function chunk(int $size): static
    {
        return new static(function () use ($size) {
            $chunk = [];
            foreach ($this as $value) {
                $chunk[] = $value;
                if (count($chunk) === $size) {
                    yield Collection::make($chunk);
                    $chunk = [];
                }
            }
            if (!empty($chunk)) {
                yield Collection::make($chunk);
            }
        });
    }

    public function each(callable $callback): static
    {
        foreach ($this as $key => $value) {
            if ($callback($value, $key) === false) {
                break;
            }
        }
        return $this;
    }

    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        foreach ($this as $key => $value) {
            if ($callback === null || $callback($value, $key)) {
                return $value;
            }
        }
        return $default;
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }

    public function toCollection(): Collection
    {
        return Collection::make($this->toArray());
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this as $_) { $count++; }
        return $count;
    }

    public function values(): static
    {
        return new static(function () {
            foreach ($this as $value) {
                yield $value;
            }
        });
    }

    public function pluck(string $key): static
    {
        return $this->map(fn($item) => is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null));
    }
}
