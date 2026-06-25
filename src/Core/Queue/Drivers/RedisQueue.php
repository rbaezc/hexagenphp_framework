<?php
namespace HexaGen\Core\Queue\Drivers;

use HexaGen\Core\Cache\Drivers\RedisCache;
use HexaGen\Core\Config;
use HexaGen\Core\Queue\Job;

/**
 * Queue driver backed by Redis (BLPOP/RPUSH).
 * Alta concurrencia, sin polling a base de datos.
 * Configura en config/queue.php → drivers.redis
 */
class RedisQueue
{
    private RedisCache $cache;

    public function __construct()
    {
        $config      = Config::get('queue.drivers.redis', []);
        $this->cache = new RedisCache($config);
    }

    public function push(Job $job): void
    {
        $this->cache->getClient()->rpush(
            $this->queueKey($job->getQueue()),
            [serialize($job)]
        );
    }

    /**
     * Block-pop: waits up to $timeout seconds for a job.
     * Returns [queueName, payload] or null on timeout.
     */
    public function pop(string $queue = 'default', int $timeout = 3): ?array
    {
        $result = $this->cache->getClient()->blpop([$this->queueKey($queue)], $timeout);
        if (!$result) {
            return null;
        }
        return ['queue' => $queue, 'payload' => $result[1]];
    }

    /** Move a failed job to the failed queue. */
    public function fail(string $payload, string $queue, string $error): void
    {
        $this->cache->getClient()->rpush('hexagen:queue:failed', [json_encode([
            'queue'      => $queue,
            'payload'    => $payload,
            'error'      => $error,
            'failed_at'  => date('Y-m-d H:i:s'),
        ])]);
    }

    private function queueKey(string $queue): string
    {
        return "hexagen:queue:$queue";
    }
}
