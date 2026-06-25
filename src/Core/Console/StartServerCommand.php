<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StartServerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('server:start')
            ->setDescription('Levanta FrankenPHP en modo Worker.')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'El host en el que escuchar.', '127.0.0.1')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'El puerto en el que escuchar.', '8080')
            ->addOption('no-watch', null, InputOption::VALUE_NONE, 'Desactivar el hot-reloading (modo watch).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $noWatch = $input->getOption('no-watch');
        
        $isWindows = PHP_OS_FAMILY === 'Windows';
        $binName = $isWindows ? 'frankenphp.exe' : 'frankenphp';
        
        $projectDir = dirname(dirname(dirname(__DIR__)));
        $frankenphpBin = $projectDir . DIRECTORY_SEPARATOR . $binName;

        // 1. Check if bin exists in project root
        if (!file_exists($frankenphpBin)) {
            // 2. Check if bin exists globally in system PATH
            $checkCmd = $isWindows ? 'where frankenphp' : 'which frankenphp';
            @exec($checkCmd, $outputLines, $returnCode);
            
            if ($returnCode === 0 && !empty($outputLines)) {
                // Found globally!
                $frankenphpBin = trim($outputLines[0]);
            } else {
                // Not found. Offer automatic download!
                $io->warning('No se detectó el ejecutable de FrankenPHP en el proyecto ni en el PATH de tu sistema.');
                $confirm = $io->confirm('¿Deseas descargar automáticamente la versión oficial de FrankenPHP para tu plataforma?', true);
                
                if ($confirm) {
                    $io->info('Iniciando descarga e instalación de FrankenPHP...');
                    if ($isWindows) {
                        $installCmd = 'powershell -Command "$env:FRANKENPHP_INSTALL = \'' . $projectDir . '\'; irm https://frankenphp.dev/install.ps1 | iex"';
                    } else {
                        $installCmd = 'cd ' . escapeshellarg($projectDir) . ' && curl -L https://frankenphp.dev/install.sh | sh';
                    }
                    
                    $io->text("Ejecutando script de instalación oficial...");
                    passthru($installCmd, $installExitCode);
                    
                    if ($installExitCode === 0 && file_exists($frankenphpBin)) {
                        $io->success('¡FrankenPHP se ha descargado y configurado exitosamente!');
                    } else {
                        $io->error('La descarga falló o no se pudo guardar el archivo. Puedes descargarlo de forma manual desde https://frankenphp.dev');
                        return Command::FAILURE;
                    }
                } else {
                    $io->error('Para levantar el servidor en modo Worker, necesitas contar con FrankenPHP. Abortando.');
                    return Command::FAILURE;
                }
            }
        }

        $io->title('HexaGen PHP Framework - Servidor de Desarrollo');
        $io->text("Iniciando FrankenPHP en modo Worker...");
        $io->text("Host: <info>http://$host:$port</info>");
        $io->text("Worker Script: <info>public/index.php</info>");
        $io->text("Hot-Reloading (Watch): " . ($noWatch ? '<comment>Desactivado</comment>' : '<info>Activado (observando cambios en .php, .env)</info>'));
        $io->newLine();

        $listen = "$host:$port";
        $root = "public";
        $worker = "public/index.php";

        // Build command array
        $cmd = [
            escapeshellarg($frankenphpBin),
            'php-server',
            '--listen', escapeshellarg($listen),
            '--root', escapeshellarg($root),
            '--worker', escapeshellarg($worker),
        ];

        if (!$noWatch) {
            $cmd[] = '--watch';
        }

        $commandString = implode(' ', $cmd);
        $io->note("Ejecutando: $commandString");

        chdir($projectDir);
        
        // Execute blocking server process
        passthru($commandString, $exitCode);

        return $exitCode;
    }
}
