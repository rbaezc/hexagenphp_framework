<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeJobCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:job')
             ->setDescription('Genera un Job para la cola.')
             ->addArgument('name', InputArgument::REQUIRED, 'Nombre del Job (ej. EnviarEmailJob)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $dir  = dirname(__DIR__, 4) . '/src/Jobs';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = "$dir/$name.php";
        if (file_exists($file)) { $io->error("$name ya existe."); return Command::FAILURE; }

        file_put_contents($file, <<<PHP
        <?php
        namespace HexaGen\Jobs;

        use HexaGen\Core\Queue\Job;

        class {$name} extends Job
        {
            public int \$tries   = 3;
            public int \$timeout = 60;

            public function __construct(
                // define las propiedades del job aquí
            ) {}

            public function handle(): void
            {
                // lógica del job
            }

            public function failed(\Throwable \$e): void
            {
                // opcional: manejo de fallo permanente
            }
        }
        PHP);

        $io->success("Job creado: src/Jobs/$name.php");
        return Command::SUCCESS;
    }
}
