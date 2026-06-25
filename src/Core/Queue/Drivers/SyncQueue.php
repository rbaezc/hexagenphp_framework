<?php
namespace HexaGen\Core\Queue\Drivers;

use HexaGen\Core\Queue\Job;
use HexaGen\Core\Log\Logger;

/** Ejecuta el job de forma inmediata en el mismo proceso. Ideal para desarrollo. */
class SyncQueue
{
    public function push(Job $job): void
    {
        try {
            $job->handle();
        } catch (\Throwable $e) {
            $job->failed($e);
            (new Logger())->error('Job sync fallido: ' . get_class($job), [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
