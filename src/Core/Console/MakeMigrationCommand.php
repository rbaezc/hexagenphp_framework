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
        $this->setName('make:migration')
             ->setDescription('Create a new migration file.')
             ->addArgument('name', InputArgument::REQUIRED, 'Migration name (e.g. create_users_table)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = strtolower($input->getArgument('name'));

        $migrationsDir = dirname(__DIR__, 3) . '/database/migrations';
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $fileName  = "{$timestamp}_{$name}.php";
        $filePath  = "{$migrationsDir}/{$fileName}";

        $tableName = $this->guessTableName($name);
        $isCreate  = str_starts_with($name, 'create_');

        $stub = $isCreate
            ? $this->createStub($tableName)
            : $this->alterStub($tableName);

        file_put_contents($filePath, $stub);

        $io->success("Migration created: database/migrations/{$fileName}");
        return Command::SUCCESS;
    }

    private function guessTableName(string $name): string
    {
        if (str_starts_with($name, 'create_') && str_ends_with($name, '_table')) {
            return substr($name, 7, -6);
        }
        if (preg_match('/(?:add|remove|drop)_\w+_(?:to|from|in)_(\w+)/', $name, $m)) {
            return $m[1];
        }
        return 'table_name';
    }

    private function createStub(string $table): string
    {
        return <<<PHP
<?php
use HexaGen\Core\Database\Migration;
use HexaGen\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(Schema \$schema): void
    {
        \$schema->create('{$table}', function (\$table) {
            \$table->id();
            \$table->string('name');
            \$table->timestamps();
        });
    }

    public function down(Schema \$schema): void
    {
        \$schema->dropIfExists('{$table}');
    }
};
PHP;
    }

    private function alterStub(string $table): string
    {
        return <<<PHP
<?php
use HexaGen\Core\Database\Migration;
use HexaGen\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(Schema \$schema): void
    {
        \$schema->table('{$table}', function (\$table) {
            \$table->string('column_name')->nullable();
        });
    }

    public function down(Schema \$schema): void
    {
        \$schema->table('{$table}', function (\$table) {
            \$table->dropColumn('column_name');
        });
    }
};
PHP;
    }
}
