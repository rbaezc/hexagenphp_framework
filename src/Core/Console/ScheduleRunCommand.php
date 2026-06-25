<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleRunCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('schedule:run')
             ->setDescription('Ejecuta las tareas programadas que están pendientes. Llámalo desde crontab cada minuto.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Scheduler::runDue();
        return Command::SUCCESS;
    }
}
