<?php
namespace HexaGen\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class MaintenanceMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $file = dirname(__DIR__, 3) . '/storage/framework/maintenance.php';

        if (!file_exists($file)) {
            return $next($request);
        }

        $data = require $file;

        // Allow specific IPs through
        $allowedIps = $data['allow'] ?? [];
        if (in_array($request->getClientIp(), (array) $allowedIps, true)) {
            return $next($request);
        }

        $message = $data['message'] ?? 'Be right back.';
        $retry   = (int) ($data['retry'] ?? 60);

        $headers = ['Retry-After' => $retry];

        if (str_contains($request->headers->get('Accept', ''), 'application/json')) {
            return new JsonResponse(['message' => $message], 503, $headers);
        }

        $html = "<html><body><h1>503 Service Unavailable</h1><p>{$message}</p></body></html>";
        return new Response($html, 503, array_merge($headers, ['Content-Type' => 'text/html']));
    }
}
