<?php
namespace HexaGen\Core\Middleware;

use HexaGen\Core\Config;
use HexaGen\Core\Observability\Telemetry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Crea un span automático por cada request HTTP.
 * Registra: método, ruta, status code, duración y trace_id.
 *
 * Se activa añadiéndolo como middleware global en index.php:
 *   $kernel->addMiddleware(TelemetryMiddleware::class);
 *
 * O activando en config/telemetry.php → trace_requests: true
 * (el Kernel lo registra automáticamente en ese caso).
 */
class TelemetryMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Config::get('telemetry.enabled', true)) {
            return $next($request);
        }

        $span = Telemetry::startSpan('http.request', [
            'http.method' => $request->getMethod(),
            'http.path'   => $request->getPathInfo(),
            'http.host'   => $request->getHost(),
        ]);

        try {
            $response = $next($request);
            $span['attributes']['http.status_code'] = $response->getStatusCode();
            $span['status'] = $response->getStatusCode() >= 500 ? 'error' : 'ok';
            return $response;
        } catch (\Throwable $e) {
            $span['status'] = 'error';
            $span['error']  = $e->getMessage();
            throw $e;
        } finally {
            Telemetry::endSpan($span);
            // Expose trace_id in response header for distributed tracing correlation
            if (isset($response)) {
                $response->headers->set('X-Trace-Id', Telemetry::traceId());
            }
        }
    }
}
