<?php
namespace HexaGen\Core\Bootstrap;

/**
 * Carga un archivo .env en el entorno del proceso.
 *
 * No sobrescribe variables que ya existen (en producción mandan las del
 * contenedor o del sistema). Soporta comentarios, "export", comillas simples
 * y dobles, y comentarios al final de la línea en valores sin comillas.
 */
final class EnvLoader
{
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $parsed = self::parseLine($line);
            if ($parsed === null) {
                continue;
            }
            [$key, $value] = $parsed;

            if (getenv($key) !== false || isset($_ENV[$key])) {
                continue;
            }

            putenv("{$key}={$value}");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }

    /** @return array{0: string, 1: string}|null */
    private static function parseLine(string $line): ?array
    {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            return null;
        }
        if (str_starts_with($line, 'export ')) {
            $line = ltrim(substr($line, 7));
        }

        $eq = strpos($line, '=');
        if ($eq === false) {
            return null;
        }

        $key = trim(substr($line, 0, $eq));
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
            return null;
        }

        return [$key, self::parseValue(trim(substr($line, $eq + 1)))];
    }

    private static function parseValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $quote = $value[0];
        if (($quote === '"' || $quote === "'") && ($end = strpos($value, $quote, 1)) !== false) {
            $inner = substr($value, 1, $end - 1);
            return $quote === '"' ? str_replace(['\\n', '\\"'], ["\n", '"'], $inner) : $inner;
        }

        // Sin comillas: " #" inicia un comentario.
        $hash = strpos($value, ' #');
        return rtrim($hash === false ? $value : substr($value, 0, $hash));
    }
}
