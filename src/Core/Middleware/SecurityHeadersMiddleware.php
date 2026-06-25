<?php
namespace HexaGen\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * Append security-hardening HTTP headers to every response.
     *
     * HSTS is only added when APP_HTTPS=true is set in the environment,
     * since it must not be sent over plain HTTP.
     */
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Only send HSTS over HTTPS to avoid breaking plain-HTTP environments
        if (filter_var(getenv('APP_HTTPS'), FILTER_VALIDATE_BOOLEAN)) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Restrictive CSP by default; override via APP_CSP env var for custom policies
        $csp = getenv('APP_CSP') ?: "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'";
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
