<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DownCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('down')
             ->setDescription('Put the application into maintenance mode.')
             ->addOption('message', null, InputOption::VALUE_OPTIONAL, 'Message to show users', 'Be right back.')
             ->addOption('retry', null, InputOption::VALUE_OPTIONAL, 'Retry-After seconds', 60)
             ->addOption('allow', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'IPs to allow through');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $file = dirname(__DIR__, 3) . '/storage/framework/maintenance.php';

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        $data = [
            'message' => $input->getOption('message'),
            'retry'   => (int) $input->getOption('retry'),
            'allow'   => $input->getOption('allow'),
            'time'    => time(),
        ];

        file_put_contents($file, '<?php return ' . var_export($data, true) . ';');

        $io->success('Application is now in maintenance mode.');
        return Command::SUCCESS;
    }
}
