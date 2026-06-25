<?php
namespace HexaGen\Core\Console;

use HexaGen\Core\Queue\Drivers\DatabaseQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueueInstallCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('queue:install')
            ->setDescription('Crea la tabla de jobs en la base de datos.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        (new DatabaseQueue())->createTable();
        $io->success('Tabla de jobs creada correctamente.');
        return Command::SUCCESS;
    }
}
