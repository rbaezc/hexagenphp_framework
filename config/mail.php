<?php
return [
    'driver' => env('MAIL_DRIVER', 'log'),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name'    => env('MAIL_FROM_NAME', 'HexaGen'),
    ],

    'smtp' => [
        'host'       => env('MAIL_SMTP_HOST', 'localhost'),
        'port'       => (int) env('MAIL_SMTP_PORT', 587),
        'username'   => env('MAIL_SMTP_USERNAME', ''),
        'password'   => env('MAIL_SMTP_PASSWORD', ''),
        'encryption' => env('MAIL_SMTP_ENCRYPTION', 'tls'),
    ],
];
