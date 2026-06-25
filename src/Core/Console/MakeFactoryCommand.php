<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeFactoryCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:factory')
             ->setDescription('Create a new model factory class.')
             ->addArgument('name', InputArgument::REQUIRED, 'The factory name (e.g. UserFactory)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = trim($input->getArgument('name'));

        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*Factory$/', $name)) {
            $io->error("Factory name must follow the pattern: ModelNameFactory");
            return Command::FAILURE;
        }

        $model    = str_replace('Factory', '', $name);
        $dir      = dirname(__DIR__, 3) . '/database/factories';
        $filePath = $dir . '/' . $name . '.php';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($filePath)) {
            $io->warning("Factory $name already exists.");
            return Command::SUCCESS;
        }

        file_put_contents($filePath, <<<PHP
<?php
namespace Database\Factories;

use HexaGen\Core\Testing\ModelFactory;

class {$name} extends ModelFactory
{
    protected string \$model = \\App\\Models\\{$model}::class;

    public function definition(): array
    {
        return [
            // 'name' => \$this->faker->name(),
            // 'email' => \$this->faker->unique()->safeEmail(),
        ];
    }
}
PHP);

        $io->success("Factory created: database/factories/{$name}.php");
        return Command::SUCCESS;
    }
}
