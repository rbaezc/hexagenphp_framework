<?php
namespace HexaGen\Core\Support\Traits;

use Closure;
use BadMethodCallException;

trait Macroable
{
    protected static array $macros = [];

    public static function macro(string $name, callable $macro): void
    {
        static::$macros[$name] = $macro;
    }

    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    public static function flushMacros(): void
    {
        static::$macros = [];
    }

    public function __call(string $method, array $args): mixed
    {
        if (!isset(static::$macros[$method])) {
            throw new BadMethodCallException("Method {$method} does not exist on " . static::class);
        }
        $macro = static::$macros[$method];
        if ($macro instanceof Closure) {
            return Closure::bind($macro, $this, static::class)(...$args);
        }
        return $macro(...$args);
    }

    public static function __callStatic(string $method, array $args): mixed
    {
        if (!isset(static::$macros[$method])) {
            throw new BadMethodCallException("Method {$method} does not exist on " . static::class);
        }
        return (static::$macros[$method])(...$args);
    }
}
