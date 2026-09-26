<?php
namespace HexaGen\Core\Queue\Drivers;

use HexaGen\Core\Database\Grammar;

use HexaGen\Core\Config;
use HexaGen\Core\Database\DatabaseConnection;
use HexaGen\Core\Log\Logger;
use HexaGen\Core\Queue\Job;

/**
 * Almacena jobs en la tabla `jobs` de la base de datos.
 * Crea la tabla con: php hexaphp queue:install
 * Corre el worker con: php hexaphp queue:work
 */
class DatabaseQueue
{
    private \PDO $pdo;
    private string $table;

    public function __construct()
    {
        $this->pdo   = (new DatabaseConnection())->getPdo();
        $this->table = Config::get('queue.drivers.database.table', 'jobs');
    }

    public function push(Job $job): void
    {
        $this->pdo->prepare("
            INSERT INTO {$this->wrapped()} (queue, payload, attempts, available_at, created_at)
            VALUES (:queue, :payload, 0, :available_at, :created_at)
        ")->execute([
            ':queue'        => $job->getQueue(),
            ':payload'      => serialize($job),
            ':available_at' => time(),
            ':created_at'   => time(),
        ]);
    }

    /** Reserve the next available job from the queue. Returns null when empty. */
    public function pop(string $queue = 'default'): ?array
    {
        $retryAfter = Config::get('queue.drivers.database.retry_after', 90);

        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->wrapped()}
            WHERE queue = :queue
              AND reserved_at IS NULL
              AND available_at <= :now
            ORDER BY id ASC
            LIMIT 1
        ");
        $stmt->execute([':queue' => $queue, ':now' => time()]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $this->pdo->prepare("
            UPDATE {$this->wrapped()}
            SET reserved_at = :reserved_at, attempts = attempts + 1
            WHERE id = :id
        ")->execute([':reserved_at' => time(), ':id' => $row['id']]);

        return $row;
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM {$this->wrapped()} WHERE id = :id")
            ->execute([':id' => $id]);
    }

    public function release(int $id, int $delay = 0): void
    {
        $this->pdo->prepare("
            UPDATE {$this->wrapped()}
            SET reserved_at = NULL, available_at = :available_at
            WHERE id = :id
        ")->execute([':available_at' => time() + $delay, ':id' => $id]);
    }

    public function fail(int $id, string $error): void
    {
        $row = $this->pdo->prepare("SELECT * FROM {$this->wrapped()} WHERE id = :id")
            ->execute([':id' => $id]);

        $failedTable = Config::get('queue.failed.table', 'failed_jobs');
        $this->pdo->prepare("
            INSERT INTO {$this->wrapped($failedTable)} (queue, payload, exception, failed_at)
            VALUES (:queue, :payload, :exception, :failed_at)
        ")->execute([
            ':queue'     => 'unknown',
            ':payload'   => '',
            ':exception' => $error,
            ':failed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->delete($id);
    }

    public function createTable(): void
    {
        $driver = Grammar::driver($this->pdo);
        $id     = Grammar::autoIncrementId($driver);
        $int    = $driver === 'sqlite' ? 'INTEGER' : 'BIGINT';

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS {$this->wrapped()} (
                {$id},
                queue        VARCHAR(255) NOT NULL DEFAULT 'default',
                payload      TEXT    NOT NULL,
                attempts     INTEGER NOT NULL DEFAULT 0,
                reserved_at  {$int} NULL,
                available_at {$int} NOT NULL,
                created_at   {$int} NOT NULL
            )
        ");

        $failedTable = Config::get('queue.failed.table', 'failed_jobs');
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS {$this->wrapped($failedTable)} (
                {$id},
                queue      VARCHAR(255) NOT NULL,
                payload    TEXT NOT NULL,
                exception  TEXT NOT NULL,
                failed_at  VARCHAR(19) NOT NULL
            )
        ");
    }

    private function wrapped(?string $table = null): string
    {
        return Grammar::wrapFor($this->pdo, $table ?? $this->table);
    }
}
