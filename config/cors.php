<?php
/**
 * CORS Configuration
 *
 * Controla qué orígenes, métodos y headers pueden acceder a tu API.
 * Para cambios por entorno usa la variable CORS_ALLOWED_ORIGINS en tu .env.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Orígenes permitidos
    |--------------------------------------------------------------------------
    | Lista de orígenes que pueden hacer requests cross-origin.
    | Usa ['*'] solo para APIs completamente públicas sin cookies/credentials.
    |
    | Ejemplos:
    |   ['https://miapp.com', 'https://admin.miapp.com']
    |   ['*']
    |
    | El valor puede sobreescribirse vía la variable de entorno:
    |   CORS_ALLOWED_ORIGINS=https://miapp.com,https://admin.miapp.com
    */
    'allowed_origins' => array_filter(
        array_map('trim', explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: ''))
    ),

    /*
    |--------------------------------------------------------------------------
    | Métodos HTTP permitidos
    |--------------------------------------------------------------------------
    */
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
    |--------------------------------------------------------------------------
    | Headers permitidos en la request
    |--------------------------------------------------------------------------
    */
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-Live-State',
        'X-CSRF-Token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Headers expuestos al navegador en la response
    |--------------------------------------------------------------------------
    */
    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Credentials (cookies, Authorization headers)
    |--------------------------------------------------------------------------
    | Activa esto solo si tu frontend envía cookies o Authorization headers
    | con credenciales. Con esto activo, allowed_origins NO puede ser ['*'].
    */
    'allow_credentials' => false,

    /*
    |--------------------------------------------------------------------------
    | Duración del caché de preflight (segundos)
    |--------------------------------------------------------------------------
    | Cuánto tiempo el navegador puede cachear la respuesta OPTIONS.
    | 0 = sin caché. Valor recomendado en producción: 7200 (2 horas).
    */
    'max_age' => 0,

];
