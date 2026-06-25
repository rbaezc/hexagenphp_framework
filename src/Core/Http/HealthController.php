<?php
namespace HexaGen\Core\Http;

use HexaGen\Core\Cache\CacheManager;
use HexaGen\Core\Database\DatabaseConnection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Built-in health check endpoint — auto-registered at GET /_health.
 * Returns 200 when healthy, 503 when any check fails.
 * Used by Kubernetes, Docker, load balancers, and uptime monitors.
 */
class HealthController
{
    public function __invoke(Request $request): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Database check
        try {
            $db = new DatabaseConnection();
            $db->getPdo()->query('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail';
            $healthy = false;
        }

        // Cache check
        try {
            $key = '_health_probe_' . time();
            CacheManager::set($key, 1, 5);
            $checks['cache'] = CacheManager::get($key) === 1 ? 'ok' : 'fail';
            CacheManager::delete($key);
        } catch (\Throwable $e) {
            $checks['cache'] = 'fail';
            $healthy = false;
        }

        $checks['memory'] = round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
        $checks['php']    = PHP_VERSION;

        return new JsonResponse(
            ['status' => $healthy ? 'ok' : 'degraded', 'checks' => $checks],
            $healthy ? 200 : 503
        );
    }
}
