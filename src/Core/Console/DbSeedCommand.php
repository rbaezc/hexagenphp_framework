<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DbSeedCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('db:seed')
             ->setDescription('Run database seeders.')
             ->addOption('class', null, InputOption::VALUE_OPTIONAL, 'The seeder class to run', 'DatabaseSeeder');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $class = $input->getOption('class');

        $seedersDir = dirname(__DIR__, 3) . '/database/seeders';

        // Load all seeder files
        foreach (glob($seedersDir . '/*.php') as $file) {
            require_once $file;
        }

        if (!class_exists($class)) {
            $io->error("Seeder class [{$class}] not found.");
            return Command::FAILURE;
        }

        $io->info("Seeding: {$class}");
        $seeder = new $class();
        $seeder->run();
        $io->success("Database seeding completed.");

        return Command::SUCCESS;
    }
}
