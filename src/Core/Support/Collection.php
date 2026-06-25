<?php
namespace HexaGen\Core\Support;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    use Traits\Macroable;

    protected array $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public static function make(mixed $items = []): static
    {
        if ($items instanceof static) {
            return new static($items->all());
        }
        if (is_array($items)) {
            return new static($items);
        }
        if ($items instanceof \Traversable) {
            return new static(iterator_to_array($items));
        }
        return new static([$items]);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->items));
    }

    public function filter(?callable $callback = null): static
    {
        if ($callback === null) {
            return new static(array_filter($this->items));
        }
        return new static(array_filter($this->items, $callback));
    }

    public function reject(callable $callback): static
    {
        return $this->filter(fn($item) => !$callback($item));
    }

    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }

    public function flatMap(callable $callback): static
    {
        $result = [];
        foreach ($this->items as $key => $item) {
            $mapped = $callback($item, $key);
            if (is_array($mapped) || $mapped instanceof self) {
                foreach ((is_array($mapped) ? $mapped : $mapped->all()) as $v) {
                    $result[] = $v;
                }
            } else {
                $result[] = $mapped;
            }
        }
        return new static($result);
    }

    public function reduce(callable $callback, mixed $carry = null): mixed
    {
        return array_reduce($this->items, $callback, $carry);
    }

    public function pluck(string $key, ?string $indexBy = null): static
    {
        $result = [];
        foreach ($this->items as $item) {
            $value = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
            if ($indexBy !== null) {
                $index = is_array($item) ? ($item[$indexBy] ?? null) : ($item->$indexBy ?? null);
                $result[$index] = $value;
            } else {
                $result[] = $value;
            }
        }
        return new static($result);
    }

    public function chunk(int $size): static
    {
        if ($size <= 0) {
            return new static();
        }
        $chunks = array_chunk($this->items, $size);
        return new static(array_map(fn($chunk) => new static($chunk), $chunks));
    }

    public function groupBy(string|callable $key): static
    {
        $result = [];
        foreach ($this->items as $item) {
            $groupKey = is_callable($key)
                ? $key($item)
                : (is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null));
            $result[$groupKey][] = $item;
        }
        return new static(array_map(fn($group) => new static($group), $result));
    }

    public function sortBy(string|callable $key, bool $descending = false): static
    {
        $items = $this->items;
        usort($items, function ($a, $b) use ($key) {
            $aVal = is_callable($key) ? $key($a) : (is_array($a) ? ($a[$key] ?? null) : ($a->$key ?? null));
            $bVal = is_callable($key) ? $key($b) : (is_array($b) ? ($b[$key] ?? null) : ($b->$key ?? null));
            return $aVal <=> $bVal;
        });
        if ($descending) {
            $items = array_reverse($items);
        }
        return new static($items);
    }

    public function sortByDesc(string|callable $key): static
    {
        return $this->sortBy($key, true);
    }

    public function sort(?callable $callback = null): static
    {
        $items = $this->items;
        $callback ? usort($items, $callback) : sort($items);
        return new static($items);
    }

    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return empty($this->items) ? $default : reset($this->items);
        }
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }
        return $default;
    }

    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return empty($this->items) ? $default : end($this->items);
        }
        $result = $default;
        foreach ($this->items as $item) {
            if ($callback($item)) {
                $result = $item;
            }
        }
        return $result;
    }

    public function contains(mixed $keyOrCallback, mixed $value = null): bool
    {
        if ($value !== null) {
            return $this->contains(fn($item) =>
                (is_array($item) ? ($item[$keyOrCallback] ?? null) : ($item->$keyOrCallback ?? null)) === $value
            );
        }
        if (is_callable($keyOrCallback)) {
            foreach ($this->items as $item) {
                if ($keyOrCallback($item)) {
                    return true;
                }
            }
            return false;
        }
        return in_array($keyOrCallback, $this->items, true);
    }

    public function unique(?string $key = null): static
    {
        if ($key === null) {
            return new static(array_values(array_unique($this->items)));
        }
        $seen = [];
        $result = [];
        foreach ($this->items as $item) {
            $val = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
            if (!in_array($val, $seen, true)) {
                $seen[] = $val;
                $result[] = $item;
            }
        }
        return new static($result);
    }

    public function values(): static
    {
        return new static(array_values($this->items));
    }

    public function keys(): static
    {
        return new static(array_keys($this->items));
    }

    public function flip(): static
    {
        return new static(array_flip($this->items));
    }

    public function merge(array|self $items): static
    {
        return new static(array_merge($this->items, is_array($items) ? $items : $items->all()));
    }

    public function diff(array|self $items): static
    {
        return new static(array_diff($this->items, is_array($items) ? $items : $items->all()));
    }

    public function intersect(array|self $items): static
    {
        return new static(array_intersect($this->items, is_array($items) ? $items : $items->all()));
    }

    public function take(int $limit): static
    {
        if ($limit < 0) {
            return new static(array_slice($this->items, $limit));
        }
        return new static(array_slice($this->items, 0, $limit));
    }

    public function skip(int $count): static
    {
        return new static(array_slice($this->items, $count));
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length));
    }

    public function flatten(int $depth = PHP_INT_MAX): static
    {
        $result = [];
        $flatten = function (array $items, int $currentDepth) use (&$flatten, &$result, $depth) {
            foreach ($items as $item) {
                if (is_array($item) && $currentDepth < $depth) {
                    $flatten($item, $currentDepth + 1);
                } elseif ($item instanceof self && $currentDepth < $depth) {
                    $flatten($item->all(), $currentDepth + 1);
                } else {
                    $result[] = $item;
                }
            }
        };
        $flatten($this->items, 0);
        return new static($result);
    }

    public function sum(string|callable|null $key = null): int|float
    {
        if ($key === null) {
            return array_sum($this->items);
        }
        return $this->pluck(is_string($key) ? $key : '__computed__')
            ->reduce(fn($carry, $item) => $carry + (is_callable($key) ? $key($item) : $item), 0);
    }

    public function avg(string|callable|null $key = null): float|int
    {
        $count = $this->count();
        if ($count === 0) {
            return 0;
        }
        $values = $key !== null ? $this->map(is_callable($key) ? $key : fn($i) => is_array($i) ? ($i[$key] ?? 0) : ($i->$key ?? 0)) : $this;
        return $values->sum() / $count;
    }

    public function min(string|callable|null $key = null): mixed
    {
        $values = $key !== null ? $this->map(is_callable($key) ? $key : fn($i) => is_array($i) ? ($i[$key] ?? null) : ($i->$key ?? null)) : $this;
        return min($values->all());
    }

    public function max(string|callable|null $key = null): mixed
    {
        $values = $key !== null ? $this->map(is_callable($key) ? $key : fn($i) => is_array($i) ? ($i[$key] ?? null) : ($i->$key ?? null)) : $this;
        return max($values->all());
    }

    public function get(int|string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function put(int|string $key, mixed $value): static
    {
        $items = $this->items;
        $items[$key] = $value;
        return new static($items);
    }

    public function prepend(mixed $value, int|string|null $key = null): static
    {
        $items = $this->items;
        if ($key !== null) {
            $items = [$key => $value] + $items;
        } else {
            array_unshift($items, $value);
        }
        return new static($items);
    }

    public function push(mixed ...$values): static
    {
        $items = $this->items;
        foreach ($values as $value) {
            $items[] = $value;
        }
        return new static($items);
    }

    public function pop(): mixed
    {
        $items = $this->items;
        return array_pop($items);
    }

    public function shift(): mixed
    {
        $items = $this->items;
        return array_shift($items);
    }

    public function reverse(): static
    {
        return new static(array_reverse($this->items, true));
    }

    public function shuffle(): static
    {
        $items = $this->items;
        shuffle($items);
        return new static($items);
    }

    public function random(int $number = 1): mixed
    {
        if ($number === 1) {
            return $this->items[array_rand($this->items)];
        }
        $keys = (array) array_rand($this->items, $number);
        return new static(array_intersect_key($this->items, array_flip($keys)));
    }

    public function when(bool $condition, callable $callback, ?callable $default = null): static
    {
        if ($condition) {
            return $callback($this) ?? $this;
        }
        if ($default !== null) {
            return $default($this) ?? $this;
        }
        return $this;
    }

    public function unless(bool $condition, callable $callback, ?callable $default = null): static
    {
        return $this->when(!$condition, $callback, $default);
    }

    public function tap(callable $callback): static
    {
        $callback($this);
        return $this;
    }

    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    public function toArray(): array
    {
        return array_map(fn($item) => $item instanceof self ? $item->toArray() : $item, $this->items);
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->jsonSerialize(), $flags);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function implode(string $glue, ?string $key = null): string
    {
        $items = $key !== null ? $this->pluck($key)->all() : $this->items;
        return implode($glue, $items);
    }

    public function join(string $glue, string $finalGlue = ''): string
    {
        if ($finalGlue === '') {
            return $this->implode($glue);
        }
        $count = $this->count();
        if ($count === 0) return '';
        if ($count === 1) return (string) $this->first();
        $all = $this->all();
        $last = array_pop($all);
        return implode($glue, $all) . $finalGlue . $last;
    }

    // ArrayAccess
    public function offsetExists(mixed $offset): bool { return isset($this->items[$offset]); }
    public function offsetGet(mixed $offset): mixed { return $this->items[$offset]; }
    public function offsetSet(mixed $offset, mixed $value): void { $this->items[$offset] = $value; }
    public function offsetUnset(mixed $offset): void { unset($this->items[$offset]); }

    // IteratorAggregate
    public function getIterator(): ArrayIterator { return new ArrayIterator($this->items); }

    public function __toString(): string { return $this->toJson(); }
}
