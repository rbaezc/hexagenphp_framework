<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use HexaGen\Core\OpenApi\OpenApiGenerator;
use HexaGen\Core\Kernel;

class OpenApiGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('openapi:generate')
             ->setDescription('Generate an OpenAPI specification from registered routes.')
             ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format: json or yaml', 'yaml')
             ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $format = $input->getOption('format');
        $out    = $input->getOption('output');

        $kernel    = Kernel::getInstance();
        $routes    = $kernel?->getRoutes() ?? new \Symfony\Component\Routing\RouteCollection();
        $generator = new OpenApiGenerator($routes);

        $content = match ($format) {
            'json'  => $generator->toJson(),
            'yaml'  => $generator->toYaml(),
            default => $io->error("Unknown format: {$format}") ?: '',
        };

        if ($out) {
            file_put_contents($out, $content);
            $io->success("OpenAPI spec written to: {$out}");
        } else {
            $output->writeln($content);
        }

        return Command::SUCCESS;
    }
}
