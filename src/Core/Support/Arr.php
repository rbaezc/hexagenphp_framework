<?php
namespace HexaGen\Core\Support;

class Arr
{
    use Traits\Macroable;

    public static function get(array $array, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $array;
        }
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        if (!str_contains((string) $key, '.')) {
            return $array[$key] ?? $default;
        }
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        return $array;
    }

    public static function set(array &$array, string|int $key, mixed $value): array
    {
        if (!str_contains((string) $key, '.')) {
            $array[$key] = $value;
            return $array;
        }
        $keys = explode('.', $key);
        $current = &$array;
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
        $current[array_shift($keys)] = $value;
        return $array;
    }

    public static function has(array $array, string|array $keys): bool
    {
        foreach ((array) $keys as $key) {
            if (!array_key_exists($key, $array)) {
                // Try dot notation
                $value = $array;
                foreach (explode('.', $key) as $segment) {
                    if (!is_array($value) || !array_key_exists($segment, $value)) {
                        return false;
                    }
                    $value = $value[$segment];
                }
            }
        }
        return true;
    }

    public static function forget(array &$array, array|string $keys): void
    {
        foreach ((array) $keys as $key) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
                continue;
            }
            $parts = explode('.', $key);
            $current = &$array;
            while (count($parts) > 1) {
                $part = array_shift($parts);
                if (!isset($current[$part]) || !is_array($current[$part])) {
                    break;
                }
                $current = &$current[$part];
            }
            unset($current[array_shift($parts)]);
        }
    }

    public static function only(array $array, array|string $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    public static function except(array $array, array|string $keys): array
    {
        return array_diff_key($array, array_flip((array) $keys));
    }

    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }
        return $results;
    }

    public static function undot(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            static::set($result, $key, $value);
        }
        return $result;
    }

    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        $result = [];
        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $values = $depth === 1 ? array_values($item) : static::flatten($item, $depth - 1);
                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }

    public static function pluck(array $array, string|array $value, string|array|null $key = null): array
    {
        $results = [];
        [$value, $key] = static::explodePluckParameters($value, $key);
        foreach ($array as $item) {
            $itemValue = static::get(is_object($item) ? (array) $item : $item, $value);
            if ($key === null) {
                $results[] = $itemValue;
            } else {
                $itemKey = static::get(is_object($item) ? (array) $item : $item, $key);
                $results[$itemKey] = $itemValue;
            }
        }
        return $results;
    }

    protected static function explodePluckParameters(string|array $value, string|array|null $key): array
    {
        $value = is_string($value) ? explode('.', $value) : $value;
        $key   = is_null($key) || is_array($key) ? $key : explode('.', $key);
        return [$value, $key];
    }

    public static function first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if (empty($array)) {
                return $default;
            }
            return reset($array);
        }
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        return $default;
    }

    public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return empty($array) ? $default : end($array);
        }
        return static::first(array_reverse($array, true), $callback, $default);
    }

    public static function wrap(mixed $value): array
    {
        if ($value === null) {
            return [];
        }
        return is_array($value) ? $value : [$value];
    }

    public static function prepend(array $array, mixed $value, mixed $key = null): array
    {
        if (func_num_args() === 2) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
        return $array;
    }

    public static function pull(array &$array, string $key, mixed $default = null): mixed
    {
        $value = static::get($array, $key, $default);
        static::forget($array, $key);
        return $value;
    }

    public static function sortBy(array $array, string $key, bool $descending = false): array
    {
        usort($array, function ($a, $b) use ($key) {
            $aVal = is_array($a) ? ($a[$key] ?? null) : ($a->$key ?? null);
            $bVal = is_array($b) ? ($b[$key] ?? null) : ($b->$key ?? null);
            return $aVal <=> $bVal;
        });
        return $descending ? array_reverse($array) : $array;
    }

    public static function groupBy(array $array, string|callable $key): array
    {
        $result = [];
        foreach ($array as $item) {
            $groupKey = is_callable($key)
                ? $key($item)
                : (is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null));
            $result[$groupKey][] = $item;
        }
        return $result;
    }

    public static function crossJoin(array ...$arrays): array
    {
        $results = [[]];
        foreach ($arrays as $array) {
            $append = [];
            foreach ($results as $product) {
                foreach ($array as $item) {
                    $append[] = array_merge($product, [$item]);
                }
            }
            $results = $append;
        }
        return $results;
    }

    public static function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    public static function isList(array $array): bool
    {
        return !static::isAssoc($array);
    }

    public static function shuffle(array $array, ?int $seed = null): array
    {
        if ($seed !== null) {
            mt_srand($seed);
        }
        shuffle($array);
        return $array;
    }

    public static function where(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    public static function whereNotNull(array $array): array
    {
        return static::where($array, fn($value) => $value !== null);
    }

    public static function map(array $array, callable $callback): array
    {
        $keys   = array_keys($array);
        $values = array_map($callback, $array, $keys);
        return array_combine($keys, $values);
    }

    public static function keyBy(array $array, string|callable $key): array
    {
        $result = [];
        foreach ($array as $item) {
            $k = is_callable($key) ? $key($item) : (is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null));
            $result[$k] = $item;
        }
        return $result;
    }
}
