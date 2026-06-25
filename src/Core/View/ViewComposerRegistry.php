<?php
namespace HexaGen\Core\View;

class ViewComposerRegistry
{
    private static array $shared    = [];
    private static array $composers = [];

    public static function share(string $key, mixed $value): void
    {
        static::$shared[$key] = $value;
    }

    public static function composer(string|array $templates, callable $callback): void
    {
        foreach ((array) $templates as $template) {
            static::$composers[$template][] = $callback;
        }
    }

    public static function getShared(): array
    {
        return static::$shared;
    }

    public static function applyComposers(string $template, array &$data): void
    {
        foreach (static::$composers as $pattern => $callbacks) {
            if (fnmatch($pattern, $template) || $pattern === $template) {
                foreach ($callbacks as $callback) {
                    $view = new ViewData($data);
                    $callback($view);
                    $data = array_merge($data, $view->getData());
                }
            }
        }
    }

    public static function flush(): void
    {
        static::$shared    = [];
        static::$composers = [];
    }
}
