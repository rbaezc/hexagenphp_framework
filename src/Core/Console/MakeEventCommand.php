<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeEventCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:event')
             ->setDescription('Genera un Event y su Listener.')
             ->addArgument('name', InputArgument::REQUIRED, 'Nombre del evento (ej. UserRegistered)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $dir  = dirname(__DIR__, 4) . '/src/Events';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Event
        $eventFile = "$dir/$name.php";
        if (!file_exists($eventFile)) {
            file_put_contents($eventFile, <<<PHP
            <?php
            namespace HexaGen\Events;

            use HexaGen\Core\Events\Event;

            class {$name} extends Event
            {
                public function __construct(
                    // define las propiedades del evento
                ) {
                    parent::__construct();
                }
            }
            PHP);
        }

        // Listener
        $listenerFile = "$dir/{$name}Listener.php";
        if (!file_exists($listenerFile)) {
            file_put_contents($listenerFile, <<<PHP
            <?php
            namespace HexaGen\Events;

            use HexaGen\Core\Events\Event;
            use HexaGen\Core\Events\ListenerInterface;

            class {$name}Listener implements ListenerInterface
            {
                public function handle(Event \$event): void
                {
                    /** @var {$name} \$event */
                    // lógica del listener
                }
            }
            PHP);
        }

        $io->success("Evento creado: src/Events/$name.php + {$name}Listener.php");
        return Command::SUCCESS;
    }
}
