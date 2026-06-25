<?php
namespace HexaGen\Core\Broadcasting;

class BroadcastManager
{
    public static function event(ShouldBroadcast $event): void
    {
        $driver = (string) \HexaGen\Core\Config::get('broadcasting.driver', 'null');

        match ($driver) {
            'redis' => static::broadcastViaRedis($event),
            'sse'   => static::broadcastViaSse($event),
            'null'  => null,
            default => throw new \InvalidArgumentException("Unknown broadcast driver: {$driver}"),
        };
    }

    private static function broadcastViaRedis(ShouldBroadcast $event): void
    {
        $payload = json_encode([
            'event'   => $event->broadcastAs(),
            'data'    => $event->broadcastWith(),
            'channels'=> $event->broadcastOn(),
        ]);

        $redis = \HexaGen\Core\Cache\CacheManager::driver('redis');
        foreach ($event->broadcastOn() as $channel) {
            $redis->getClient()->publish("hexagen:{$channel}", $payload);
        }
    }

    private static function broadcastViaSse(ShouldBroadcast $event): void
    {
        $payload = json_encode([
            'event' => $event->broadcastAs(),
            'data'  => $event->broadcastWith(),
        ]);

        foreach ($event->broadcastOn() as $channel) {
            $file = \HexaGen\Core\Application::storagePath("framework/sse/{$channel}.json");
            $dir  = dirname($file);
            if (!is_dir($dir)) { mkdir($dir, 0755, true); }
            file_put_contents($file, $payload . "\n", FILE_APPEND | LOCK_EX);
        }
    }
}
