<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeProviderCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:provider')
             ->setDescription('Genera un ServiceProvider.')
             ->addArgument('name', InputArgument::REQUIRED, 'Nombre del provider (ej. AuthServiceProvider)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $dir  = dirname(__DIR__, 4) . '/src/Providers';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = "$dir/$name.php";
        if (file_exists($file)) { $io->error("$name ya existe."); return Command::FAILURE; }

        file_put_contents($file, <<<PHP
        <?php
        namespace HexaGen\Providers;

        use HexaGen\Core\Kernel;
        use HexaGen\Core\ServiceProvider;
        use Symfony\Component\DependencyInjection\ContainerBuilder;

        class {$name} extends ServiceProvider
        {
            public function register(): void
            {
                // Registra servicios en el container DI
                // \$this->kernel->getContainer()->register(MyService::class)->setPublic(true);
            }

            public function boot(): void
            {
                // El container ya está compilado — puedes resolver servicios aquí
            }

            public function middlewares(): array { return []; }
            public function commands(): array    { return []; }
        }
        PHP);

        $io->success("Provider creado: src/Providers/$name.php");
        return Command::SUCCESS;
    }
}
