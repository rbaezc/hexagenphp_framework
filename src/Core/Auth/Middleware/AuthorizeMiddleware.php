<?php
namespace HexaGen\Core\Auth\Middleware;

use HexaGen\Core\Auth\AuthManager;
use HexaGen\Core\Auth\Gate;
use HexaGen\Core\Middleware\MiddlewareInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica un permiso o rol antes de permitir el acceso a la ruta.
 *
 * Por ruta (en Routes.php):
 *   new Route('/admin', [...], ['_middleware' => [new AuthorizeMiddleware('admin')]])
 *   new Route('/posts/delete', [...], ['_middleware' => [new AuthorizeMiddleware('eliminar-post')]])
 */
class AuthorizeMiddleware implements MiddlewareInterface
{
    public function __construct(private string $ability) {}

    public function handle(Request $request, callable $next): Response
    {
        AuthManager::setRequest($request);

        if (!AuthManager::check()) {
            return new JsonResponse(['message' => 'No autenticado.'], 401);
        }

        if (Gate::denies($this->ability)) {
            return new JsonResponse(['message' => "No autorizado para: {$this->ability}"], 403);
        }

        return $next($request);
    }
}
