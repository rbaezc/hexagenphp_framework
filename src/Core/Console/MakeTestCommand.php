<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeTestCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:test')
             ->setDescription('Genera un TestCase de integración.')
             ->addArgument('name', InputArgument::REQUIRED, 'Nombre del test (ej. ProductosTest)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $dir  = dirname(__DIR__, 4) . '/tests';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = "$dir/$name.php";
        if (file_exists($file)) { $io->error("$name ya existe."); return Command::FAILURE; }

        file_put_contents($file, <<<PHP
        <?php

        use HexaGen\Core\Testing\TestCase;

        class {$name} extends TestCase
        {
            public function test_ejemplo(): void
            {
                \$this->get('/_health')
                     ->assertOk()
                     ->assertJson(['status' => 'ok']);
            }
        }
        PHP);

        $io->success("Test creado: tests/$name.php");
        return Command::SUCCESS;
    }
}
