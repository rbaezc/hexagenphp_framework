<?php
namespace HexaGen\Core\Database\Schema;

class Schema
{
    private \PDO   $pdo;
    private string $driver;

    public function __construct(\PDO $pdo)
    {
        $this->pdo    = $pdo;
        $this->driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    // ── DDL ──────────────────────────────────────────────────────────────────

    public function create(string $table, \Closure $callback): void
    {
        $blueprint = new Blueprint($table, true);
        $callback($blueprint);
        $this->execute($blueprint);
    }

    /** Modify an existing table (ALTER TABLE). */
    public function table(string $table, \Closure $callback): void
    {
        $blueprint = new Blueprint($table, false);
        $callback($blueprint);
        $this->execute($blueprint);
    }

    public function drop(string $table): void
    {
        $this->pdo->exec("DROP TABLE `{$table}`");
    }

    public function dropIfExists(string $table): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
    }

    public function rename(string $from, string $to): void
    {
        $this->pdo->exec("ALTER TABLE `{$from}` RENAME TO `{$to}`");
    }

    // ── Introspection ─────────────────────────────────────────────────────────

    public function hasTable(string $table): bool
    {
        try {
            return match ($this->driver) {
                'sqlite' => (bool) $this->pdo
                    ->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=" . $this->pdo->quote($table))
                    ->fetch(),
                'pgsql'  => (bool) $this->pdo
                    ->query("SELECT 1 FROM pg_tables WHERE tablename=" . $this->pdo->quote($table))
                    ->fetch(),
                default  => (bool) $this->pdo
                    ->query("SHOW TABLES LIKE " . $this->pdo->quote($table))
                    ->fetch(),
            };
        } catch (\Throwable) {
            return false;
        }
    }

    public function hasColumn(string $table, string $column): bool
    {
        try {
            if ($this->driver === 'sqlite') {
                $rows = $this->pdo->query("PRAGMA table_info(`{$table}`)")->fetchAll();
                foreach ($rows as $row) {
                    if ($row['name'] === $column) return true;
                }
                return false;
            }
            if ($this->driver === 'pgsql') {
                return (bool) $this->pdo
                    ->query("SELECT 1 FROM information_schema.columns WHERE table_name=" . $this->pdo->quote($table) . " AND column_name=" . $this->pdo->quote($column))
                    ->fetch();
            }
            return (bool) $this->pdo
                ->query("SHOW COLUMNS FROM `{$table}` LIKE " . $this->pdo->quote($column))
                ->fetch();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getColumns(string $table): array
    {
        try {
            if ($this->driver === 'sqlite') {
                return array_column($this->pdo->query("PRAGMA table_info(`{$table}`)")->fetchAll(), 'name');
            }
            if ($this->driver === 'pgsql') {
                return array_column(
                    $this->pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name=" . $this->pdo->quote($table))->fetchAll(),
                    'column_name'
                );
            }
            return array_column($this->pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(), 'Field');
        } catch (\Throwable) {
            return [];
        }
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function execute(Blueprint $blueprint): void
    {
        foreach ($blueprint->toSql($this->driver) as $sql) {
            $this->pdo->exec($sql);
        }
    }
}
