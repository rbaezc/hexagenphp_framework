<?php
namespace HexaGen\Core;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use HexaGen\Core\Database\DatabaseConnection;
use HexaGen\Core\Database\Model;

class Kernel
{
    private static ?self $instance = null;
    private ContainerBuilder $container;
    private RouteCollection $routes;
    private bool $booted = false;

    /**
     * Array of global middlewares executed for all HTTP requests.
     */
    private array $globalMiddlewares = [
        \HexaGen\Core\Middleware\CorsMiddleware::class,
    ];

    public function __construct()
    {
        $this->container = new ContainerBuilder();
        $this->routes = new RouteCollection();
        self::$instance = $this;
    }

    /**
     * Get active booted Kernel instance.
     */
    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    /**
     * Boots the application kernel, registers core services,
     * scans vertical slices, and compiles the DI container.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Register core services
        $this->registerCoreServices();

        // Register Live Slices global endpoint (maps events to handleLiveRequest)
        $this->routes->add('live_component_action', new \Symfony\Component\Routing\Route('/live/{component}/{action}', [
            '_controller' => [$this, 'handleLiveRequest']
        ], [], [], '', [], ['POST', 'GET']));

        // Load vertical slices
        $this->loadSlices();

        // Compile DI container
        $this->container->compile();

        // Connect HexaORM to DatabaseConnection service
        $connection = $this->container->get(DatabaseConnection::class);
        Model::setConnection($connection);

        $this->booted = true;
    }

    /**
     * Register core framework services.
     */
    private function registerCoreServices(): void
    {
        $this->container->register(DatabaseConnection::class, DatabaseConnection::class)
            ->setPublic(true);

        $this->container->register(\HexaGen\Core\Template\TemplateEngine::class, \HexaGen\Core\Template\TemplateEngine::class)
            ->setPublic(true);
            
        $this->container->set(self::class, $this);
    }

    /**
     * Scan the Slices directory and register routes and services.
     */
    private function loadSlices(): void
    {
        $slicesDir = dirname(__DIR__) . '/Slices';
        if (!is_dir($slicesDir)) {
            return;
        }

        $dirs = glob($slicesDir . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            // Load Routes
            $routesFile = $dir . '/Routes.php';
            if (file_exists($routesFile)) {
                $routesLoader = require $routesFile;
                if (is_callable($routesLoader)) {
                    $routesLoader($this->routes);
                }
            }

            // Load Services
            $servicesFile = $dir . '/Services.php';
            if (file_exists($servicesFile)) {
                $servicesLoader = require $servicesFile;
                if (is_callable($servicesLoader)) {
                    $servicesLoader($this->container);
                }
            }
        }
    }

