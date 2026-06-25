<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use HexaGen\Core\Database\DatabaseConnection;

class MigrateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('migrate')
             ->setDescription('Run all pending database migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io  = new SymfonyStyle($input, $output);
        $pdo = (new DatabaseConnection())->getPdo();

        $this->ensureMigrationsTable($pdo);

        $migrationsDir = dirname(__DIR__, 3) . '/database/migrations';
        if (!is_dir($migrationsDir)) {
            $io->info('No migrations directory found. Nothing to migrate.');
            return Command::SUCCESS;
        }

        $files = glob($migrationsDir . '/*.php') ?: [];
        if (empty($files)) {
            $io->info('No migration files found.');
            return Command::SUCCESS;
        }

        sort($files);

        $executed = $pdo->query("SELECT migration FROM migrations")
                        ->fetchAll(\PDO::FETCH_COLUMN);
        $executedSet = array_flip($executed);

        $batch    = ((int) $pdo->query("SELECT MAX(batch) FROM migrations")->fetchColumn()) + 1;
        $runCount = 0;

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (isset($executedSet[$name])) {
                continue;
            }

            $io->text("Migrating: <comment>$name</comment>");

            $migration = require $file;
            if (!$migration instanceof \HexaGen\Core\Database\Migration) {
                $io->warning("Skipped $name — does not return a Migration instance.");
                continue;
            }

            try {
                $pdo->beginTransaction();
                $migration->up($pdo);
                $pdo->prepare("INSERT INTO migrations (migration, batch, ran_at) VALUES (?, ?, ?)")
                    ->execute([$name, $batch, date('Y-m-d H:i:s')]);
                $pdo->commit();
                $io->text("  Migrated:  <info>$name</info>");
                $runCount++;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $io->error("Failed: $name — " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        if ($runCount === 0) {
            $io->success('Database is up to date. Nothing to migrate.');
        } else {
            $io->success("$runCount migration(s) ran successfully (batch $batch).");
        }

        return Command::SUCCESS;
    }

    private function ensureMigrationsTable(\PDO $pdo): void
    {
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch     INTEGER NOT NULL DEFAULT 1,
                ran_at    DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            // Add batch column if table existed without it (upgrade path)
            try {
                $pdo->exec("ALTER TABLE migrations ADD COLUMN batch INTEGER NOT NULL DEFAULT 1");
            } catch (\Throwable) {}
            try {
                $pdo->exec("ALTER TABLE migrations ADD COLUMN ran_at DATETIME DEFAULT CURRENT_TIMESTAMP");
            } catch (\Throwable) {}
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
                id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch     INT NOT NULL DEFAULT 1,
                ran_at    DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        }
    }
}
