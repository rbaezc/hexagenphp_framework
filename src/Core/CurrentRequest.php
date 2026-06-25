<?php
namespace HexaGen\Core;

use Symfony\Component\HttpFoundation\Request;

class CurrentRequest
{
    private static ?Request $instance = null;

    public static function set(Request $request): void
    {
        static::$instance = $request;
    }

    public static function get(): ?Request
    {
        return static::$instance;
    }

    public static function reset(): void
    {
        static::$instance = null;
    }
}
