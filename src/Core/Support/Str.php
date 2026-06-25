<?php
namespace HexaGen\Core\Support;

class Str
{
    use Traits\Macroable;

    public static function slug(string $value, string $separator = '-', string $language = 'en'): string
    {
        $value = mb_strtolower(static::ascii($value, $language));
        $value = preg_replace('/[^\pL\pN\s-]/u', '', $value);
        $value = preg_replace('/[\s_]+/', $separator, $value);
        $value = preg_replace('/[' . preg_quote($separator, '/') . ']+/', $separator, $value);
        return trim($value, $separator);
    }

    public static function ascii(string $value, string $language = 'en'): string
    {
        return transliterator_transliterate('Any-Latin; Latin-ASCII', $value) ?: $value;
    }

    public static function camel(string $value): string
    {
        return lcfirst(static::studly($value));
    }

    public static function studly(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

    public static function snake(string $value, string $delimiter = '_'): string
    {
        $value = preg_replace('/\s+/u', '', ucwords($value));
        return mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
    }

    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    public static function plural(string $value, int|array|\Countable $count = 2): string
    {
        $count = is_int($count) ? $count : count($count);
        if ($count === 1) {
            return $value;
        }
        // Simple English pluralization
        if (preg_match('/(s|x|z|ch|sh)$/i', $value)) {
            return $value . 'es';
        }
        if (preg_match('/[^aeiou]y$/i', $value)) {
            return substr($value, 0, -1) . 'ies';
        }
        if (preg_match('/f$/i', $value)) {
            return substr($value, 0, -1) . 'ves';
        }
        if (preg_match('/fe$/i', $value)) {
            return substr($value, 0, -2) . 'ves';
        }
        return $value . 's';
    }

    public static function singular(string $value): string
    {
        if (preg_match('/ies$/i', $value)) {
            return substr($value, 0, -3) . 'y';
        }
        if (preg_match('/ves$/i', $value)) {
            return substr($value, 0, -3) . 'f';
        }
        if (preg_match('/es$/i', $value) && preg_match('/(s|x|z|ch|sh)es$/i', $value)) {
            return substr($value, 0, -2);
        }
        if (preg_match('/s$/i', $value) && !preg_match('/ss$/i', $value)) {
            return substr($value, 0, -1);
        }
        return $value;
    }

    public static function contains(string $haystack, string|array $needles, bool $ignoreCase = false): bool
    {
        if ($ignoreCase) {
            $haystack = mb_strtolower($haystack);
        }
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_contains($ignoreCase ? mb_strtolower($needle) : $needle, '') === false) {
                if (str_contains($haystack, $ignoreCase ? mb_strtolower($needle) : $needle)) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (str_starts_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function endsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (str_ends_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }
        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    public static function words(string $value, int $words = 100, string $end = '...'): string
    {
        preg_match('/^\s*+(?:\S+\s*+){1,' . $words . '}/u', $value, $matches);
        if (!isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }
        return rtrim($matches[0]) . $end;
    }

    public static function length(string $value, ?string $encoding = null): int
    {
        return mb_strlen($value, $encoding ?? 'UTF-8');
    }

    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }

    public static function orderedUuid(): string
    {
        $time = microtime(true);
        $timeHex = str_pad(dechex((int)($time * 1000)), 12, '0', STR_PAD_LEFT);
        return sprintf(
            '%s-%s-7%s-%s-%s',
            substr($timeHex, 0, 8),
            substr($timeHex, 8, 4),
            substr(bin2hex(random_bytes(2)), 1, 3),
            dechex(random_int(0x8000, 0xbfff)),
            bin2hex(random_bytes(6))
        );
    }

    public static function random(int $length = 16): string
    {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $size   = $length - $len;
            $bytes  = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        return $string;
    }

    public static function before(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }
        $result = strstr($subject, $search, true);
        return $result === false ? $subject : $result;
    }

    public static function beforeLast(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }
        $pos = strrpos($subject, $search);
        return $pos === false ? $subject : substr($subject, 0, $pos);
    }

    public static function after(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }
        return array_reverse(explode($search, $subject, 2))[0];
    }

    public static function afterLast(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }
        $pos = strrpos($subject, $search);
        return $pos === false ? $subject : substr($subject, $pos + strlen($search));
    }

    public static function between(string $subject, string $from, string $to): string
    {
        if ($from === '' || $to === '') {
            return $subject;
        }
        return static::beforeLast(static::after($subject, $from), $to);
    }

    public static function padLeft(string $value, int $length, string $pad = ' '): string
    {
        return str_pad($value, $length, $pad, STR_PAD_LEFT);
    }

    public static function padRight(string $value, int $length, string $pad = ' '): string
    {
        return str_pad($value, $length, $pad, STR_PAD_RIGHT);
    }

    public static function padBoth(string $value, int $length, string $pad = ' '): string
    {
        return str_pad($value, $length, $pad, STR_PAD_BOTH);
    }

    public static function wrap(string $value, string $before, string $after = ''): string
    {
        return $before . $value . ($after ?: $before);
    }

    public static function replace(string|array $search, string|array $replace, string $subject): string
    {
        return str_replace($search, $replace, $subject);
    }

    public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }
        $pos = strpos($subject, $search);
        return $pos === false ? $subject : substr_replace($subject, $replace, $pos, strlen($search));
    }

    public static function replaceLast(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }
        $pos = strrpos($subject, $search);
        return $pos === false ? $subject : substr_replace($subject, $replace, $pos, strlen($search));
    }

    public static function remove(string|array $search, string $subject, bool $caseSensitive = true): string
    {
        return $caseSensitive
            ? str_replace($search, '', $subject)
            : str_ireplace($search, '', $subject);
    }

    public static function trim(string $value, ?string $characters = null): string
    {
        return $characters !== null ? trim($value, $characters) : trim($value);
    }

    public static function ltrim(string $value, ?string $characters = null): string
    {
        return $characters !== null ? ltrim($value, $characters) : ltrim($value);
    }

    public static function rtrim(string $value, ?string $characters = null): string
    {
        return $characters !== null ? rtrim($value, $characters) : rtrim($value);
    }

    public static function substr(string $string, int $start, ?int $length = null): string
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    public static function substrCount(string $haystack, string $needle, int $offset = 0, ?int $length = null): int
    {
        if ($length !== null) {
            return substr_count($haystack, $needle, $offset, $length);
        }
        return substr_count($haystack, $needle, $offset);
    }

    public static function isJson(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public static function isUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }

    public static function of(string $value): \HexaGen\Core\Support\Stringable
    {
        return new \HexaGen\Core\Support\Stringable($value);
    }

    public static function mask(string $string, string $character, int $index, ?int $length = null): string
    {
        $segment   = mb_substr($string, $index, $length ?? mb_strlen($string) - $index, 'UTF-8');
        $startIndex = $index < 0 ? max(0, mb_strlen($string) + $index) : min($index, mb_strlen($string));
        $start      = mb_substr($string, 0, $startIndex, 'UTF-8');
        $end        = mb_substr($string, $startIndex + mb_strlen($segment, 'UTF-8'), null, 'UTF-8');
        return $start . str_repeat(mb_substr($character, 0, 1, 'UTF-8'), mb_strlen($segment, 'UTF-8')) . $end;
    }
}
