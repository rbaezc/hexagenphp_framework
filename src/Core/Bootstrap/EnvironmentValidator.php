<?php
namespace HexaGen\Core\Bootstrap;

/**
 * Valida variables de entorno requeridas en el arranque del framework.
 * Falla rápido con un mensaje claro antes de que el error aparezca en runtime.
 *
 * Uso en index.php antes de boot():
 *   EnvironmentValidator::require(['APP_KEY', 'DB_DSN', 'JWT_SECRET']);
 *
 * O mediante el Kernel automáticamente leyendo config/env.php.
 */
class EnvironmentValidator
{
    /**
     * @param  string[] $required  Variables de entorno que deben estar definidas y no vacías.
     * @throws \RuntimeException   Con la lista de variables faltantes.
     */
    public static function require(array $required): void
    {
        $missing = [];

        foreach ($required as $var) {
            $value = getenv($var);
            if ($value === false || $value === '') {
                $missing[] = $var;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                "Variables de entorno requeridas no configuradas: " . implode(', ', $missing) . "\n" .
                "Copia .env.example a .env y configura los valores."
            );
        }
    }

    /**
     * Valida desde un archivo config/env.php que retorna ['required' => [...], 'optional' => [...]].
     */
    public static function fromConfig(): void
    {
        $configPath = dirname(__DIR__, 3) . '/config/env.php';
        if (!file_exists($configPath)) {
            return;
        }

        $config   = require $configPath;
        $required = $config['required'] ?? [];

        if (!empty($required)) {
            self::require($required);
        }
    }
}
