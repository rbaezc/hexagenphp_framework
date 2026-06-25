<?php
namespace HexaGen\Core\I18n;

class Translator
{
    private static string $locale    = 'en';
    private static string $fallback  = 'en';
    private static array  $loaded    = [];

    public static function setLocale(string $locale): void
    {
        static::$locale = $locale;
    }

    public static function getLocale(): string
    {
        return static::$locale;
    }

    public static function setFallback(string $locale): void
    {
        static::$fallback = $locale;
    }

    public static function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale ??= static::$locale;
        $line = static::getLine($key, $locale)
            ?? static::getLine($key, static::$fallback)
            ?? $key;

        return static::makeReplacements($line, $replace);
    }

    public static function transChoice(string $key, int $count, array $replace = [], ?string $locale = null): string
    {
        $locale ??= static::$locale;
        $line = static::getLine($key, $locale)
            ?? static::getLine($key, static::$fallback)
            ?? $key;

        // Pipe syntax: "one item|many items" or "{0} none|{1} one|[2,*] many"
        $choice = static::choiceLine($line, $count);
        return static::makeReplacements($choice, array_merge($replace, ['count' => $count]));
    }

    private static function getLine(string $key, string $locale): ?string
    {
        [$file, $item] = array_pad(explode('.', $key, 2), 2, null);

        if (!isset(static::$loaded[$locale][$file])) {
            $path = dirname(__DIR__, 3) . "/lang/{$locale}/{$file}.php";
            static::$loaded[$locale][$file] = file_exists($path) ? (require $path) : [];
        }

        if ($item === null) {
            $value = static::$loaded[$locale][$file] ?? null;
            return is_string($value) ? $value : null;
        }

        $config = static::$loaded[$locale][$file];
        foreach (explode('.', $item) as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return null;
            }
            $config = $config[$segment];
        }

        return is_string($config) ? $config : null;
    }

    private static function makeReplacements(string $line, array $replace): string
    {
        foreach ($replace as $key => $value) {
            $line = str_replace(
                [':' . $key, ':' . strtoupper($key), ':' . ucfirst($key)],
                [(string) $value, strtoupper((string) $value), ucfirst((string) $value)],
                $line
            );
        }
        return $line;
    }

    private static function choiceLine(string $line, int $count): string
    {
        $segments = explode('|', $line);

        foreach ($segments as $segment) {
            $segment = trim($segment);

            // {n} exact match
            if (preg_match('/^\{(\d+)\}(.*)$/s', $segment, $m)) {
                if ((int) $m[1] === $count) {
                    return trim($m[2]);
                }
                continue;
            }

            // [n,m] range or [n,*]
            if (preg_match('/^\[(\d+),(\d+|\*)\](.*)$/s', $segment, $m)) {
                $from = (int) $m[1];
                $to   = $m[2] === '*' ? PHP_INT_MAX : (int) $m[2];
                if ($count >= $from && $count <= $to) {
                    return trim($m[3]);
                }
                continue;
            }
        }

        // Fallback: first segment for count=1, last for everything else
        return $count === 1 ? trim($segments[0]) : trim(end($segments));
    }

    public static function flush(): void
    {
        static::$loaded = [];
    }
}
