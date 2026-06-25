<?php
/**
 * Environment Variables Configuration
 *
 * Define qué variables de entorno son requeridas para que la app arranque.
 * El framework valida estas variables en boot() y falla rápido con un mensaje claro
 * si alguna falta, en lugar de fallar con errores crípticos en runtime.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Variables requeridas
    |--------------------------------------------------------------------------
    | La app NO arrancará si alguna de estas no está definida.
    | Descomenta las que apliquen a tu proyecto.
    */
    'required' => [
        'APP_KEY',
        // 'DB_DSN',
        // 'JWT_SECRET',
    ],

    /*
    |--------------------------------------------------------------------------
    | Variables opcionales con valores por defecto documentados
    |--------------------------------------------------------------------------
    | Solo documentación — el framework no falla si faltan.
    */
    'optional' => [
        'APP_DEBUG'              => 'false',
        'APP_HTTPS'              => 'false',
        'APP_ENV'                => 'production',
        'LOG_CHANNEL'            => 'stack',
        'LOG_LEVEL'              => 'debug',
        'CACHE_DRIVER'           => 'file',
        'QUEUE_DRIVER'           => 'sync',
        'CORS_ALLOWED_ORIGINS'   => '',
        'RATE_LIMIT_REQUESTS'    => '60',
        'RATE_LIMIT_WINDOW'      => '60',
        'TELEMETRY_ENABLED'      => 'true',
        'OTEL_SERVICE_NAME'      => 'hexagen-app',
    ],

];
