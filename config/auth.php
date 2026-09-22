<?php

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
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

        // Admin platform memakai guard sesi tersendiri.
        'platform' => [
            'driver' => 'session',
            'provider' => 'admin_platform',
        ],

        // Portal partner hidup di hostnya sendiri dengan guard sendiri; ia bukan tenant dan bukan admin.
        'partner' => [
            'driver' => 'session',
            'provider' => 'partner',
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

        'partner' => [
            'driver' => 'eloquent',
            'model' => Partner::class,
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
