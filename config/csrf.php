<?php
/**
 * CSRF Configuration
 *
 * Protección contra Cross-Site Request Forgery para apps con formularios HTML.
 * Para APIs puras que usan JWT o API keys, desactiva CSRF o agrega tus rutas
 * a la lista 'except'.
 *
 * Para activar el middleware, agrega en public/index.php (antes de boot()):
 *   $kernel->addMiddleware(\HexaGen\Core\Middleware\CsrfMiddleware::class);
 *
 * En tus templates Twig, incluye el token con:
 *   {{ csrf_field() }}         → campo hidden completo
 *   {{ csrf_token() }}         → solo el valor del token
 *
 * En requests AJAX, envía el header:
 *   X-CSRF-Token: <token>
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Activar / desactivar
    |--------------------------------------------------------------------------
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Nombre del campo en formularios HTML
    |--------------------------------------------------------------------------
    */
    'token_name' => '_csrf_token',

    /*
    |--------------------------------------------------------------------------
    | Nombre del header para requests AJAX / fetch
    |--------------------------------------------------------------------------
    */
    'header_name' => 'X-CSRF-Token',

    /*
    |--------------------------------------------------------------------------
    | Métodos que requieren validación
    |--------------------------------------------------------------------------
    */
    'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],

    /*
    |--------------------------------------------------------------------------
    | Rutas excluidas de la validación CSRF
    |--------------------------------------------------------------------------
    | Usa patrones fnmatch. Agrega aquí tus endpoints de API que usan
    | autenticación por token (JWT, API keys) en lugar de sesiones/cookies.
    |
    | Ejemplos:
    |   '/api/*'
    |   '/webhooks/*'
    |   '/grpc/*'
    */
    'except' => [
        // '/api/*',
        // '/webhooks/*',
    ],

];
