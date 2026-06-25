<?php
namespace HexaGen\Core\Console;

use HexaGen\Core\Database\DatabaseConnection;
use HexaGen\Core\Database\Schema\Schema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateRollbackCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('migrate:rollback')
             ->setDescription('Roll back the last batch of migrations.')
             ->addOption('step', null, InputOption::VALUE_OPTIONAL, 'Number of batches to roll back', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $pdo    = (new DatabaseConnection())->getPdo();
        $schema = new Schema($pdo);
        $steps  = max(1, (int) $input->getOption('step'));

        // Detect if migrations table exists (driver-agnostic)
        try {
            $pdo->query("SELECT 1 FROM migrations LIMIT 1");
        } catch (\Throwable) {
            $io->info('No migrations table found. Nothing to roll back.');
            return Command::SUCCESS;
        }

        // Get the last N batches to roll back
        $maxBatch = (int) $pdo->query("SELECT MAX(batch) FROM migrations")->fetchColumn();
        if ($maxBatch === 0) {
            $io->success('Nothing to roll back.');
            return Command::SUCCESS;
        }

        $targetBatch = max(1, $maxBatch - $steps + 1);
        $toRollback  = $pdo->prepare("SELECT migration FROM migrations WHERE batch >= ? ORDER BY id DESC");
        $toRollback->execute([$targetBatch]);
        $migrations  = $toRollback->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($migrations)) {
            $io->success('Nothing to roll back.');
            return Command::SUCCESS;
        }

        $migrationsDir = dirname(__DIR__, 3) . '/database/migrations';

        foreach ($migrations as $name) {
            $file = $migrationsDir . '/' . $name . '.php';
            if (!file_exists($file)) {
                $io->warning("File not found for rollback: $name. Skipping.");
                continue;
            }

            $io->text("Rolling back: <comment>$name</comment>");
            $migration = require $file;

            if (!$migration instanceof \HexaGen\Core\Database\Migration) {
                $io->warning("Skipped $name — not a Migration instance.");
                continue;
            }

            try {
                $pdo->beginTransaction();
                $migration->down($schema);
                $pdo->prepare("DELETE FROM migrations WHERE migration = ?")->execute([$name]);
                $pdo->commit();
                $io->text("  Rolled back: <info>$name</info>");
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $io->error("Failed: $name — " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $io->success(count($migrations) . ' migration(s) rolled back.');
        return Command::SUCCESS;
    }
}
