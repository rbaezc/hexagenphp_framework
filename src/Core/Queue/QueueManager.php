<?php
namespace HexaGen\Core\Queue;

use HexaGen\Core\Config;
use HexaGen\Core\Queue\Drivers\DatabaseQueue;
use HexaGen\Core\Queue\Drivers\RedisQueue;
use HexaGen\Core\Queue\Drivers\SyncQueue;

/**
 * Facade del sistema de colas. Accede vía el helper dispatch().
 */
class QueueManager
{
    private static array $resolved = [];

    public static function driver(?string $name = null): SyncQueue|DatabaseQueue|RedisQueue
    {
        $name ??= Config::get('queue.default', 'sync');

        if (isset(self::$resolved[$name])) {
            return self::$resolved[$name];
        }

        $config = Config::get("queue.drivers.$name", []);
        $driver = $config['driver'] ?? $name;

        self::$resolved[$name] = match ($driver) {
            'database' => new DatabaseQueue(),
            'redis'    => new RedisQueue(),
            default    => new SyncQueue(),
        };

        return self::$resolved[$name];
    }

    public static function push(Job $job): void
    {
        self::driver()->push($job);
    }
}