    /**
     * Handle incoming HTTP or gRPC requests.
     */
    public function handle(Request $request): Response
    {
        // 1. gRPC Request interception
        $contentType = $request->headers->get('Content-Type') ?? '';
        if (str_starts_with($contentType, 'application/grpc')) {
            return $this->handleGrpc($request);
        }

        $context = new RequestContext();
        $context->fromRequest($request);
        
        $matcher = new UrlMatcher($this->routes, $context);
        
        try {
            $parameters = $matcher->match($request->getPathInfo());
            $request->attributes->add($parameters);
            
            $controllerAttr = $parameters['_controller'] ?? null;
            if (!$controllerAttr) {
                return new Response('Not Found', 404);
            }

            // 2. Define the destination callback for the middleware pipeline
            $destination = function (Request $request) use ($controllerAttr, $parameters): Response {
                if (is_array($controllerAttr)) {
                    [$class, $method] = $controllerAttr;
                    
                    if (is_object($class)) {
                        $controller = $class;
                    } else {
                        if ($this->container->has($class)) {
                            $controller = $this->container->get($class);
                        } else {
                            $controller = $this->instantiateController($class);
                        }
                    }
                    
                    if (!method_exists($controller, $method)) {
                        throw new \RuntimeException(sprintf('Method "%s" not found on controller "%s"', $method, is_object($class) ? get_class($class) : $class));
                    }
                    
                    $arguments = [];
                    $reflection = new \ReflectionMethod($controller, $method);
                    foreach ($reflection->getParameters() as $param) {
                        $name = $param->getName();
                        if ($name === 'request') {
                            $arguments[] = $request;
                        } elseif (isset($parameters[$name])) {
                            $arguments[] = $parameters[$name];
                        } elseif ($param->isDefaultValueAvailable()) {
                            $arguments[] = $param->getDefaultValue();
                        } else {
                            $type = $param->getType();
                            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                                $typeClass = $type->getName();
                                if ($this->container->has($typeClass)) {
                                    $arguments[] = $this->container->get($typeClass);
                                    continue;
                                }
                            }
                            $arguments[] = null;
                        }
                    }
                    
                    $response = call_user_func_array([$controller, $method], $arguments);
                    
                    if ($response instanceof Response) {
                        return $response;
                    }
                    
                    if (is_array($response) || is_object($response)) {
                        return new JsonResponse($response);
                    }
                    
                    return new Response((string)$response);
                }
                
                if (is_callable($controllerAttr)) {
                    $response = call_user_func($controllerAttr, $request);
                    return $response instanceof Response ? $response : new Response((string)$response);
                }
                
                return new Response('Invalid controller specification', 500);
            };

            // 3. Collect and merge global + route-specific middlewares
            $routeMiddlewares = $parameters['_middleware'] ?? [];
            if (!is_array($routeMiddlewares)) {
                $routeMiddlewares = [$routeMiddlewares];
            }
            
            $middlewares = array_merge($this->globalMiddlewares, $routeMiddlewares);

            // 4. Run Request through onion-style middleware pipeline
            return $this->runPipeline($request, $middlewares, $destination);
            
        } catch (\HexaGen\Core\Validation\ValidationException $e) {
            // Laravel-style validation response
            return new JsonResponse([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $e->getErrors()
            ], 422);
        } catch (ResourceNotFoundException $e) {
            return new Response('Not Found', 404);
        } catch (\Throwable $e) {
            return new Response(
                'Internal Server Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                500,
                ['Content-Type' => 'text/plain']
            );
        }
    }

    /**
     * Onion-style functional middleware pipeline executor.
     */
    private function runPipeline(Request $request, array $middlewares, callable $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($middlewares),
            function ($next, $middlewareClass) {
                return function (Request $request) use ($next, $middlewareClass): Response {
                    if ($this->container->has($middlewareClass)) {
                        $middleware = $this->container->get($middlewareClass);
                    } else {
                        $middleware = new $middlewareClass();
                    }
                    return $middleware->handle($request, $next);
                };
            },
            $destination
        );

        return $pipeline($request);
    }

    /**
     * Native, pure-PHP gRPC router implementation.
     */
    private function handleGrpc(Request $request): Response
    {
        $path = trim($request->getPathInfo(), '/');
        $parts = explode('/', $path);
        if (count($parts) !== 2) {
            return new Response('Invalid gRPC Path', 400, ['grpc-status' => '3']); // INVALID_ARGUMENT
        }
        
        [$service, $method] = $parts;
        
        // Resolve gRPC Service controller (global or slice-specific)
        $serviceClass = "HexaGen\\Grpc\\" . $service;
        if (!$this->container->has($serviceClass)) {
            $sliceName = explode('.', $service)[0] ?? $service;
            $serviceClass = "HexaGen\\Slices\\" . ucfirst($sliceName) . "\\Controller\\" . ucfirst($sliceName) . "GrpcController";
        }
        
        if (!$this->container->has($serviceClass) && !class_exists($serviceClass)) {
            return new Response('gRPC Service Not Found', 404, [
                'content-type' => 'application/grpc',
                'grpc-status' => '12' // UNIMPLEMENTED
            ]);
        }
        
        $instance = $this->container->has($serviceClass) 
            ? $this->container->get($serviceClass) 
            : new $serviceClass();
            
        if (!method_exists($instance, $method)) {
            return new Response('gRPC Method Not Found', 404, [
                'content-type' => 'application/grpc',
                'grpc-status' => '12' // UNIMPLEMENTED
            ]);
        }
        
        // Read binary protobuf payload from request body
        $rawPayload = $request->getContent();
        
        try {
            $result = $instance->$method($rawPayload);
            
            // Format gRPC frame (1 byte zero status + 4 bytes big-endian length + payload)
            $responseBody = pack('C', 0) . pack('N', strlen($result)) . $result;
            
            return new Response($responseBody, 200, [
                'Content-Type' => 'application/grpc',
                'grpc-status' => '0', // OK
                'grpc-message' => 'OK'
            ]);
        } catch (\Throwable $e) {
            return new Response('', 200, [
                'Content-Type' => 'application/grpc',
                'grpc-status' => '13', // INTERNAL
                'grpc-message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Instantiate controller class, injecting constructor parameters from DI container.
     */
    private function instantiateController(string $class): object
    {
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return new $class();
        }
        
        $arguments = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeClass = $type->getName();
                if ($this->container->has($typeClass)) {
                    $arguments[] = $this->container->get($typeClass);
                    continue;
                }
            }
            
            if ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException(sprintf('Cannot auto-wire parameter "%s" for controller "%s" constructor', $param->getName(), $class));
            }
        }
        
        return $reflection->newInstanceArgs($arguments);
    }
    
    public function getContainer(): ContainerBuilder
    {
        return $this->container;
    }
    
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Handle reactive event requests for Live Slices.
     */
    public function handleLiveRequest(Request $request, string $component, string $action): Response
    {
        // 1. Locate the component class by scanning Slices
        $componentClass = null;
        $slicesDir = dirname(__DIR__) . '/Slices';
        if (is_dir($slicesDir)) {
            $dirs = glob($slicesDir . '/*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $possibleClass = "HexaGen\\Slices\\" . basename($dir) . "\\Components\\" . $component;
                if (class_exists($possibleClass)) {
                    $componentClass = $possibleClass;
                    break;
                }
            }
        }

        if (!$componentClass) {
            return new Response('Component Not Found: ' . $component, 404);
        }

        // 2. Decrypt and hydrate state payload
        $stateToken = $request->headers->get('X-Live-State') 
            ?: $request->request->get('state') 
            ?: $request->query->get('state');

        if (!$stateToken) {
            return new Response('Missing Live Component State Token', 400);
        }

        $state = \HexaGen\Core\Live\LiveComponent::decryptState($stateToken);
        if ($state === null) {
            return new Response('Invalid or tampered Live Component State Token', 403);
        }

        // 3. Instantiate and hydrate component
        $instance = new $componentClass();
        $instance->hydrate($state);

        // 4. Two-way data binding: hydrate form fields sent in request
        $inputs = array_merge($request->query->all(), $request->request->all());
        unset($inputs['state']); // Exclude state itself
        $instance->hydrate($inputs);

        // 5. Execute action method
        if (!method_exists($instance, $action)) {
            return new Response(sprintf('Action Method "%s" Not Found on component "%s"', $action, $component), 404);
        }

        $instance->$action();

        // 6. Render component and return HTML response
        $html = $instance->render();
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Terminate the request lifecycle and perform post-response cleanups.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Custom cleanup logic can be added here (e.g. closing resources, event dispatching)
        // For example, in worker mode, this is where we clear state if needed.
    }
}
