<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeSliceCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('make:slice')
            ->setDescription('Genera la estructura de un Slice vertical limpio.')
            ->addArgument('name', InputArgument::REQUIRED, 'El nombre del Slice (ej. Vuelos)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        
        // Spanish singularizer for Model name
        $modelName = $name;
        if (str_ends_with($name, 'es')) {
            $modelName = substr($name, 0, -2);
        } elseif (str_ends_with($name, 's')) {
            $modelName = substr($name, 0, -1);
        }

        $projectDir = dirname(dirname(dirname(__DIR__)));
        $slicesDir = $projectDir . '/src/Slices/' . $name;

        if (is_dir($slicesDir)) {
            $io->error(sprintf('El Slice "%s" ya existe en %s', $name, $slicesDir));
            return Command::FAILURE;
        }

        $io->info(sprintf('Creando Slice "%s" en %s...', $name, $slicesDir));

        // Create directory structure
        $folders = [
            $slicesDir,
            $slicesDir . '/Controller',
            $slicesDir . '/Domain',
        ];

        foreach ($folders as $folder) {
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
        }

        // 1. Controller Template
        $controllerContent = <<<PHP
<?php
namespace HexaGen\Slices\\{$name}\Controller;

use HexaGen\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use HexaGen\Slices\\{$name}\Domain\\{$modelName};

class {$name}Controller extends AbstractController
{
    public function index(Request \$request): Response
    {
        return \$this->json([
            'message' => '¡Hola desde el Slice vertical de {$name}!',
            'items' => {$modelName}::all()
        ]);
    }
    
    public function create(Request \$request): Response
    {
        \$item = new {$modelName}();
        \$item->name = \$request->request->get('name', 'Elemento de prueba ' . rand(1, 100));
        \$item->created_at = date('Y-m-d H:i:s');
        \$item->save();
        
        return \$this->json([
            'success' => true,
            'message' => 'Registro insertado usando HexaORM en la tabla de {$name}',
            'item' => \$item
        ], 201);
    }
}
PHP;
        file_put_contents($slicesDir . '/Controller/' . $name . 'Controller.php', $controllerContent);

        // 2. Domain/Model Template
        $modelContent = <<<PHP
<?php
namespace HexaGen\Slices\\{$name}\Domain;

use HexaGen\Core\Database\Model;

class {$modelName} extends Model
{
    // Por defecto, la tabla será mapeada según las reglas de pluralización (ej. vuelos, hoteles)
    // protected static string \$table = 'custom_table';
    
    public ?int \$id = null;
    public ?string \$name = null;
    public ?string \$created_at = null;
}
PHP;
        file_put_contents($slicesDir . '/Domain/' . $modelName . '.php', $modelContent);

        // 3. Routes Template
        $routesContent = <<<PHP
<?php
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use HexaGen\Slices\\{$name}\Controller\\{$name}Controller;

return function (RouteCollection \$routes) {
    // GET /{$name} -> list
    \$routes->add('{$name}_index', new Route('/' . strtolower('{$name}'), [
        '_controller' => [{$name}Controller::class, 'index']
    ], [], [], '', [], ['GET']));

    // POST /{$name}/create -> create
    \$routes->add('{$name}_create', new Route('/' . strtolower('{$name}') . '/create', [
        '_controller' => [{$name}Controller::class, 'create']
    ], [], [], '', [], ['POST', 'GET'])); // GET supported for easy browser test
};
PHP;
        file_put_contents($slicesDir . '/Routes.php', $routesContent);

        // 4. Services Template
        $servicesContent = <<<PHP
<?php
use Symfony\Component\DependencyInjection\ContainerBuilder;

return function (ContainerBuilder \$container) {
    // Define dependencias específicas del slice aquí
    // \$container->register(MySliceService::class, MySliceService::class);
};
PHP;
        file_put_contents($slicesDir . '/Services.php', $servicesContent);

        // 5. Automatic SQLite Table Creation (Developer Convenience)
        try {
            $dbConn = new \HexaGen\Core\Database\DatabaseConnection();
            $pdo = $dbConn->getPdo();

            // Resolve same pluralized table name
            $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName));
            if (preg_match('/[bcdfghjklmnpqrstvwxz]$/i', $tableName)) {
                $tableName .= 'es';
            } else {
                $tableName .= 's';
            }

            // Validate identifier before using in DDL (only alphanumeric + underscore allowed)
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
                $io->warning("Nombre de tabla inválido '$tableName'. Se omite la creación automática de tabla.");
            } else {
                // Use prepared statement for the SELECT check
                $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:name");
                $stmt->execute([':name' => $tableName]);
                if (!$stmt->fetch()) {
                    // DDL cannot use prepared statements, but identifier is validated above
                    $pdo->exec("CREATE TABLE `$tableName` (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name TEXT NOT NULL,
                        created_at TEXT NOT NULL
                    )");
                    $io->info("Base de datos SQLite: Creada la tabla '$tableName' para el Slice.");
                }
            }
        } catch (\Throwable $e) {
            // Ignore database connection issues (e.g. if DB is not SQLite or credentials not set yet)
        }

        $io->success(sprintf('Slice vertical "%s" generado exitosamente (Modelo singularizado como "%s").', $name, $modelName));
        return Command::SUCCESS;
    }
}
