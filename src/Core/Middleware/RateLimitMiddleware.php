<?php
namespace HexaGen\Core\Middleware;

use HexaGen\Core\Cache\CacheManager;
use HexaGen\Core\Config;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Token-bucket rate limiter using the cache layer.
 *
 * Usage per-route (in Routes.php):
 *   new Route('/api/login', [...], ['_middleware' => ['throttle:auth']])
 *
 * Usage global (in index.php before boot):
 *   $kernel->addMiddleware(RateLimitMiddleware::class);
 *
 * Named limits are configured in config/rate-limiting.php.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(private string $limitName = 'default') {}

    public function handle(Request $request, callable $next): Response
    {
        $config   = Config::get("rate-limiting.limits.$this->limitName")
                 ?? Config::get('rate-limiting.default', ['requests' => 60, 'window' => 60]);

        $maxRequests = (int)($config['requests'] ?? 60);
        $window      = (int)($config['window']   ?? 60);

        $identifier = $this->identifier($request);
        $cacheKey   = "rl:{$this->limitName}:{$identifier}";

        $cache    = CacheManager::driver();
        $attempts = (int)($cache->get($cacheKey, 0));

        if ($attempts === 0) {
            $cache->set($cacheKey, 1, $window);
        } else {
            $cache->increment($cacheKey);
            $attempts++;
        }

        $remaining = max(0, $maxRequests - $attempts);

        if ($attempts > $maxRequests) {
            return new JsonResponse(
                ['message' => 'Demasiadas solicitudes. Intenta de nuevo más tarde.'],
                429,
                [
                    'X-RateLimit-Limit'     => $maxRequests,
                    'X-RateLimit-Remaining' => 0,
                    'Retry-After'           => $window,
                ]
            );
        }

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit',     (string)$maxRequests);
        $response->headers->set('X-RateLimit-Remaining', (string)$remaining);
        return $response;
    }

    private function identifier(Request $request): string
    {
        return sha1($request->getClientIp() . '|' . $request->getPathInfo());
    }
}
