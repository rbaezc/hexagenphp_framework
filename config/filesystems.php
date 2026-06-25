<?php
return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root'   => dirname(__DIR__) . '/storage/app',
            'url'    => env('APP_URL', '') . '/storage',
        ],
        'public' => [
            'driver' => 'local',
            'root'   => dirname(__DIR__) . '/public/storage',
            'url'    => env('APP_URL', '') . '/storage',
        ],
        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID', ''),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY', ''),
            'region'                  => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket'                  => env('AWS_BUCKET', ''),
            'prefix'                  => env('AWS_PREFIX', ''),
            'endpoint'                => env('AWS_ENDPOINT', null),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
    ],
];
