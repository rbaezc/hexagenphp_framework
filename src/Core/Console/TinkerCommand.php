<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TinkerCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('tinker')
             ->setDescription('Interact with the application in a REPL (requires psy/psysh).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!class_exists(\Psy\Shell::class)) {
            $io->error('PsySH is not installed. Run: composer require --dev psy/psysh');
            return Command::FAILURE;
        }

        $io->info('Starting Tinker session... (Ctrl+D or "exit" to quit)');

        $config = new \Psy\Configuration([
            'updateCheck' => 'never',
            'useBracketedPaste' => true,
        ]);

        $shell = new \Psy\Shell($config);

        // Push all helpers and key classes into the shell context
        $shell->setScopeVariables([
            'app'     => \HexaGen\Core\Kernel::getInstance(),
            'config'  => fn(string $key, mixed $default = null) => \HexaGen\Core\Config::get($key, $default),
            'cache'   => \HexaGen\Core\Cache\CacheManager::driver(),
            'db'      => fn(?string $table = null) => db($table),
            'log'     => fn(string $channel = 'default') => new \HexaGen\Core\Log\Logger($channel),
        ]);

        $shell->run();

        return Command::SUCCESS;
    }
}
