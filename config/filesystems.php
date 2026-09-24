<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        /*
         * Salinan cadangan di luar server (FASE 45). Kompatibel S3: AWS S3,
         * Cloudflare R2 (endpoint https://<akun>.r2.cloudflarestorage.com,
         * region `auto`, path-style), atau penyedia S3 lain. Aktif bila
         * AMANPOLL_CADANGAN_DISK_LUAR=cadangan_luar. Tanpa `visibility`: R2 tidak
         * mengenal ACL, dan bucket cadangan memang harus privat seluruhnya.
         * `throw` menyala supaya unggahan gagal tidak tampak berhasil.
         */
        'cadangan_luar' => [
            'driver' => 's3',
            'key' => env('AMANPOLL_CADANGAN_LUAR_KEY'),
            'secret' => env('AMANPOLL_CADANGAN_LUAR_SECRET'),
            'region' => env('AMANPOLL_CADANGAN_LUAR_REGION', 'auto'),
            'bucket' => env('AMANPOLL_CADANGAN_LUAR_BUCKET'),
            'endpoint' => env('AMANPOLL_CADANGAN_LUAR_ENDPOINT'),
            'use_path_style_endpoint' => (bool) env('AMANPOLL_CADANGAN_LUAR_PATH_STYLE', true),
            // Checksum permintaan bawaan AWS SDK baru tidak didukung semua penyedia S3-kompatibel;
            // keutuhan salinan sudah diverifikasi sendiri lewat SHA-256 (SalinanLuarCadangan).
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
            'throw' => true,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
