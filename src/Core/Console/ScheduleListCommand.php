<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;

class ScheduleListCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('schedule:list')
             ->setDescription('List all scheduled tasks with their cron expression and next due time.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $tasks = \HexaGen\Core\Console\Scheduler::getTasks();

        if (empty($tasks)) {
            $io->info('No scheduled tasks defined. Register tasks in your AppServiceProvider::boot().');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['#', 'Expression', 'Next Due', 'Description']);

        foreach ($tasks as $i => $task) {
            $table->addRow([
                $i + 1,
                $task->getCron(),
                $task->isDue() ? '<info>Now</info>' : 'Pending',
                $task->getDescription(),
            ]);
        }

        $table->render();
        return Command::SUCCESS;
    }
}
