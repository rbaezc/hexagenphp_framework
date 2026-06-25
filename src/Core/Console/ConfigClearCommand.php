<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigClearCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('config:clear')
             ->setDescription('Remove the cached configuration file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $cacheFile = dirname(__DIR__, 3) . '/bootstrap/cache/config.php';

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            $io->success("Configuration cache cleared.");
        } else {
            $io->info("No configuration cache file found.");
        }

        \HexaGen\Core\Config::flush();

        return Command::SUCCESS;
    }
}
