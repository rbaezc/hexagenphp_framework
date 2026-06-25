<?php
namespace HexaGen\Core\Container;

class ContextualBindingRegistry
{
    private static array $bindings = [];

    public function bind(string $concrete, string $abstract, string|\Closure $implementation): void
    {
        static::$bindings[$concrete][$abstract] = $implementation;
    }

    public static function resolve(string $concrete, string $abstract): string|\Closure|null
    {
        return static::$bindings[$concrete][$abstract] ?? null;
    }

    public function when(string $concrete): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, $concrete);
    }

    public static function flush(): void
    {
        static::$bindings = [];
    }
}
