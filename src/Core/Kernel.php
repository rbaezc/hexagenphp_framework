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

    /** @var string[] Global middleware classes applied to every HTTP request. */
    private array $globalMiddlewares = [
        \HexaGen\Core\Middleware\MaintenanceMiddleware::class,
        \HexaGen\Core\Middleware\TelemetryMiddleware::class,
        \HexaGen\Core\Middleware\CorsMiddleware::class,
        \HexaGen\Core\Middleware\SecurityHeadersMiddleware::class,
    ];

    /** @var \HexaGen\Core\Exceptions\ExceptionHandlerInterface|null Custom exception handler. */
    private ?\HexaGen\Core\Exceptions\ExceptionHandlerInterface $exceptionHandler = null;

    /** @var string[] Provider class names registered before boot (app-level). */
    private array $pendingProviders = [];

    /** @var ServiceProvider[] Instantiated providers, available after boot. */
    private array $providers = [];

    /** @var array<string, string> Service ID => deferred provider class. */
    private array $deferredServices = [];

    /** @var UrlMatcher|null Cached matcher — routes don't change after boot(). */
    private ?UrlMatcher $matcher = null;

    /** @var array<string, \ReflectionMethod> Cached reflections keyed by "Class::method". */
    private static array $reflectionCache = [];

    /** @var array<string, object> Cached middleware singleton instances. */
    private array $middlewareInstances = [];

    /** @var array<string, string[]> Named middleware groups, e.g. 'web', 'api'. */
    private array $middlewareGroups = [
        'web' => [
            \HexaGen\Core\Middleware\CsrfMiddleware::class,
        ],
        'api' => [
            \HexaGen\Core\Middleware\RateLimitMiddleware::class,
        ],
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
     * Register a middleware class in the global pipeline.
     * Must be called before boot(). Duplicates are ignored.
     */
    public function addMiddleware(string $middlewareClass): void
    {
        if (!in_array($middlewareClass, $this->globalMiddlewares, true)) {
            $this->globalMiddlewares[] = $middlewareClass;
        }
    }

    /**
     * Register a ServiceProvider to be loaded during boot().
     * Must be called before boot(). Duplicates are ignored.
     *
     * @throws \InvalidArgumentException If the class does not extend ServiceProvider.
     */
    public function addProvider(string $providerClass): void
    {
        if (!is_subclass_of($providerClass, ServiceProvider::class)) {
            throw new \InvalidArgumentException(
                "$providerClass must extend " . ServiceProvider::class
            );
        }
        if (!in_array($providerClass, $this->pendingProviders, true)) {
            $this->pendingProviders[] = $providerClass;
        }
    }

    /**
     * Return all console commands contributed by registered providers.
     * Called by the hexaphp CLI after boot().
     */
    private \HexaGen\Core\Container\ContextualBindingRegistry $contextualBindings;

    public function when(string $concrete): \HexaGen\Core\Container\ContextualBindingBuilder
    {
        if (!isset($this->contextualBindings)) {
            $this->contextualBindings = new \HexaGen\Core\Container\ContextualBindingRegistry();
        }
        return $this->contextualBindings->when($concrete);
    }

    public function setExceptionHandler(\HexaGen\Core\Exceptions\ExceptionHandlerInterface $handler): void
    {
        $this->exceptionHandler = $handler;
    }

    public function middlewareGroup(string $name, array $classes): void
    {
        $this->middlewareGroups[$name] = $classes;
    }

    public function getMiddlewareGroup(string $name): ?array
    {
        return $this->middlewareGroups[$name] ?? null;
    }

    public function addToMiddlewareGroup(string $group, string $middlewareClass): void
    {
        $this->middlewareGroups[$group][] = $middlewareClass;
    }

    public function getProviderCommands(): array
    {
        $commands = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->commands() as $commandClass) {
                $commands[] = new $commandClass();
            }
        }
        return $commands;
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

        // Validate required environment variables before doing anything else
        \HexaGen\Core\Bootstrap\EnvironmentValidator::fromConfig();

        // Register core services
        $this->registerCoreServices();

        // Wire Route facade to this kernel's RouteCollection
        \HexaGen\Core\Routing\Route::setCollection($this->routes);

        // Auto-discover providers from installed Composer packages (extra.hexagen.providers)
        // These are prepended so app-level providers (added via addProvider) register last and can override.
        $this->discoverProviders();

        // Phase 1 — instantiate all providers and call register() before container compilation.
        foreach ($this->pendingProviders as $providerClass) {
            $provider = new $providerClass($this);

            // Deferred providers: record their services, skip instantiation until needed
            if ($provider->deferred) {
                foreach ($provider->provides() as $serviceId) {
                    $this->deferredServices[$serviceId] = $providerClass;
                }
                continue;
            }

            $provider->register();
            // Add provider-contributed middlewares to the global pipeline
            foreach ($provider->middlewares() as $middlewareClass) {
                $this->addMiddleware($middlewareClass);
            }
            $this->providers[] = $provider;
        }

        // Built-in health check endpoint
        $this->routes->add('_health', new \Symfony\Component\Routing\Route('/_health', [
            '_controller' => [\HexaGen\Core\Http\HealthController::class, '__invoke'],
        ], [], [], '', [], ['GET']));

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

        // Phase 2 — boot all providers after the container is compiled and services are resolvable.
        foreach ($this->providers as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }

    /**
     * Scan vendor/composer/installed.json for packages that declare
     * HexaGen providers under extra.hexagen.providers.
     * Discovered providers are prepended so app providers can override them.
     */
    private function discoverProviders(): void
    {
        $installedJson = dirname(__DIR__, 2) . '/vendor/composer/installed.json';
        if (!file_exists($installedJson)) {
            return;
        }

        $installed = json_decode(file_get_contents($installedJson), true);
        $packages  = $installed['packages'] ?? $installed ?? [];

        $discovered = [];
        foreach ($packages as $package) {
            $providers = $package['extra']['hexagen']['providers'] ?? [];
            foreach ($providers as $providerClass) {
                if (
                    class_exists($providerClass)
                    && is_subclass_of($providerClass, ServiceProvider::class)
                    && !in_array($providerClass, $this->pendingProviders, true)
                    && !in_array($providerClass, $discovered, true)
                ) {
                    $discovered[] = $providerClass;
                }
            }
        }

        // Discovered package providers run first; app providers (manual) run last and can override
        $this->pendingProviders = array_merge($discovered, $this->pendingProviders);
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

        // Make current request available globally (request() helper, AuthManager)
        \HexaGen\Core\CurrentRequest::set($request);
        \HexaGen\Core\Auth\AuthManager::setRequest($request);

        $context = new RequestContext();
        $context->fromRequest($request);

        if ($this->matcher === null) {
            $this->matcher = new UrlMatcher($this->routes, $context);
        } else {
            $this->matcher->setContext($context);
        }
        $matcher = $this->matcher;
        
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
                    $cacheKey  = get_class($controller) . '::' . $method;
                    $reflection = static::$reflectionCache[$cacheKey]
                        ??= new \ReflectionMethod($controller, $method);
                    foreach ($reflection->getParameters() as $param) {
                        $name = $param->getName();
                        $type = $param->getType();
                        $typeClass = ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) ? $type->getName() : null;

                        if ($typeClass && is_a($typeClass, Request::class, true)) {
                            $arguments[] = $request;
                        } elseif ($name === 'request') {
                            $arguments[] = $request;
                        } elseif ($typeClass && is_subclass_of($typeClass, \HexaGen\Core\Http\FormRequest::class)) {
                            // Form request resolution
                            $formRequest = new $typeClass($request);
                            if (!$formRequest->authorize()) {
                                throw new \HexaGen\Core\Auth\AuthorizationException('This action is unauthorized.');
                            }
                            $formRequest->validate();
                            $arguments[] = $formRequest;
                        } elseif ($typeClass && is_subclass_of($typeClass, \HexaGen\Core\Database\Model::class)) {
                            // Route model binding
                            $routeKey = isset($parameters[$name]) ? $name : null;
                            if ($routeKey === null) {
                                foreach ($parameters as $k => $v) {
                                    if (!str_starts_with($k, '_')) { $routeKey = $k; break; }
                                }
                            }
                            $routeValue = $routeKey ? ($parameters[$routeKey] ?? null) : null;
                            if ($routeValue !== null) {
                                $keyName = method_exists($typeClass, 'getRouteKeyName')
                                    ? (new $typeClass)->getRouteKeyName()
                                    : 'id';
                                $model = $typeClass::query()->where($keyName, $routeValue)->first();
                                if ($model === null) {
                                    return new Response("Model not found", 404);
                                }
                                $arguments[] = $model;
                            } else {
                                $arguments[] = null;
                            }
                        } elseif (isset($parameters[$name])) {
                            $arguments[] = $parameters[$name];
                        } elseif ($param->isDefaultValueAvailable()) {
                            $arguments[] = $param->getDefaultValue();
                        } else {
                            if ($typeClass) {
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

            // 3. Collect and merge global + route-specific middlewares (resolve groups)
            $routeMiddlewares = $parameters['_middleware'] ?? [];
            if (!is_array($routeMiddlewares)) {
                $routeMiddlewares = [$routeMiddlewares];
            }
            $routeMiddlewares = $this->resolveMiddlewareGroups($routeMiddlewares);
            $middlewares = array_merge($this->globalMiddlewares, $routeMiddlewares);

            // 4. Run Request through onion-style middleware pipeline
            return $this->runPipeline($request, $middlewares, $destination);
            
        } catch (\HexaGen\Core\Validation\ValidationException $e) {
            return new JsonResponse([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors'  => $e->getErrors(),
            ], 422);
        } catch (\HexaGen\Core\Auth\AuthorizationException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 403);
        } catch (ResourceNotFoundException $e) {
            return new Response('Not Found', 404);
        } catch (\Throwable $e) {
            $handler = $this->exceptionHandler ?? new \HexaGen\Core\Exceptions\Handler();
            $handler->report($e);
            return $handler->render($request, $e);
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
                    $middleware = $this->middlewareInstances[$middlewareClass]
                        ??= ($this->container->has($middlewareClass)
                            ? $this->container->get($middlewareClass)
                            : new $middlewareClass());
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
    /** @var array<string, \ReflectionClass<object>> */
    private static array $classReflectionCache = [];

    private function instantiateController(string $class): object
    {
        $reflection  = static::$classReflectionCache[$class] ??= new \ReflectionClass($class);
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
    
    public function loadDeferredProvider(string $serviceId): void
    {
        if (!isset($this->deferredServices[$serviceId])) {
            return;
        }
        $providerClass = $this->deferredServices[$serviceId];
        unset($this->deferredServices[$serviceId]);

        $provider = new $providerClass($this);
        $provider->register();
        $provider->boot();
        $this->providers[] = $provider;
    }

    private function resolveMiddlewareGroups(array $middlewares): array
    {
        $resolved = [];
        foreach ($middlewares as $middleware) {
            if (isset($this->middlewareGroups[$middleware])) {
                $resolved = array_merge($resolved, $this->middlewareGroups[$middleware]);
            } else {
                $resolved[] = $middleware;
            }
        }
        return $resolved;
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

        // 3. Instantiate and hydrate component from trusted (encrypted) state
        $instance = new $componentClass();
        $instance->hydrate($state);

        // 4. Two-way data binding: hydrate only user-exposed fields from request input
        $inputs = array_merge($request->query->all(), $request->request->all());
        unset($inputs['state']); // Exclude state payload itself
        $instance->hydrateFromInput($inputs);

        // 5. Execute action — restrict to methods declared on the concrete class only,
        //    preventing callers from invoking internal LiveComponent methods (e.g. getSignedState).
        $baseMethods = get_class_methods(\HexaGen\Core\Live\LiveComponent::class);
        if (in_array($action, $baseMethods, true) || !method_exists($instance, $action)) {
            return new Response('Action Not Found', 404);
        }

        $instance->$action();

        // 6. Render component and return HTML response
        $html = $instance->render();
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Terminate the request lifecycle and perform post-response cleanups.
     * In FrankenPHP worker mode this runs after each request to release resources.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Release session lock so the next worker request isn't blocked
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // CRITICAL: Reset per-request state so worker mode never leaks across requests.
        \HexaGen\Core\Auth\AuthManager::reset();
        \HexaGen\Core\CurrentRequest::reset();

        // Reset telemetry trace state for next request in worker mode
        \HexaGen\Core\Observability\Telemetry::reset();
    }
}
