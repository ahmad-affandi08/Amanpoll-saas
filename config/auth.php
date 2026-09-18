<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => 'pengguna',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'pengguna',
        ],
    ],

    'providers' => [
        'pengguna' => [
            'driver' => 'eloquent',
            'model' => App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class,
        ],
    ],

    'passwords' => [
        'pengguna' => [
            'provider' => 'pengguna',
            'table' => 'TokenResetKataSandi',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
