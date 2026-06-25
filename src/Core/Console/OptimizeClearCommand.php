<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OptimizeClearCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('optimize:clear')
             ->setDescription('Remove all cached bootstrap files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Clearing caches...');

        $this->getApplication()->find('config:clear')->run(new ArrayInput([]), $output);

        $io->success('Caches cleared.');
        return Command::SUCCESS;
    }
}
