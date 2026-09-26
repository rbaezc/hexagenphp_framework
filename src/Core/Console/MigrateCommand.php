<?php
namespace HexaGen\Core\Console;

use HexaGen\Core\Database\DatabaseConnection;
use HexaGen\Core\Database\Schema\Schema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('migrate')
             ->setDescription('Run all pending database migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $pdo    = (new DatabaseConnection())->getPdo();
        $schema = new Schema($pdo);

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

        $executed    = $pdo->query("SELECT migration FROM migrations")->fetchAll(\PDO::FETCH_COLUMN);
        $executedSet = array_flip($executed);
        $batch       = ((int) $pdo->query("SELECT MAX(batch) FROM migrations")->fetchColumn()) + 1;
        $runCount    = 0;

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
                $migration->up($schema);
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
        \HexaGen\Core\Database\MigrationsTable::ensure($pdo);
    }
}
