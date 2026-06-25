<?php
/**
 * Rate Limiting Configuration
 *
 * Define límites de requests por ruta o grupo de rutas.
 * Usa el middleware RateLimitMiddleware con el nombre del límite:
 *
 *   new Route('/api/login', [...], ['_middleware' => ['throttle:auth']])
 *
 * O globalmente para todas las rutas:
 *   $kernel->addMiddleware(\HexaGen\Core\Middleware\RateLimitMiddleware::class);
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Límite por defecto (se aplica si no se especifica uno concreto)
    |--------------------------------------------------------------------------
    | requests: número máximo de requests
    | window:   ventana de tiempo en segundos
    */
    'default' => [
        'requests' => (int)(getenv('RATE_LIMIT_REQUESTS') ?: 60),
        'window'   => (int)(getenv('RATE_LIMIT_WINDOW')   ?: 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Límites con nombre (úsalos con throttle:<nombre> en la ruta)
    |--------------------------------------------------------------------------
    */
    'limits' => [

        'api' => [
            'requests' => 120,
            'window'   => 60,
        ],

        'auth' => [
            'requests' => 10,
            'window'   => 60,
        ],

        'strict' => [
            'requests' => 5,
            'window'   => 60,
        ],

    ],

];
