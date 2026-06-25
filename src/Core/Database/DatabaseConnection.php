<?php
namespace HexaGen\Core\Database;

use PDO;

class DatabaseConnection
{
    private ?PDO $pdo = null;

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->getPdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($this);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function beginTransaction(): void { $this->getPdo()->beginTransaction(); }
    public function commit(): void           { $this->getPdo()->commit(); }
    public function rollBack(): void         { $this->getPdo()->rollBack(); }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->connect();
        }
        return $this->pdo;
    }

    private function connect(): PDO
    {
        $driver   = getenv('DB_DRIVER')   ?: 'sqlite';
        $host     = getenv('DB_HOST')     ?: '127.0.0.1';
        $port     = getenv('DB_PORT')     ?: ($driver === 'pgsql' ? '5432' : '3306');
        $database = getenv('DB_DATABASE') ?: 'hexagen';
        $user     = getenv('DB_USER')     ?: null;
        $password = getenv('DB_PASSWORD') ?: null;
        $charset  = getenv('DB_CHARSET')  ?: 'utf8mb4';

        $dsn = match ($driver) {
            'pgsql'  => "pgsql:host={$host};port={$port};dbname={$database}",
            'sqlite' => $this->resolveSqliteDsn($database),
            default  => "mysql:host={$host};port={$port};dbname={$database};charset={$charset}",
        };

        // Auto-create the database if it doesn't exist (MySQL / PostgreSQL)
        if ($driver !== 'sqlite') {
            $this->ensureDatabase($driver, $host, $port, $database, $user, $password, $charset);
        }

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    private function resolveSqliteDsn(string $database): string
    {
        // Allow absolute paths and :memory:
        if ($database === ':memory:' || str_starts_with($database, '/') || str_contains($database, ':')) {
            $path = $database;
        } else {
            $path = dirname(__DIR__, 3) . '/' . ltrim($database, '/');
            if (!str_ends_with($path, '.sqlite')) {
                $path .= '.sqlite';
            }
        }

        // Auto-create file and parent directory
        if ($path !== ':memory:' && !file_exists($path)) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            touch($path);
        }

        return "sqlite:{$path}";
    }

    private function ensureDatabase(
        string $driver,
        string $host,
        string $port,
        string $database,
        ?string $user,
        ?string $password,
        string $charset
    ): void {
        try {
            // Connect without specifying the database
            $bootstrapDsn = $driver === 'pgsql'
                ? "pgsql:host={$host};port={$port}"
                : "mysql:host={$host};port={$port};charset={$charset}";

            $bootstrap = new PDO($bootstrapDsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            if ($driver === 'pgsql') {
                // PostgreSQL: check then create (IF NOT EXISTS requires pg 9.3+)
                $exists = $bootstrap->query("SELECT 1 FROM pg_database WHERE datname=" . $bootstrap->quote($database))->fetch();
                if (!$exists) {
                    $bootstrap->exec("CREATE DATABASE " . $bootstrap->quote($database));
                }
            } else {
                $bootstrap->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET {$charset}");
            }
        } catch (\Throwable) {
            // Silently ignore — the main connect() will throw a proper error if it still fails
        }
    }
}
