<?php
return [
    'name'    => env('APP_NAME', 'HexaGen'),
    'env'     => env('APP_ENV', 'production'),
    'debug'   => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url'     => env('APP_URL', 'http://localhost'),
    'locale'  => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
];
