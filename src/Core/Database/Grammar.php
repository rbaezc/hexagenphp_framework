<?php
namespace HexaGen\Core\Database;

/**
 * Reglas de SQL que cambian según el motor.
 *
 * MySQL/MariaDB citan identificadores con backticks; PostgreSQL y SQLite
 * con comillas dobles (estándar SQL). Todo SQL armado a mano en el núcleo
 * debe citar tablas y columnas con estos métodos.
 */
final class Grammar
{
    public static function driver(\PDO $pdo): string
    {
        return (string) $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    public static function usesBackticks(string $driver): bool
    {
        return in_array($driver, ['mysql', 'mariadb'], true);
    }

    /**
     * Cita un identificador ("tabla", "tabla.columna" o "*").
     * Elimina comillas incrustadas para impedir inyección por nombre de columna.
     */
    public static function wrap(string $driver, string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }

        $quote = self::usesBackticks($driver) ? '`' : '"';

        return implode('.', array_map(
            static fn (string $part): string => $part === '*'
                ? '*'
                : $quote . str_replace(['`', '"'], '', $part) . $quote,
            explode('.', $identifier)
        ));
    }

    public static function wrapFor(\PDO $pdo, string $identifier): string
    {
        return self::wrap(self::driver($pdo), $identifier);
    }

    /** Lista de columnas citadas: "a", "b", "c". */
    public static function columnList(string $driver, array $columns): string
    {
        return implode(', ', array_map(static fn (string $c): string => self::wrap($driver, $c), $columns));
    }

    /** Columna de clave primaria autoincremental para CREATE TABLE. */
    public static function autoIncrementId(string $driver, string $name = 'id'): string
    {
        $column = self::wrap($driver, $name);

        return match ($driver) {
            'pgsql'  => "{$column} BIGSERIAL PRIMARY KEY",
            'sqlite' => "{$column} INTEGER PRIMARY KEY AUTOINCREMENT",
            default  => "{$column} BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        };
    }

    /** Tipo de fecha y hora para columnas escritas a mano. */
    public static function dateTimeType(string $driver): string
    {
        return $driver === 'pgsql' ? 'TIMESTAMP' : 'DATETIME';
    }

    /** INSERT que ignora duplicados, en la sintaxis de cada motor. */
    public static function insertIgnore(string $driver, string $table, array $columns): string
    {
        $cols         = self::columnList($driver, $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $wrapped      = self::wrap($driver, $table);

        return match ($driver) {
            'sqlite' => "INSERT OR IGNORE INTO {$wrapped} ({$cols}) VALUES ({$placeholders})",
            'pgsql'  => "INSERT INTO {$wrapped} ({$cols}) VALUES ({$placeholders}) ON CONFLICT DO NOTHING",
            default  => "INSERT IGNORE INTO {$wrapped} ({$cols}) VALUES ({$placeholders})",
        };
    }
}
