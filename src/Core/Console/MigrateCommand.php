<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use HexaGen\Core\Database\DatabaseConnection;

class MigrateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('migrate')
            ->setDescription('Ejecuta todas las migraciones pendientes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $db = new DatabaseConnection();
        $pdo = $db->getPdo();

        // 1. Create migrations table if not exists (SQLite auto-increment format)
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration TEXT NOT NULL,
            run_at TEXT NOT NULL
        )");

        $projectDir = dirname(dirname(dirname(__DIR__)));
        $migrationsDir = $projectDir . '/database/migrations';

        if (!is_dir($migrationsDir)) {
            $io->info('No se encontró la carpeta de migraciones. Nada que migrar.');
            return Command::SUCCESS;
        }

        $files = glob($migrationsDir . '/*.php');
        if (empty($files)) {
            $io->info('No hay archivos de migración pendientes.');
            return Command::SUCCESS;
        }

        // Sort files to guarantee execution order based on timestamp prefixes
        sort($files);

        // Get already executed migrations from database log
        $stmt = $pdo->query("SELECT migration FROM migrations");
        $executed = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $runCount = 0;
        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            if (in_array($migrationName, $executed)) {
                continue;
            }

            $io->text("Migrando: <comment>$migrationName</comment>...");
            
            // Require the anonymous class and check type
            $migration = require $file;
            if ($migration instanceof \HexaGen\Core\Database\Migration) {
                try {
                    $pdo->beginTransaction();
                    $migration->up($pdo);
                    
                    // Log migration run
                    $stmtLog = $pdo->prepare("INSERT INTO migrations (migration, run_at) VALUES (:mig, :run_at)");
                    $stmtLog->execute([
                        ':mig' => $migrationName,
                        ':run_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $pdo->commit();
                    $io->text("Migrado:  <info>$migrationName</info>");
                    $runCount++;
                } catch (\Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $io->error("Fallo al migrar: $migrationName. Detalle: " . $e->getMessage());
                    return Command::FAILURE;
                }
            }
        }

        if ($runCount === 0) {
            $io->success('Base de datos al día. Nada que migrar.');
        } else {
            $io->success(sprintf('Se ejecutaron %d migración(es) con éxito.', $runCount));
        }

        return Command::SUCCESS;
    }
}
