<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OptimizeCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('optimize')
             ->setDescription('Cache configuration for faster bootstrapping.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Caching configuration...');

        $this->getApplication()->find('config:cache')->run(new ArrayInput([]), $output);

        $io->success('Application optimized.');
        return Command::SUCCESS;
    }
}
