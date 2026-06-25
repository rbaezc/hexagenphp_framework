<?php
namespace HexaGen\Core\Console;

use HexaGen\Core\Config;
use HexaGen\Core\Database\DatabaseConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueueFailedCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('queue:failed')
            ->setDescription('Muestra, reintenta o elimina jobs fallidos.')
            ->addArgument('action', InputArgument::OPTIONAL, 'list | retry <id> | flush', 'list')
            ->addArgument('id', InputArgument::OPTIONAL, 'ID del job a reintentar');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $pdo    = (new DatabaseConnection())->getPdo();
        $table  = Config::get('queue.failed.table', 'failed_jobs');

        match ($action) {
            'retry' => $this->retry((int)$input->getArgument('id'), $pdo, $table, $io),
            'flush' => $this->flush($pdo, $table, $io),
            default => $this->list($pdo, $table, $io),
        };

        return Command::SUCCESS;
    }

    private function list(\PDO $pdo, string $table, SymfonyStyle $io): void
    {
        $rows = $pdo->query("SELECT id, queue, exception, failed_at FROM `$table` ORDER BY id DESC LIMIT 50")->fetchAll();
        if (!$rows) {
            $io->success('No hay jobs fallidos.');
            return;
        }
        $io->table(['ID', 'Queue', 'Exception', 'Failed At'], $rows);
    }

    private function retry(int $id, \PDO $pdo, string $table, SymfonyStyle $io): void
    {
        $row = $pdo->prepare("SELECT * FROM `$table` WHERE id = :id")->execute([':id' => $id]);
        if (!$row) {
            $io->error("Job #$id no encontrado.");
            return;
        }
        $pdo->prepare("DELETE FROM `$table` WHERE id = :id")->execute([':id' => $id]);
        $io->success("Job #$id movido de vuelta a la cola para reintento.");
    }

    private function flush(\PDO $pdo, string $table, SymfonyStyle $io): void
    {
        $pdo->exec("DELETE FROM `$table`");
        $io->success('Todos los jobs fallidos eliminados.');
    }
}
