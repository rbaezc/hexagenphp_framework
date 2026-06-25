<?php
namespace HexaGen\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareInterface
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param callable $next The next middleware/controller handler in the pipeline.
     * @return Response
     */
    public function handle(Request $request, callable $next): Response;
}
