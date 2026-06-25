<?php
namespace HexaGen\Core\Database\Traits;

use HexaGen\Core\Database\ObserverInterface;

trait HasObservers
{
    /** @var array<string, string[]> class => [observerClass, ...] */
    private static array $observers = [];

    public static function observe(string $observerClass): void
    {
        self::$observers[static::class][] = $observerClass;
    }

    protected function fireObservers(string $event): void
    {
        foreach (self::$observers[static::class] ?? [] as $observerClass) {
            $observer = new $observerClass();
            if (method_exists($observer, $event)) {
                $observer->$event($this);
            }
        }
    }
}
