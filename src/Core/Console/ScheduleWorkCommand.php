<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ScheduleWorkCommand extends Command
{
    private bool $shouldStop = false;

    protected function configure(): void
    {
        $this->setName('schedule:work')
             ->setDescription('Start the schedule daemon — checks and runs due tasks every minute.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Schedule daemon started. Press Ctrl+C to stop.');

        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGTERM, function () { $this->shouldStop = true; });
            pcntl_signal(SIGINT,  function () { $this->shouldStop = true; });
        }

        while (!$this->shouldStop) {
            if (extension_loaded('pcntl')) {
                pcntl_signal_dispatch();
            }

            $io->writeln('[' . date('Y-m-d H:i:s') . '] Checking schedule...');
            Scheduler::runDue();

            // Sleep until the next minute boundary
            $sleepSeconds = 60 - (time() % 60);
            $slept        = 0;
            while ($slept < $sleepSeconds && !$this->shouldStop) {
                sleep(1);
                $slept++;
                if (extension_loaded('pcntl')) {
                    pcntl_signal_dispatch();
                }
            }
        }

        $io->info('Schedule daemon stopped gracefully.');
        return Command::SUCCESS;
    }
}
