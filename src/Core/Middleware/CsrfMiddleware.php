<?php
namespace HexaGen\Core\Middleware;

use HexaGen\Core\Config;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Config::get('csrf.enabled', true)) {
            return $next($request);
        }

        $this->startSession();

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        $methods    = Config::get('csrf.methods', ['POST', 'PUT', 'PATCH', 'DELETE']);
        $except     = Config::get('csrf.except', []);
        $tokenName  = Config::get('csrf.token_name', '_csrf_token');
        $headerName = Config::get('csrf.header_name', 'X-CSRF-Token');

        // Safe methods don't need validation
        if (!in_array($request->getMethod(), $methods, true)) {
            return $next($request);
        }

        // Skip excluded paths
        $path = $request->getPathInfo();
        foreach ($except as $pattern) {
            if (fnmatch($pattern, $path)) {
                return $next($request);
            }
        }

        // Validate token from header (AJAX) or form body
        $requestToken = $request->headers->get($headerName)
            ?: $request->request->get($tokenName);

        if (!$requestToken || !hash_equals($_SESSION['_csrf_token'], $requestToken)) {
            return new JsonResponse(
                ['message' => 'CSRF token inválido o ausente.'],
                419
            );
        }

        return $next($request);
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
