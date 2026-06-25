<?php
return [
    'driver' => env('BROADCAST_DRIVER', 'null'),

    'connections' => [
        'redis' => [
            'driver'     => 'redis',
            'connection' => 'default',
        ],
        'sse' => [
            'driver' => 'sse',
        ],
        'null' => [
            'driver' => 'null',
        ],
    ],
];
