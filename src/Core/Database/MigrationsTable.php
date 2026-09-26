<?php
namespace HexaGen\Core\Database;

/**
 * Tabla de control de migraciones, en la sintaxis de cada motor.
 * La usan migrate, migrate:status y migrate:rollback.
 */
final class MigrationsTable
{
    public static function ensure(\PDO $pdo): void
    {
        $driver = Grammar::driver($pdo);
        $id     = Grammar::autoIncrementId($driver);
        $ranAt  = Grammar::dateTimeType($driver);

        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            {$id},
            migration VARCHAR(255) NOT NULL,
            batch     INTEGER NOT NULL DEFAULT 1,
            ran_at    {$ranAt} DEFAULT CURRENT_TIMESTAMP
        )");

        // Bases SQLite creadas con versiones anteriores pueden no tener estas columnas.
        if ($driver === 'sqlite') {
            try { $pdo->exec('ALTER TABLE migrations ADD COLUMN batch INTEGER NOT NULL DEFAULT 1'); } catch (\Throwable) {}
            try { $pdo->exec('ALTER TABLE migrations ADD COLUMN ran_at DATETIME DEFAULT CURRENT_TIMESTAMP'); } catch (\Throwable) {}
        }
    }
}
