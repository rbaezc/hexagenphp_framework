<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeResourceCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:resource')
             ->setDescription('Genera un ApiResource.')
             ->addArgument('name', InputArgument::REQUIRED, 'Nombre (ej. UserResource)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $dir  = dirname(__DIR__, 4) . '/src/Resources';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = "$dir/$name.php";
        if (file_exists($file)) { $io->error("$name ya existe."); return Command::FAILURE; }

        file_put_contents($file, <<<PHP
        <?php
        namespace HexaGen\Resources;

        use HexaGen\Core\Http\ApiResource;

        class {$name} extends ApiResource
        {
            public function toArray(): array
            {
                return [
                    'id'         => \$this->id,
                    'created_at' => \$this->created_at,
                    // añade más campos aquí
                ];
            }
        }
        PHP);

        $io->success("Resource creado: src/Resources/$name.php");
        return Command::SUCCESS;
    }
}
