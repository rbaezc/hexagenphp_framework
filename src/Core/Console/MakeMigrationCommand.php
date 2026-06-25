<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeMigrationCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('make:migration')
            ->setDescription('Crea un nuevo archivo de migración.')
            ->addArgument('name', InputArgument::REQUIRED, 'El nombre de la migración (ej. create_users_table)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = strtolower($input->getArgument('name'));
        
        $projectDir = dirname(dirname(dirname(__DIR__)));
        $migrationsDir = $projectDir . '/database/migrations';

        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $fileName = $timestamp . '_' . $name . '.php';
        $filePath = $migrationsDir . '/' . $fileName;

        // Guess table name from migration name (e.g. create_users_table -> users)
        $tableName = 'table_name';
        if (str_starts_with($name, 'create_') && str_ends_with($name, '_table')) {
            $tableName = substr($name, 7, -6);
        }

        $template = <<<PHP
<?php
use HexaGen\Core\Database\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(PDO \$pdo): void
    {
        \$pdo->exec("CREATE TABLE `{$tableName}` (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            created_at TEXT NOT NULL
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(PDO \$pdo): void
    {
        \$pdo->exec("DROP TABLE `{$tableName}`");
    }
};
PHP;

        file_put_contents($filePath, $template);
        $io->success(sprintf('Migración creada exitosamente: %s', 'database/migrations/' . $fileName));
        return Command::SUCCESS;
    }
}
