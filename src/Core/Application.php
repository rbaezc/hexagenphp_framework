<?php
namespace HexaGen\Core;

class Application
{
    private static string $version = '1.0.0';
    private static string $basePath = '';

    public static function setBasePath(string $path): void
    {
        static::$basePath = rtrim($path, DIRECTORY_SEPARATOR);
    }

    public static function basePath(string $path = ''): string
    {
        $base = static::$basePath ?: dirname(__DIR__, 2);
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $base;
    }

    public static function storagePath(string $path = ''): string
    {
        $storage = static::basePath('storage');
        return $path ? $storage . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $storage;
    }

    public static function configPath(string $path = ''): string
    {
        $config = static::basePath('config');
        return $path ? $config . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $config;
    }

    public static function langPath(string $path = ''): string
    {
        $lang = static::basePath('lang');
        return $path ? $lang . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $lang;
    }

    public static function publicPath(string $path = ''): string
    {
        $public = static::basePath('public');
        return $path ? $public . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $public;
    }

    public static function environment(): string
    {
        return (string) (getenv('APP_ENV') ?: Config::get('app.env', 'production'));
    }

    public static function isProduction(): bool
    {
        return static::environment() === 'production';
    }

    public static function isLocal(): bool
    {
        return static::environment() === 'local';
    }

    public static function isTesting(): bool
    {
        return static::environment() === 'testing';
    }

    public static function isStaging(): bool
    {
        return static::environment() === 'staging';
    }

    public static function isDownForMaintenance(): bool
    {
        return file_exists(static::storagePath('framework/maintenance.php'));
    }

    public static function version(): string
    {
        return static::$version;
    }

    public static function make(string $abstract): mixed
    {
        $kernel = Kernel::getInstance();
        if ($kernel && $kernel->getContainer()->has($abstract)) {
            return $kernel->getContainer()->get($abstract);
        }
        return new $abstract();
    }

    public static function setLocale(string $locale): void
    {
        \HexaGen\Core\I18n\Translator::setLocale($locale);
    }

    public static function getLocale(): string
    {
        return \HexaGen\Core\I18n\Translator::getLocale();
    }
}
