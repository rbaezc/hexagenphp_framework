<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateFreshCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('migrate:fresh')
             ->setDescription('Drop all tables and re-run all migrations from scratch.')
             ->addOption('seed', null, InputOption::VALUE_NONE, 'Run database seeders after migrating');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io  = new SymfonyStyle($input, $output);
        $pdo = (new \HexaGen\Core\Database\DatabaseConnection())->getPdo();

        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            // SQLite: get all tables and drop them
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '{$dbName}'")->fetchAll(\PDO::FETCH_COLUMN);
        }

        if ($driver !== 'sqlite') {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach ($tables as $table) {
            if ($table === 'migrations') continue;
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            $io->writeln("  <fg=red>Dropped:</> $table");
        }

        if ($driver !== 'sqlite') {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }

        // Reset migrations tracking
        $pdo->exec("DROP TABLE IF EXISTS `migrations`");

        $io->writeln('');
        $io->success('All tables dropped.');

        // Re-run all migrations via MigrateCommand
        $migrate = $this->getApplication()->find('migrate');
        $migrate->run($input, $output);

        if ($input->getOption('seed')) {
            $seed = $this->getApplication()->find('db:seed');
            $seed->run($input, $output);
        }

        return Command::SUCCESS;
    }
}
