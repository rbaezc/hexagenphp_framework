<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeMiddlewareCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:middleware')
             ->setDescription('Genera un Middleware.')
             ->addArgument('name', InputArgument::REQUIRED, 'Nombre (ej. EnsureJsonMiddleware)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $dir  = dirname(__DIR__, 4) . '/src/Middleware';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = "$dir/$name.php";
        if (file_exists($file)) { $io->error("$name ya existe."); return Command::FAILURE; }

        file_put_contents($file, <<<PHP
        <?php
        namespace HexaGen\Middleware;

        use HexaGen\Core\Middleware\MiddlewareInterface;
        use Symfony\Component\HttpFoundation\Request;
        use Symfony\Component\HttpFoundation\Response;

        class {$name} implements MiddlewareInterface
        {
            public function handle(Request \$request, callable \$next): Response
            {
                // Lógica antes del controlador
                \$response = \$next(\$request);
                // Lógica después del controlador
                return \$response;
            }
        }
        PHP);

        $io->success("Middleware creado: src/Middleware/$name.php");
        return Command::SUCCESS;
    }
}
