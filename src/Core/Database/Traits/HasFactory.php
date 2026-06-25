<?php
namespace HexaGen\Core\Database\Traits;

use HexaGen\Core\Testing\ModelFactory;

trait HasFactory
{
    public static function factory(): ModelFactory
    {
        return new ModelFactory(static::class);
    }

    /** Override in your model to define default factory attributes. */
    public static function definition(): array
    {
        return [];
    }
}
