<?php
namespace HexaGen\Core\Routing;

use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

class Route
{
    private static ?RouteCollection $collection = null;
    private static array $namedRoutes = [];

    // Group context stack (allows nested groups)
    private static array $groupStack = [];

    public static function setCollection(RouteCollection $collection): void
    {
        static::$collection = $collection;
    }

    public static function getCollection(): ?RouteCollection
    {
        return static::$collection;
    }

    // ── Registration ────────────────────────────────────────────────────────────

    public static function get(string $path, mixed $controller): RouteDefinition
    {
        return static::addRoute(['GET', 'HEAD'], $path, $controller);
    }

    public static function post(string $path, mixed $controller): RouteDefinition
    {
        return static::addRoute(['POST'], $path, $controller);
    }

    public static function put(string $path, mixed $controller): RouteDefinition
    {
        return static::addRoute(['PUT'], $path, $controller);
    }

    public static function patch(string $path, mixed $controller): RouteDefinition
    {
        return static::addRoute(['PATCH'], $path, $controller);
    }

    public static function delete(string $path, mixed $controller): RouteDefinition
    {
        return static::addRoute(['DELETE'], $path, $controller);
    }

    public static function options(string $path, mixed $controller): RouteDefinition
    {
        return static::addRoute(['OPTIONS'], $path, $controller);
    }

    public static function any(string $path, mixed $controller): RouteDefinition
    {
        return static::addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'], $path, $controller);
    }

    public static function match(array $methods, string $path, mixed $controller): RouteDefinition
    {
        return static::addRoute(array_map('strtoupper', $methods), $path, $controller);
    }

    private static function addRoute(array $methods, string $path, mixed $controller): RouteDefinition
    {
        $group = empty(static::$groupStack) ? [] : end(static::$groupStack);

        $prefix     = $group['prefix']     ?? '';
        $middleware = $group['middleware'] ?? [];
        $namePrefix = $group['name']       ?? '';

        $fullPath = $prefix ? '/' . trim($prefix, '/') . '/' . ltrim($path, '/') : $path;
        $fullPath = '/' . ltrim($fullPath, '/');

        // Remove trailing slash unless it's the root
        if ($fullPath !== '/' && str_ends_with($fullPath, '/')) {
            $fullPath = rtrim($fullPath, '/');
        }

        return new RouteDefinition($methods, $fullPath, $controller, $middleware, $namePrefix);
    }

    // ── Groups ───────────────────────────────────────────────────────────────────

    public static function prefix(string $prefix): RouteGroup
    {
        return new RouteGroup(['prefix' => $prefix]);
    }

    public static function middleware(string|array $middleware): RouteGroup
    {
        return new RouteGroup(['middleware' => (array) $middleware]);
    }

    public static function name(string $name): RouteGroup
    {
        return new RouteGroup(['name' => $name]);
    }

    public static function group(array $attributes, callable $routes): void
    {
        static::$groupStack[] = $attributes;
        $routes();
        array_pop(static::$groupStack);
    }

    // ── URL Generation ────────────────────────────────────────────────────────────

    public static function registerName(string $name, string $path): void
    {
        static::$namedRoutes[$name] = $path;
    }

    public static function url(string $name, array $parameters = []): string
    {
        if (!isset(static::$namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route [{$name}] not defined.");
        }

        $path = static::$namedRoutes[$name];

        // Replace {param} and {param?} placeholders
        foreach ($parameters as $key => $value) {
            $path = preg_replace('/\{' . preg_quote($key, '/') . '\??\}/', (string) $value, $path);
        }

        // Remove unfilled optional params
        $path = preg_replace('/\/\{[^}]+\?\}/', '', $path);

        $base = rtrim((string) \HexaGen\Core\Config::get('app.url', ''), '/');
        return $base . $path;
    }

    public static function has(string $name): bool
    {
        return isset(static::$namedRoutes[$name]);
    }

    // ── Resource routes (CRUD) ────────────────────────────────────────────────────

    public static function resource(string $name, string $controller, array $only = []): void
    {
        $resourceRoutes = [
            'index'   => ['GET',    "/{$name}",             'index'],
            'create'  => ['GET',    "/{$name}/create",      'create'],
            'store'   => ['POST',   "/{$name}",             'store'],
            'show'    => ['GET',    "/{$name}/{id}",        'show'],
            'edit'    => ['GET',    "/{$name}/{id}/edit",   'edit'],
            'update'  => ['PUT',    "/{$name}/{id}",        'update'],
            'destroy' => ['DELETE', "/{$name}/{id}",        'destroy'],
        ];

        foreach ($resourceRoutes as $action => [$method, $path, $controllerMethod]) {
            if ($only && !in_array($action, $only, true)) {
                continue;
            }
            static::addRoute([$method], $path, [$controller, $controllerMethod])
                ->name("{$name}.{$action}");
        }
    }

    public static function apiResource(string $name, string $controller, array $only = []): void
    {
        static::resource($name, $controller, $only ?: ['index', 'store', 'show', 'update', 'destroy']);
    }

    public static function pushGroupStack(array $attributes): void
    {
        static::$groupStack[] = $attributes;
    }

    public static function popGroupStack(): void
    {
        array_pop(static::$groupStack);
    }
}
