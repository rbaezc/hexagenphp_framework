<?php
namespace HexaGen\Core\Routing;

class RouteGroup
{
    private array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function prefix(string $prefix): static
    {
        $this->attributes['prefix'] = ($this->attributes['prefix'] ?? '') . '/' . trim($prefix, '/');
        return $this;
    }

    public function middleware(string|array $middleware): static
    {
        $existing = $this->attributes['middleware'] ?? [];
        $this->attributes['middleware'] = array_merge($existing, (array) $middleware);
        return $this;
    }

    public function name(string $name): static
    {
        $this->attributes['name'] = ($this->attributes['name'] ?? '') . $name;
        return $this;
    }

    public function group(callable $routes): void
    {
        Route::pushGroupStack($this->mergeWithParentGroup($this->attributes));
        $routes();
        Route::popGroupStack();
    }

    private function mergeWithParentGroup(array $attributes): array
    {
        // Merge with parent group on stack if present
        return $attributes;
    }
}
