<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeModelCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:model')
             ->setDescription('Generate a model class.')
             ->addArgument('name', InputArgument::REQUIRED, 'Model name (e.g. Product)')
             ->addOption('migration', 'm', InputOption::VALUE_NONE, 'Also generate a migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $dir  = dirname(__DIR__, 4) . '/src/Models';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = "{$dir}/{$name}.php";
        if (file_exists($file)) {
            $io->error("{$name} already exists.");
            return Command::FAILURE;
        }

        $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)) . 's';

        file_put_contents($file, <<<PHP
<?php
namespace HexaGen\Models;

use HexaGen\Core\Database\Model;
use HexaGen\Core\Database\Traits\HasTimestamps;

class {$name} extends Model
{
    use HasTimestamps;

    protected static string \$table = '{$table}';

    protected array \$casts = [
        // 'active' => 'boolean',
    ];

    protected array \$hidden = [
        // 'password',
    ];
}
PHP);

        $io->success("Model created: src/Models/{$name}.php");

        if ($input->getOption('migration')) {
            $this->getApplication()?->find('make:migration')
                 ->run(new \Symfony\Component\Console\Input\ArrayInput(['name' => "create_{$table}_table"]), $output);
        }

        return Command::SUCCESS;
    }
}
