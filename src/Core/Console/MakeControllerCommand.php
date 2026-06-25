<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeControllerCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:controller')
             ->setDescription('Generate a controller class.')
             ->addArgument('name', InputArgument::REQUIRED, 'Controller name (e.g. ProductController)')
             ->addOption('resource', 'r', InputOption::VALUE_NONE, 'Generate resource CRUD methods')
             ->addOption('slice', 's', InputOption::VALUE_OPTIONAL, 'Place inside a Slice directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $name  = ucfirst($input->getArgument('name'));
        $slice = $input->getOption('slice');

        if ($slice) {
            $dir       = dirname(__DIR__, 4) . "/src/Slices/{$slice}/Controllers";
            $namespace = "HexaGen\\Slices\\{$slice}\\Controllers";
        } else {
            $dir       = dirname(__DIR__, 4) . '/src/Controllers';
            $namespace = 'HexaGen\\Controllers';
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = "{$dir}/{$name}.php";
        if (file_exists($file)) {
            $io->error("{$name} already exists.");
            return Command::FAILURE;
        }

        $body = $input->getOption('resource')
            ? $this->resourceBody($name, $namespace)
            : $this->basicBody($name, $namespace);

        file_put_contents($file, $body);

        $location = $slice ? "src/Slices/{$slice}/Controllers/{$name}.php" : "src/Controllers/{$name}.php";
        $io->success("Controller created: {$location}");

        return Command::SUCCESS;
    }

    private function basicBody(string $name, string $namespace): string
    {
        return <<<PHP
<?php
namespace {$namespace};

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class {$name}
{
    public function index(Request \$request): JsonResponse
    {
        return new JsonResponse(['data' => []]);
    }
}
PHP;
    }

    private function resourceBody(string $name, string $namespace): string
    {
        return <<<PHP
<?php
namespace {$namespace};

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class {$name}
{
    public function index(Request \$request): JsonResponse
    {
        return new JsonResponse(['data' => []]);
    }

    public function store(Request \$request): JsonResponse
    {
        return new JsonResponse(['data' => []], 201);
    }

    public function show(Request \$request, int \$id): JsonResponse
    {
        return new JsonResponse(['data' => ['id' => \$id]]);
    }

    public function update(Request \$request, int \$id): JsonResponse
    {
        return new JsonResponse(['data' => ['id' => \$id]]);
    }

    public function destroy(Request \$request, int \$id): Response
    {
        return new Response('', 204);
    }
}
PHP;
    }
}
