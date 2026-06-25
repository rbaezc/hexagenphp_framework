<?php
namespace HexaGen\Core\Auth\Middleware;

use HexaGen\Core\Auth\AuthManager;
use HexaGen\Core\Middleware\MiddlewareInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** Solo permite el paso a usuarios NO autenticados (ej: rutas de login/registro). */
class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        AuthManager::setRequest($request);

        if (AuthManager::check()) {
            return new JsonResponse(['message' => 'Ya estás autenticado.'], 403);
        }

        return $next($request);
    }
}
