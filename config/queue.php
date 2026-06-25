<?php
/**
 * Queue Configuration
 *
 * Drivers disponibles:
 *   'sync'     → ejecuta el job inmediatamente (sin cola real, ideal para desarrollo)
 *   'database' → almacena jobs en la tabla `jobs` de la base de datos
 *
 * Para correr el worker en producción:
 *   php hexaphp queue:work
 *   php hexaphp queue:work --queue=emails,notifications
 */
return [

    'default' => getenv('QUEUE_DRIVER') ?: 'sync',

    'drivers' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver'      => 'database',
            'table'       => 'jobs',
            'tries'       => 3,
            'retry_after' => 90,
        ],

        'redis' => [
            'driver'   => 'redis',
            'host'     => getenv('REDIS_HOST')     ?: '127.0.0.1',
            'port'     => (int)(getenv('REDIS_PORT')     ?: 6379),
            'password' => getenv('REDIS_PASSWORD') ?: null,
            'database' => (int)(getenv('REDIS_DB')       ?: 1),
            'tries'    => 3,
        ],

    ],

    'failed' => [
        'driver' => 'database',
        'table'  => 'failed_jobs',
    ],

];
