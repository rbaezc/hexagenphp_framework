<?php
/**
 * Logging Configuration
 *
 * Define canales de log. El canal 'default' se usa cuando llamas log()->info(...).
 * Puedes apilar múltiples canales con el driver 'stack'.
 *
 * Drivers disponibles: 'file', 'stderr', 'stack'
 */
return [

    'default' => getenv('LOG_CHANNEL') ?: 'stack',

    'channels' => [

        'stack' => [
            'driver'   => 'stack',
            'channels' => ['file', 'stderr'],
        ],

        'file' => [
            'driver' => 'file',
            'path'   => dirname(__DIR__) . '/var/log/app.log',
            'level'  => getenv('LOG_LEVEL') ?: 'debug',
        ],

        'stderr' => [
            'driver' => 'stderr',
            'level'  => getenv('LOG_LEVEL') ?: 'debug',
        ],

    ],

];
