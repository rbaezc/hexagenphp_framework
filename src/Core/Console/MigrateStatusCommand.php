<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class MigrateStatusCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('migrate:status')
             ->setDescription('Show the status of each migration (ran vs pending).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pdo = (new \HexaGen\Core\Database\DatabaseConnection())->getPdo();

        // Ensure migrations table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) NOT NULL,
            batch INTEGER NOT NULL DEFAULT 1,
            ran_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $ran = $pdo->query("SELECT migration FROM migrations ORDER BY id ASC")
                   ->fetchAll(\PDO::FETCH_COLUMN);
        $ranSet = array_flip($ran);

        $migrationsPath = dirname(__DIR__, 3) . '/database/migrations';
        $files = glob($migrationsPath . '/*.php') ?: [];
        sort($files);

        $table = new Table($output);
        $table->setHeaders(['Migration', 'Status', 'Batch']);

        $batches = $pdo->query("SELECT migration, batch FROM migrations ORDER BY id ASC")
                       ->fetchAll(\PDO::FETCH_KEY_PAIR);

        foreach ($files as $file) {
            $name   = basename($file, '.php');
            $status = isset($ranSet[$name])
                ? '<info>Ran</info>'
                : '<comment>Pending</comment>';
            $batch  = $batches[$name] ?? '-';
            $table->addRow([$name, $status, $batch]);
        }

        $table->render();
        return Command::SUCCESS;
    }
}
