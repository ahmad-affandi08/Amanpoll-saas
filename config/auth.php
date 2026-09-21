<?php

use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

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

        // Admin platform memakai guard sesi tersendiri sehingga sesi tenant dan
        // sesi platform tidak pernah saling menggantikan (22.02).
        'platform' => [
            'driver' => 'session',
            'provider' => 'admin_platform',
        ],
    ],

    'providers' => [
        'pengguna' => [
            'driver' => 'eloquent',
            'model' => Pengguna::class,
        ],

        'admin_platform' => [
            'driver' => 'eloquent',
            'model' => AdminPlatform::class,
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
