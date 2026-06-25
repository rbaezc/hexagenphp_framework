<?php
namespace HexaGen\Core\Auth\Middleware;

use HexaGen\Core\Auth\AuthManager;
use HexaGen\Core\Middleware\MiddlewareInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege rutas que requieren autenticación.
 *
 * Por ruta (en Routes.php):
 *   new Route('/perfil', [...], ['_middleware' => [AuthMiddleware::class]])
 *
 * Con guard específico:
 *   new Route('/api/me', [...], ['_middleware' => ['auth:jwt']])
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private string $guard = 'default') {}

    public function handle(Request $request, callable $next): Response
    {
        AuthManager::setRequest($request);

        if (!AuthManager::check()) {
            return new JsonResponse(['message' => 'No autenticado.'], 401);
        }

        return $next($request);
    }
}
