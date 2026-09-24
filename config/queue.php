<?php

$koneksiBawaan = env('QUEUE_CONNECTION', 'database');

return [
    'default' => $koneksiBawaan,

    /*
    |--------------------------------------------------------------------------
    | Koneksi untuk job panjang (FASE 45)
    |--------------------------------------------------------------------------
    |
    | Job berat seperti ekspor laporan berjalan sampai lima menit, sedangkan job
    | lain selesai dalam hitungan detik. `retry_after` dimiliki koneksi, bukan
    | job: satu koneksi dengan `retry_after` 90 detik membuat ekspor yang masih
    | berjalan diambil ulang pekerja lain dan dikerjakan dobel; satu koneksi
    | dengan `retry_after` tujuh menit membuat job pendek yang prosesnya mati
    | menunggu tujuh menit sebelum dicoba lagi. Karena itu job panjang memakai
    | koneksi kedua pada tabel yang sama, dengan `retry_after`-nya sendiri, dan
    | dikerjakan pekerja cron tersendiri (`routes/console.php`).
    |
    | Di luar antrean database (mis. `sync` saat pengembangan) job panjang ikut
    | koneksi bawaan, supaya ia tetap dikerjakan seperti job lain.
    |
    */
    'koneksi_panjang' => $koneksiBawaan === 'database' ? 'database-panjang' : $koneksiBawaan,

    'connections' => [
        'sync' => ['driver' => 'sync'],

        // Job pendek (queue high dan default). Pekerjanya memakai --timeout=45.
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'AntrianPekerjaan'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => true,
        ],

        // Job panjang (queue low). Harus lebih besar dari --timeout pekerja low (310 detik).
        'database-panjang' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'AntrianPekerjaan'),
            'queue' => 'low',
            'retry_after' => (int) env('DB_QUEUE_PANJANG_RETRY_AFTER', 420),
            'after_commit' => true,
        ],
    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => env('DB_JOB_BATCHES_TABLE', 'KelompokAntrianPekerjaan'),
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => env('DB_FAILED_JOBS_TABLE', 'PekerjaanGagal'),
    ],
];
