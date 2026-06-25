<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigCacheCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('config:cache')
             ->setDescription('Serialize all config files into a single cached file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $configDir = dirname(__DIR__, 3) . '/config';
        $cacheFile = dirname(__DIR__, 3) . '/bootstrap/cache/config.php';

        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }

        $merged = [];
        foreach (glob($configDir . '/*.php') as $file) {
            $key          = basename($file, '.php');
            $merged[$key] = require $file;
        }

        file_put_contents(
            $cacheFile,
            '<?php return ' . var_export($merged, true) . ';'
        );

        \HexaGen\Core\Config::flush();

        $io->success("Configuration cached: bootstrap/cache/config.php");
        return Command::SUCCESS;
    }
}
