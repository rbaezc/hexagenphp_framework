<?php
namespace HexaGen\Core\Database\Traits;

trait HasScopes
{
    protected static array $globalScopes = [];

    public static function addGlobalScope(string $name, callable $scope): void
    {
        static::$globalScopes[static::class][$name] = $scope;
    }

    public static function withoutGlobalScope(string $name): \HexaGen\Core\Database\QueryBuilder
    {
        $qb = new \HexaGen\Core\Database\QueryBuilder(
            static::getPdo(),
            static::getTableName(),
            static::class
        );
        $qb->withoutGlobalScope($name);
        return $qb;
    }

    public static function withoutGlobalScopes(): \HexaGen\Core\Database\QueryBuilder
    {
        return new \HexaGen\Core\Database\QueryBuilder(
            static::getPdo(),
            static::getTableName(),
            static::class,
            skipGlobalScopes: true
        );
    }

    public static function getGlobalScopes(): array
    {
        return static::$globalScopes[static::class] ?? [];
    }
}
