<?php
namespace HexaGen\Core\Database;

use PDO;

class DatabaseConnection
{
    private ?PDO $pdo = null;

    /**
     * Get or create a PDO database connection instance.
     */
    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $dbPath = dirname(dirname(dirname(__DIR__))) . '/database.sqlite';
            
            $dsn = getenv('DB_DSN') ?: 'sqlite:' . $dbPath;
            $user = getenv('DB_USER') ?: null;
            $password = getenv('DB_PASSWORD') ?: null;

            if (str_starts_with($dsn, 'sqlite:') && !file_exists($dbPath)) {
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                touch($dbPath);
            }

            $this->pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return $this->pdo;
    }
}
