<?php
namespace HexaGen\Core\Console;

use HexaGen\Core\Log\Logger;
use HexaGen\Core\Queue\Drivers\DatabaseQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueueWorkCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('queue:work')
            ->setDescription('Procesa jobs de la cola de base de datos.')
            ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'Cola a procesar', 'default')
            ->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Segundos de espera cuando la cola está vacía', 3)
            ->addOption('tries', null, InputOption::VALUE_OPTIONAL, 'Número máximo de intentos por job', 3);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $queueName = $input->getOption('queue');
        $sleep     = (int)$input->getOption('sleep');
        $maxTries  = (int)$input->getOption('tries');
        $log       = new Logger();
        $db        = new DatabaseQueue();

        $io->info("Worker iniciado. Procesando cola: [$queueName]");

        // Graceful shutdown: finish current job before exiting on SIGTERM/SIGINT
        $shouldStop = false;
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, function() use (&$shouldStop) { $shouldStop = true; });
            pcntl_signal(SIGINT,  function() use (&$shouldStop) { $shouldStop = true; });
        }

        while (!$shouldStop) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            $row = $db->pop($queueName);

            if (!$row) {
                sleep($sleep);
                continue;
            }

            try {
                $job = unserialize($row['payload']);

                if (!$job instanceof \HexaGen\Core\Queue\Job) {
                    throw new \RuntimeException('Payload inválido en el job #' . $row['id']);
                }

                $job->handle();
                $db->delete((int)$row['id']);
                $io->success('Job procesado: ' . get_class($job));
                $log->info('Job procesado', ['job' => get_class($job), 'id' => $row['id']]);

            } catch (\Throwable $e) {
                $attempts = (int)$row['attempts'];
                $log->error('Job fallido', [
                    'job'      => $row['id'],
                    'error'    => $e->getMessage(),
                    'attempts' => $attempts,
                ]);

                if ($attempts >= $maxTries) {
                    $db->delete((int)$row['id']);
                    if (isset($job)) {
                        $job->failed($e);
                    }
                    $io->error("Job #{$row['id']} descartado tras $maxTries intentos.");
                } else {
                    $db->release((int)$row['id'], delay: 10 * $attempts);
                }
            }
        }

        $io->info('Worker detenido limpiamente.');
        return Command::SUCCESS;
    }
}
