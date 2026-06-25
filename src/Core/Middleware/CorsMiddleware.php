<?php
namespace HexaGen\Core\Middleware;

use HexaGen\Core\Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Handle CORS preflight requests and append CORS headers to responses.
     * Configure everything in config/cors.php.
     */
    public function handle(Request $request, callable $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response('', 204);
            $this->addCorsHeaders($response, $request);
            return $response;
        }

        $response = $next($request);
        $this->addCorsHeaders($response, $request);
        return $response;
    }

    private function addCorsHeaders(Response $response, Request $request): void
    {
        $allowedOrigins   = Config::get('cors.allowed_origins', []);
        $allowedMethods   = Config::get('cors.allowed_methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
        $allowedHeaders   = Config::get('cors.allowed_headers', ['Content-Type', 'Authorization', 'X-Requested-With']);
        $allowCredentials = Config::get('cors.allow_credentials', false);
        $maxAge           = Config::get('cors.max_age', 0);

        if (empty($allowedOrigins)) {
            return;
        }

        $requestOrigin = $request->headers->get('Origin', '');

        if (in_array('*', $allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } elseif ($requestOrigin && in_array($requestOrigin, $allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $requestOrigin);
            $response->headers->set('Vary', 'Origin');
        } else {
            return;
        }

        $response->headers->set('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
        $response->headers->set('Access-Control-Allow-Headers', implode(', ', $allowedHeaders));

        if ($allowCredentials) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        if ($maxAge > 0) {
            $response->headers->set('Access-Control-Max-Age', (string) $maxAge);
        }
    }
}
