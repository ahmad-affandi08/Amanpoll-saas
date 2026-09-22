<?php

return [
    'header_organisasi' => env('AMANPOLL_HEADER_ORGANISASI', 'X-Organisasi-Id'),
    'audit_aktif' => env('AMANPOLL_AUDIT_AKTIF', true),
    'mata_uang' => env('AMANPOLL_MATA_UANG', 'IDR'),
    'zona_waktu_default' => env('AMANPOLL_ZONA_WAKTU', 'Asia/Jakarta'),
    'retensi_catatan_akses_hari' => env('AMANPOLL_RETENSI_CATATAN_AKSES_HARI', 90),
    'disk_berkas' => env('AMANPOLL_DISK_BERKAS', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Host
    |--------------------------------------------------------------------------
    |
    | Satu instalasi melayani beberapa host (PRD 5.4, MARKETING.md 1). Host tidak
    | pernah ditulis di source maupun di frontend; seluruhnya dibaca dari sini.
    |
    | `publik` boleh null, dan itulah keadaan sebelum situs pemasaran ada: grup
    | rute publik tidak didaftarkan sama sekali, sehingga root tetap milik
    | dashboard dan tidak ada rute yang bertabrakan.
    |
    */
    'domain' => [
        'publik' => env('AMANPOLL_DOMAIN_PUBLIK'),
        'dashboard' => env('AMANPOLL_DOMAIN_DASHBOARD', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost'),
        'partner' => env('AMANPOLL_DOMAIN_PARTNER'),

        // Bentuk kanonik host publik. Bentuk lainnya dialihkan 301 ke sini,
        // supaya mesin pencari tidak memecah peringkat satu halaman ke dua URL.
        'kanonik_pakai_www' => (bool) env('AMANPOLL_DOMAIN_KANONIK_WWW', false),
        'skema_kanonik' => env('AMANPOLL_DOMAIN_SKEMA', 'https'),

        // Domain induk untuk cookie yang harus bertahan saat pengunjung
        // berpindah dari host publik ke host dashboard (MARKETING.md 1.1).
        'cookie_induk' => env('SESSION_DOMAIN'),
    ],

    'langganan' => [
        // Hari setelah tanggal berakhir yang masih memberi akses tulis penuh,
        // supaya keterlambatan administrasi pembayaran tidak langsung
        // menghentikan pekerjaan lapangan.
        'hari_tenggang' => env('AMANPOLL_LANGGANAN_HARI_TENGGANG', 7),
        'hari_uji_coba' => env('AMANPOLL_LANGGANAN_HARI_UJI_COBA', 14),
        'hari_jatuh_tempo' => env('AMANPOLL_LANGGANAN_HARI_JATUH_TEMPO', 14),
        'pajak_persen' => env('AMANPOLL_LANGGANAN_PAJAK_PERSEN', 0),

        'penyedia_pembayaran' => env('AMANPOLL_LANGGANAN_PENYEDIA', 'TransferManual'),
        // Tanpa rahasia ini, endpoint webhook pembayaran menolak seluruh
        // permintaan. Sengaja tanpa nilai bawaan yang dapat ditebak.
        'rahasia_webhook' => env('AMANPOLL_LANGGANAN_RAHASIA_WEBHOOK', ''),
        'bank_nama' => env('AMANPOLL_LANGGANAN_BANK_NAMA', 'Bank Mandiri'),
        'bank_rekening' => env('AMANPOLL_LANGGANAN_BANK_REKENING', '000-000-0000'),
        'bank_atas_nama' => env('AMANPOLL_LANGGANAN_BANK_ATAS_NAMA', 'PT Amanpoll Indonesia'),
    ],
];
