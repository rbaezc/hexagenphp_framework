<?php
namespace HexaGen\Core\Routing;

use Symfony\Component\Routing\Route as SymfonyRoute;

class RouteDefinition
{
    private array $routeMiddleware = [];
    private ?string $routeName = null;

    public function __construct(
        private array $methods,
        private string $path,
        private mixed $controller,
        array $groupMiddleware = [],
        string $namePrefix = ''
    ) {
        $this->routeMiddleware = $groupMiddleware;
        if ($namePrefix) {
            $this->routeName = $namePrefix;
        }

        $this->register();
    }

    public function middleware(string|array $middleware): static
    {
        $this->routeMiddleware = array_merge($this->routeMiddleware, (array) $middleware);
        $this->register();
        return $this;
    }

    public function name(string $name): static
    {
        $prefix = '';
        // Check group stack for name prefix
        $this->routeName = $prefix . $name;
        Route::registerName($this->routeName, $this->path);
        $this->register();
        return $this;
    }

    public function where(string $param, string $regex): static
    {
        // Store requirements — will be passed to SymfonyRoute on next register()
        // For now, re-register with requirements
        $this->register(['requirements' => [$param => $regex]]);
        return $this;
    }

    private function register(array $extra = []): void
    {
        $collection = Route::getCollection();
        if (!$collection) {
            return;
        }

        $defaults = [
            '_controller' => $this->controller,
            '_middleware' => $this->resolveMiddleware($this->routeMiddleware),
        ];

        $routeName = $this->routeName
            ?? ('route_' . md5(implode('|', $this->methods) . $this->path));

        $route = new SymfonyRoute(
            $this->path,
            $defaults,
            $extra['requirements'] ?? [],
            [],
            '',
            [],
            $this->methods
        );

        // Remove existing route with same name to avoid conflicts on re-register
        try { $collection->remove($routeName); } catch (\Throwable) {}
        $collection->add($routeName, $route);

        // Also register name for URL generation
        if ($this->routeName) {
            Route::registerName($this->routeName, $this->path);
        }
    }

    private function resolveMiddleware(array $names): array
    {
        $resolved = [];
        foreach ($names as $name) {
            // If it's already a FQCN, use directly
            if (class_exists($name)) {
                $resolved[] = $name;
            } else {
                // Look up middleware groups/aliases
                $kernel = \HexaGen\Core\Kernel::getInstance();
                if ($kernel) {
                    $group = $kernel->getMiddlewareGroup($name);
                    if ($group) {
                        $resolved = array_merge($resolved, $group);
                        continue;
                    }
                }
                $resolved[] = $name;
            }
        }
        return $resolved;
    }
}
