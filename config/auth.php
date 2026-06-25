<?php
/**
 * Authentication Configuration
 *
 * Define guards y providers. El guard por defecto se usará cuando
 * llames a auth()->user() o al middleware AuthMiddleware.
 *
 * Guards disponibles: 'session', 'jwt'
 */
return [

    'default' => getenv('AUTH_GUARD') ?: 'session',

    'guards' => [

        'session' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        'jwt' => [
            'driver'   => 'jwt',
            'provider' => 'users',
            'secret'   => getenv('JWT_SECRET') ?: '',
            'ttl'      => (int)(getenv('JWT_TTL') ?: 3600), // segundos
        ],

    ],

    'providers' => [

        'users' => [
            // Clase del modelo que implementa Authenticatable
            // 'model' => \HexaGen\Slices\Users\Domain\User::class,
            'model' => null,
        ],

    ],

];
