<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('up')
             ->setDescription('Bring the application out of maintenance mode.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $file = dirname(__DIR__, 3) . '/storage/framework/maintenance.php';

        if (file_exists($file)) {
            unlink($file);
            $io->success('Application is now live.');
        } else {
            $io->info('Application is already live.');
        }

        return Command::SUCCESS;
    }
}
