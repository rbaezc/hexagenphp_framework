<?php
/**
 * Cache Configuration
 *
 * Drivers disponibles: 'file', 'array' (solo en memoria, se pierde por request)
 * En el futuro: 'redis', 'memcached'
 */
return [

    'default' => getenv('CACHE_DRIVER') ?: 'file',

    'drivers' => [

        'file' => [
            'driver' => 'file',
            'path'   => dirname(__DIR__) . '/var/cache/data',
        ],

        'array' => [
            'driver' => 'array',
        ],

        'redis' => [
            'driver'   => 'redis',
            'host'     => getenv('REDIS_HOST')     ?: '127.0.0.1',
            'port'     => (int)(getenv('REDIS_PORT')     ?: 6379),
            'password' => getenv('REDIS_PASSWORD') ?: null,
            'database' => (int)(getenv('REDIS_DB')       ?: 0),
        ],

    ],

    // TTL por defecto en segundos (5 minutos)
    'ttl' => 300,

];
