<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeNotificationCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:notification')
             ->setDescription('Generate a Notification class.')
             ->addArgument('name', InputArgument::REQUIRED, 'Notification name (e.g. PaymentReceived)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $dir  = dirname(__DIR__, 4) . '/src/Notifications';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = "{$dir}/{$name}.php";
        if (file_exists($file)) {
            $io->error("{$name} already exists.");
            return Command::FAILURE;
        }

        file_put_contents($file, <<<PHP
<?php
namespace HexaGen\Notifications;

use HexaGen\Core\Notifications\Notification;

class {$name} extends Notification
{
    public function __construct(
        // public readonly mixed \$data,
    ) {
        parent::__construct();
    }

    public function via(mixed \$notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed \$notifiable): ?\HexaGen\Core\Mail\Mailable
    {
        // return (new \HexaGen\Mail\SomeMail())->subject('{$name}');
        return null;
    }

    public function toDatabase(mixed \$notifiable): array
    {
        return [
            // 'message' => 'Something happened.',
        ];
    }
}
PHP);

        $io->success("Notification created: src/Notifications/{$name}.php");
        return Command::SUCCESS;
    }
}
