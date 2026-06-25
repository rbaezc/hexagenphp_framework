<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeSeederCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:seeder')
             ->setDescription('Generate a database seeder class.')
             ->addArgument('name', InputArgument::REQUIRED, 'Seeder name (e.g. UsersSeeder)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $dir  = dirname(__DIR__, 3) . '/database/seeders';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = "{$dir}/{$name}.php";
        if (file_exists($file)) {
            $io->error("{$name} already exists.");
            return Command::FAILURE;
        }

        file_put_contents($file, <<<PHP
<?php

use HexaGen\Core\Database\Seeder;

class {$name} extends Seeder
{
    public function run(): void
    {
        // DB::table('users')->insert([...]);
    }
}
PHP);

        $io->success("Seeder created: database/seeders/{$name}.php");
        return Command::SUCCESS;
    }
}
