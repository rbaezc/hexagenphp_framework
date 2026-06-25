<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeMailCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:mail')
             ->setDescription('Generate a Mailable class.')
             ->addArgument('name', InputArgument::REQUIRED, 'Mail name (e.g. WelcomeMail)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $dir  = dirname(__DIR__, 4) . '/src/Mail';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = "{$dir}/{$name}.php";
        if (file_exists($file)) {
            $io->error("{$name} already exists.");
            return Command::FAILURE;
        }

        $viewName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));

        file_put_contents($file, <<<PHP
<?php
namespace HexaGen\Mail;

use HexaGen\Core\Mail\Mailable;

class {$name} extends Mailable
{
    public function __construct(
        // public readonly User \$user,
    ) {}

    public function build(): static
    {
        return \$this
            ->subject('{$name}')
            ->view('mail/{$viewName}');
    }
}
PHP);

        $io->success("Mailable created: src/Mail/{$name}.php");
        return Command::SUCCESS;
    }
}
